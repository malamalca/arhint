<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Documents\Form\EmailForm;
use Documents\Lib\DocumentsExport;
use Documents\Lib\DocumentsSigner;
use Documents\Lib\DocumentsUpnQr;
use InvalidArgumentException;

/**
 * Documents Controller
 *
 * @property \Documents\Model\Table\DocumentsTable $Documents
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 */
class DocumentsController extends AppController
{
    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!empty($this->Security)) {
            if (in_array($this->getRequest()->getParam('action'), ['edit', 'editPreview'])) {
                $this->Security->setConfig(
                    'unlockedFields',
                    ['documents_taxes', 'documents_items', 'receiver', 'buyer', 'issuer']
                );
            }

            if (in_array($this->getRequest()->getParam('action'), ['editPreview'])) {
                $this->Security->setConfig('validatePost', false);
            }

            if (in_array($this->getRequest()->getParam('action'), ['sign'])) {
                $this->Security->setConfig('validatePost', false);
            }

            // post from external program like LilScan
            if (in_array($this->getRequest()->getParam('action'), ['edit']) && $this->request->hasHeader('Lil-Scan')) {
                $this->Security->setConfig('validatePost', false);
            }
        }

        return null;
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $filter = (array)$this->getRequest()->getQuery();

        $this->loadModel('Documents.DocumentsCounters');
        if (!empty($filter['counter'])) {
            $counter = $this->DocumentsCounters->get($filter['counter']);
        } else {
            $counter = $this->DocumentsCounters->findDefaultCounter(
                $this->Authorization->applyScope($this->DocumentsCounters->find(), 'index'),
                $this->getRequest()->getQuery('kind')
            );
            if (!$counter) {
                $this->Authorization->skipAuthorization();
                $this->Flash->error(__d('documents', 'No counters found. Please activate or add a new one.'));

                return $this->redirect(['controller' => 'DocumentsCounters']);
            }
        }

        $this->Authorization->authorize($counter, 'view');

        // fetch documents
        $filter['counter'] = $counter->id;
        $filter['order'] = 'Documents.counter DESC';
        $params = $this->Documents->filter($filter);

        $query = $this->Authorization->applyScope($this->Documents->find())
            ->select(['id', 'no', 'counter', 'dat_issue', 'title', 'net_total', 'total', 'project_id',
                'attachments_count', 'client.title'])
            ->join([
                'table' => 'documents_clients',
                'alias' => 'client',
                'type' => 'INNER',
                'conditions' => [
                    'client.document_id = Documents.id',
                    'client.kind' => $counter->kind == 'received' ? 'II' : 'IV',
                ],
            ])
            ->where($params['conditions'])
            ->order($params['order']);

        $data = $this->paginate($query);

        $sumQuery = $this->Authorization->applyScope($this->Documents->find());
        $documentsTotals = $sumQuery
            ->select([
                'sumTotal' => $sumQuery->func()->sum('Documents.total'),
                'sumNetTotal' => $sumQuery->func()->sum('Documents.net_total'),
            ])
            ->where($params['conditions'])
            ->disableHydration()
            ->first();

        $dateSpan = $this->Documents->maxSpan($filter['counter']);

        $counters = [];
        $controller = $this;
        $counters = Cache::remember(
            'Documents.sidebarCounters.' . $this->getCurrentUser()->id,
            function () use ($controller) {
                $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

                return $controller->Authorization->applyScope($DocumentsCounters->find(), 'index')
                    ->where(['active' => true])
                    ->order(['active', 'kind DESC', 'title'])
                    ->all();
            }
        );

        $projects = [];
        if (Plugin::isLoaded('Projects')) {
            /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

            $projectsIds = array_filter(array_unique($data->extract('project_id')->toList()));

            $projects = [];
            if (!empty($projectsIds)) {
                $projects = $ProjectsTable->find()
                    ->where(['id IN' => $projectsIds])
                    ->all()
                    ->combine('id', function ($entity) {
                        return $entity;
                    })
                    ->toArray();
            }
        }

        $this->set(compact('data', 'filter', 'counter', 'projects', 'dateSpan', 'documentsTotals', 'counters'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $containTables = [
            'Issuers', 'Buyers', 'Receivers',
            'DocumentsCounters', 'DocumentsItems', 'DocumentsTaxes', 'DocumentsAttachments',
        ];
        if (Plugin::isLoaded('Projects')) {
            $containTables[] = 'Projects';
        }
        $document = $this->Documents->get($id, ['contain' => $containTables]);

        $this->Authorization->authorize($document);

        /** @var \Documents\Model\Table\DocumentsLinksTable $LinksTable */
        $LinksTable = TableRegistry::getTableLocator()->get('Documents.DocumentsLinks');
        $links = $LinksTable->forDocument($id);

        $controller = $this;
        $counters = Cache::remember(
            'Documents.sidebarCounters.' . $this->getCurrentUser()->id,
            function () use ($controller) {
                $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

                return $controller->Authorization->applyScope($DocumentsCounters->find(), 'index')
                    ->where(['active' => true])
                    ->order(['active', 'kind DESC', 'title'])
                    ->all();
            }
        );

        $currentCounter = $document->documents_counter->id;

        $this->set(compact('document', 'counters', 'links', 'currentCounter'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $document = $this->parseRequest($id);

        $this->Authorization->authorize($document);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            // patch document and existing sub elements
            $assocModels = [
                'DocumentsTaxes',
                'DocumentsItems',
                'DocumentsAttachments',
                'Issuers',
                'Buyers',
                'Receivers',
            ];
            $document = $this->Documents->patchEntity($document, $this->getRequest()->getData(), [
                'associated' => $assocModels,
            ]);

            if (!$document->getErrors()) {
                // commit/rollback session
                $conn = $this->Documents->getConnection();
                $conn->begin();

                if ($document->isNew()) {
                    $document->getNextCounterNo();
                }

                // if there is no uploaded file, unset documents_attachments
                $tmpNames = [];

                $tmpAttachments = $this->getRequest()->getData('documents_attachments');
                if (!empty($tmpAttachments)) {
                    foreach ((array)$tmpAttachments as $tmpAttachment) {
                        if (!empty($tmpAttachment['filename']['tmp_name'])) {
                            $tmpNames[$tmpAttachment['filename']['name']] = $tmpAttachment['filename']['tmp_name'];
                        }
                    }
                }
                $scannedData = $this->getRequest()->getData('documents_attachments.0.scanned');
                if (!empty($scannedData)) {
                    $tmpName = tempnam(constant('TMP'), 'LilScan') . '.pdf';
                    file_put_contents($tmpName, base64_decode($scannedData));
                    $tmpNames['scanned.pdf'] = $tmpName;
                }

                if (count($tmpNames) == 0 && isset($document->documents_attachments)) {
                    unset($document->documents_attachments);
                }

                if ($this->Documents->save($document, ['uploadedFilename' => $tmpNames])) {
                    $conn->commit();
                    if ($this->getRequest()->is('ajax') || $this->getRequest()->is('lilScan')) {
                        $response = $this->getResponse()
                            ->withType('application/json')
                            ->withStringBody(json_encode(['document' => $document]));

                        return $response;
                    } else {
                        $this->Flash->success(__d('documents', 'The document has been saved.'));

                        $referer = $this->getRequest()->getData('referer');

                        return $this->redirect(!empty($referer) ? base64_decode($referer) : [
                            'action' => 'view',
                            $document->id,
                        ]);
                    }
                }

                $conn->rollback();
            } else {
                if ($this->getRequest()->is('ajax')) {
                    $response = $this->getResponse()
                        ->withType('application/json')
                        ->withStringBody(json_encode(['document' => $document, 'errors' => $document->getErrors()]));

                    return $response;
                } else {
                    $this->Flash->error(__d('documents', 'The document could not be saved. Please, try again.'));
                }
            }
        }

        $counter = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->find()
            ->where(['DocumentsCounters.id' => $document->counter_id])
            ->first();

        $projects = [];
        if (Plugin::isLoaded('Projects')) {
            /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
            $projectsQuery = $this->Authorization->applyScope($ProjectsTable->find(), 'index');
            $projects = $ProjectsTable->findForOwner($this->getCurrentUser()->company_id, $projectsQuery);
        }

        /** @var \Documents\Model\Table\VatsTable $VatsTable */
        $VatsTable = TableRegistry::getTableLocator()->get('Documents.Vats');
        $vatLevels = $VatsTable->levels($this->getCurrentUser()->get('company_id'));

        $currentCounter = $document->counter_id;

        $this->set(compact('document', 'counter', 'vatLevels', 'projects', 'currentCounter'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $document = $this->Documents->get($id);
        $this->Authorization->authorize($document);

        if ($this->Documents->delete($document)) {
            $this->Flash->success(__d('documents', 'The document has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The document could not be deleted. Please, try again.'));
        }

        $redirectUrl = $this->getRequest()->getQuery('redirect');
        if (!empty($redirectUrl)) {
            return $this->redirect(base64_decode($redirectUrl));
        } else {
            return $this->redirect(['action' => 'index', '?' => ['counter' => $document->counter_id]]);
        }
    }

    /**
     * Sign method
     *
     * Signs document after editing
     *
     * @param string $id Document id
     * @return \Cake\Http\Response|null
     */
    public function sign($id)
    {
        $document = $this->Documents->get($id);
        $this->Authorization->authorize($document);

        if ($this->getRequest()->is(['post', 'put'])) {
            $signTimestamp = FrozenTime::parseDateTime($this->getRequest()->getData('dat_sign'), 'yyyy-MM-ddTHH:mm:ss');
            $cert = $this->getRequest()->getData('sign_cert');

            if (empty($cert) || empty($signTimestamp)) {
                throw new InvalidArgumentException('Invalid Request Arguments');
            }

            $signer = new DocumentsSigner($id);
            $signer->setSignatureDatetime($signTimestamp);
            $signer->setCertificate($cert);

            $signature = $this->getRequest()->getData('sign_signature');
            if (empty($signature)) {
                $digest = $signer->getSigningHash();
            } else {
                $signer->setSignature($signature);

                $document = $this->Documents->patchEntity($document, $this->getRequest()->getData());
                $document->signed = $signer->getXml();

                if ($this->Documents->save($document)) {
                    $this->Flash->success(__d('documents', 'The document has been signed.'));

                    $redirectUrl = $this->getRequest()->getQuery('redirect');
                    if (!empty($redirectUrl)) {
                        return $this->redirect(base64_decode($redirectUrl));
                    } else {
                        return $this->redirect(['action' => 'view', $document->id]);
                    }
                } else {
                    $this->Flash->error(__d('documents', 'Signing document failed. Please, try again.'));
                }
            }
        }

        $this->set(compact('id', 'document'));
        $this->set('name', $document->title);
        $this->set('digest', $digest ?? '');

        return null;
    }

    /**
     * editPreview method
     *
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function editPreview()
    {
        $document = $this->parseRequest($this->getRequest()->getData('id'));
        $assocModels = ['DocumentsTaxes', 'DocumentsItems', 'DocumentsAttachments', 'Issuers', 'Buyers', 'Receivers'];
        $document = $this->Documents->patchEntity($document, $this->getRequest()->getData(), [
            'associated' => $assocModels,
        ]);

        $this->Authorization->authorize($document, 'view');

        $Exporter = new DocumentsExport();
        $data = $Exporter->export('pdf', [$document]);

        if (!empty($data)) {
            return $Exporter->response('pdf', $data);
        }

        return null;
    }

    /**
     * Templates method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function templates($id = null)
    {
        $document = $this->Documents->get($id);
        $this->Authorization->authorize($document, 'edit');

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $document = $this->Documents->patchEntity($document, $this->getRequest()->getData());

            if ($this->Documents->save($document)) {
                $this->Flash->success(__d('documents', 'The document has been saved.'));

                return $this->redirect(['action' => 'view', $document->id]);
            }
        }

        /** @var \Documents\Model\Table\DocumentsTemplatesTable $DocumentsTemplatesTable */
        $DocumentsTemplatesTable = TableRegistry::getTableLocator()->get('Documents.DocumentsTemplates');
        $templates = $DocumentsTemplatesTable->findForOwner($this->getCurrentUser()->get('company_id'));
        $this->set(compact('document', 'templates'));

        return null;
    }

    /**
     * Send document via email
     *
     * @return \Cake\Http\Response|null
     */
    public function email()
    {
        $email = new EmailForm($this->getRequest());

        if ($this->getRequest()->is('post')) {
            if ($email->execute($this->getRequest()->getData())) {
                $this->Flash->success(__d('documents', 'The document(s) has been sent.'));

                return $this->redirect($this->getRequest()->getData('referer') ?? ['action' => 'index']);
            } else {
                $this->Flash->error(__d('documents', 'Please correct all marked fields below.'));
            }
        }

        $filter = (array)$this->getRequest()->getQuery();
        $params = $this->Documents->filter($filter);

        $attachments = $this->Authorization->applyScope($this->Documents->find('list'), 'index')
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->limit(20)
            ->toArray();

        $this->set(compact('email', 'attachments'));

        return null;
    }

    /**
     * preview method
     *
     * @param string|null $id Document id.
     * @param string|null $name Display name.
     * @return \Cake\Http\Response|null
     */
    public function preview($id = null, $name = null)
    {
        $this->Authorization->skipAuthorization();
        if (empty($id)) {
            $this->set('filter', $this->getRequest()->getQuery());
        } else {
            $this->set(compact('id', 'name'));
        }

        return null;
    }

    /**
     * Export method
     *
     * @param string|null $id Document id.
     * @param string|null $name Document slug.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function export($id = null, $name = null)
    {
        $filter = (array)$this->getRequest()->getQuery();
        if (!empty($id) && ($id !== 'documents')) {
            $filter = array_merge($filter, ['id' => $id]);
        }

        $Exporter = new DocumentsExport();
        $documents = $Exporter->find($filter);
        $this->Authorization->applyScope($documents, 'index');
        $documents = $documents->toArray();

        $ext = $this->getRequest()->getParam('_ext');
        if (!empty($name) && substr($name, -4) == 'sepa') {
            $ext = 'sepa';
        }
        if (!empty($name) && substr($name, -6) == 'slog20') {
            $ext = 'eslog20';
        }

        $data = $Exporter->export($ext, $documents);

        if (!empty($data)) {
            $options = ['download' => $this->getRequest()->getQuery('download')];
            if (count($documents) == 1) {
                $first = reset($documents);
                $options['filename'] = Text::slug($first->title);
            }

            return $Exporter->response($ext, $data, $options);
        } else {
            $this->Flash->error(__d('documents', 'Error exporting to specified format.'));
            if (empty($id)) {
                $this->redirect(['action' => 'index', '?' => $filter]);
            } else {
                $this->redirect(['action' => 'view', $id]);
            }
        }

        return null;
    }

    /**
     * report method
     *
     * @return \Cake\Http\Response|null
     */
    public function report()
    {
        $this->Authorization->skipAuthorization();

        if (!empty($this->getRequest()->getQuery('kind'))) {
            /** @var array $filter */
            $filter = $this->getRequest()->getQuery();

            if ($filter['kind'] == 'span') {
                unset($filter['month']);
                $filter['start'] = FrozenTime::parse($filter['start']);
                $filter['end'] = FrozenTime::parse($filter['end']);
            } else {
                unset($filter['start']);
                unset($filter['end']);
                $filter['month'] = $filter['year'] . '-' . str_pad($filter['month'], 2, '0', STR_PAD_LEFT);
                unset($filter['year']);
            }

            $params = array_merge_recursive(
                ['conditions' => [
                    'DocumentsCounters.active' => true,
                ],
                'contain' => ['DocumentsCounters']],
                $this->Documents->filter($filter)
            );
            $data = $this->Authorization->applyScope($this->Documents->find(), 'index')
                ->where($params['conditions'])
                ->contain($params['contain'])
                ->order(['DocumentsCounters.title', 'Documents.no'])
                ->all();

            $this->set(compact('data'));
            $this->viewBuilder()->setClassName('Lil.Pdf');

            $this->response = $this->response->withType('pdf');
        }

        return null;
    }

    /**
     * autocomplete method
     *
     * @return \Cake\Http\Response|null
     */
    public function autocomplete()
    {
        if ($this->getRequest()->is('ajax')) {
            $term = $this->getRequest()->getQuery('term');

            $conditions = [
                'Documents.title LIKE' => '%' . $term . '%',
                'DocumentsCounters.active' => true,
            ];

            $result = $this->Authorization->applyScope($this->Documents->find(), 'index')
                ->where($conditions)
                ->contain('DocumentsCounters')
                ->order('Documents.title')
                ->limit(30)
                ->all();

            $ret = [];
            foreach ($result as $i) {
                $ret[] = [
                    'label' => $i->title,
                    'value' => $i->id,
                    'no' => $i->no,
                    'counter' => $i->documents_counter->title,
                ];
            }

            $response = $this->getResponse()
                ->withType('application/json')
                ->withStringBody(json_encode($ret));

            return $response;
        } else {
            throw new NotFoundException(__d('documents', 'Invalid request.'));
        }
    }

    /**
     * Upnqr method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function upnqr($id = null)
    {
        $document = $this->Documents->get($id);
        $this->Authorization->authorize($document, 'view');

        $imageData = DocumentsUpnQr::generateUpnQr($id);

        $response = $this->response;
        $response = $response->withStringBody($imageData);
        $response = $response->withType('png');

        return $response;
    }

    /**
     * parseRequest method
     *
     * @param string|null $id Document id.
     * @return \Documents\Model\Entity\Document
     */
    private function parseRequest($id = null)
    {
        if (!empty($id)) {
            $document = $this->Documents->get($id, ['contain' => ['Issuers', 'Buyers', 'Receivers',
                'DocumentsTaxes', 'DocumentsItems', 'DocumentsCounters']]);
        } else {
            /** @var \Documents\Model\Table\DocumentsClientsTable $DocumentsClients */
            $DocumentsClients = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');
            /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
            $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

            $sourceId = $this->getRequest()->getQuery('duplicate');
            if (!empty($sourceId)) {
                // clone
                $document = $this->Documents->get($sourceId, ['contain' => ['Issuers', 'Buyers', 'Receivers',
                    'DocumentsTaxes', 'DocumentsItems', 'DocumentsCounters']]);

                $document->setNew(true);
                unset($document->id);

                foreach ($document->documents_items as &$item) {
                    $item->setNew(true);
                    unset($item->id);
                    unset($item->document_id);
                }

                foreach ($document->documents_taxes as &$tax) {
                    $tax->setNew(true);
                    unset($tax->id);
                    unset($tax->document_id);
                }

                $document->issuer->setNew(true);
                unset($document->issuer->id);
                unset($document->issuer->document_id);

                $document->buyer->setNew(true);
                unset($document->buyer->id);
                unset($document->buyer->document_id);

                $document->receiver->setNew(true);
                unset($document->receiver->id);
                unset($document->receiver->document_id);

                $counterId = $this->getRequest()->getQuery('counter', $document->counter_id);

                $document->documents_counter = $DocumentsCounters->get($counterId);

                $document->counter_id = $document->documents_counter->id;
                $document->doc_type = $document->documents_counter->doc_type;
            } else {
                // new entity
                $document = $this->Documents->newEmptyEntity();

                $document->owner_id = $this->getCurrentUser()->get('company_id');

                $document->issuer = $DocumentsClients->newEntity(['kind' => 'II']);
                $document->receiver = $DocumentsClients->newEntity(['kind' => 'IV']);
                $document->buyer = $DocumentsClients->newEntity(['kind' => 'BY']);

                $counterId = $this->getRequest()->getQuery('counter');
                if (empty($counterId)) {
                    $counterId = $this->getRequest()->getData('counter_id');
                }

                $document->documents_counter = $DocumentsCounters->get($counterId);

                $document->counter_id = $document->documents_counter->id;
                $document->doc_type = $document->documents_counter->doc_type;

                switch ($document->documents_counter->kind) {
                    case 'issued':
                        $document->issuer->patchWithAuth($this->getCurrentUser());
                        break;
                    case 'received':
                        $document->receiver->patchWithAuth($this->getCurrentUser());
                        $document->buyer->patchWithAuth($this->getCurrentUser());
                        break;
                }
            }

            $document->no = $DocumentsCounters->generateNo($document->counter_id);
        }

        return $document;
    }
}
