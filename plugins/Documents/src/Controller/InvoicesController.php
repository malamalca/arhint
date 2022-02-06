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
 * Invoices Controller
 *
 * @property \Documents\Model\Table\InvoicesTable $Invoices
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 */
class InvoicesController extends AppController
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
                    ['invoices_taxes', 'invoices_items', 'receiver', 'buyer', 'issuer']
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

        // fetch invoices
        $filter['counter'] = $counter->id;
        $filter['order'] = 'Invoices.counter DESC';
        $params = $this->Invoices->filter($filter);

        $query = $this->Authorization->applyScope($this->Invoices->find())
            ->select(['id', 'no', 'counter', 'dat_issue', 'title', 'net_total', 'total', 'project_id',
                'attachments_count', 'client.title'])
            ->join([
                'table' => 'documents_clients',
                'alias' => 'Client',
                'type' => 'INNER',
                'conditions' => [
                    'Client.document_id = Invoices.id',
                    'Client.kind' => $counter->kind == 'received' ? 'II' : 'IV',
                ],
            ])
            ->where($params['conditions'])
            ->order($params['order']);

        $data = $this->paginate($query);

        $sumQuery = $this->Authorization->applyScope($this->Invoices->find());
        $invoicesTotals = $sumQuery
            ->select([
                'sumTotal' => $sumQuery->func()->sum('Invoices.total'),
                'sumNetTotal' => $sumQuery->func()->sum('Invoices.net_total'),
            ])
            ->join([
                'table' => 'documents_clients',
                'alias' => 'Client',
                'type' => 'INNER',
                'conditions' => [
                    'Client.document_id = Invoices.id',
                    'Client.kind' => $counter->kind == 'received' ? 'II' : 'IV',
                ],
            ])
            ->where($params['conditions'])
            ->disableHydration()
            ->first();

        $dateSpan = $this->Invoices->maxSpan($filter['counter']);

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

        $this->set(compact('data', 'filter', 'counter', 'projects', 'dateSpan', 'invoicesTotals', 'counters'));

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
            'DocumentsCounters', 'InvoicesItems', 'InvoicesTaxes', 'DocumentsAttachments',
        ];
        if (Plugin::isLoaded('Projects')) {
            $containTables[] = 'Projects';
        }
        $invoice = $this->Invoices->get($id, ['contain' => $containTables]);

        $this->Authorization->authorize($invoice);

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

        $currentCounter = $invoice->documents_counter->id;

        $this->set(compact('invoice', 'counters', 'links', 'currentCounter'));

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
        $invoice = $this->parseRequest($id);

        $this->Authorization->authorize($invoice);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            // patch document and existing sub elements
            $assocModels = [
                'InvoicesTaxes',
                'InvoicesItems',
                'DocumentsAttachments',
                'Issuers',
                'Buyers',
                'Receivers',
            ];
            $invoice = $this->Invoices->patchEntity($invoice, $this->getRequest()->getData(), [
                'associated' => $assocModels,
            ]);

            if (!$invoice->getErrors()) {
                // commit/rollback session
                $conn = $this->Invoices->getConnection();
                $conn->begin();

                if ($invoice->isNew()) {
                    $invoice->getNextCounterNo();
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

                if (count($tmpNames) == 0 && isset($invoice->documents_attachments)) {
                    unset($invoice->documents_attachments);
                }

                if ($this->Invoices->save($invoice, ['uploadedFilename' => $tmpNames])) {
                    $conn->commit();
                    if ($this->getRequest()->is('ajax') || $this->getRequest()->is('lilScan')) {
                        $response = $this->getResponse()
                            ->withType('application/json')
                            ->withStringBody(json_encode(['invoice' => $invoice]));

                        return $response;
                    } else {
                        $this->Flash->success(__d('documents', 'The document has been saved.'));

                        $referer = $this->getRequest()->getData('referer');

                        return $this->redirect(!empty($referer) ? base64_decode($referer) : [
                            'action' => 'view',
                            $invoice->id,
                        ]);
                    }
                }

                $conn->rollback();
            } else {
                if ($this->getRequest()->is('ajax')) {
                    $response = $this->getResponse()
                        ->withType('application/json')
                        ->withStringBody(json_encode(['invoice' => $invoice, 'errors' => $invoice->getErrors()]));

                    return $response;
                } else {
                    $this->Flash->error(__d('documents', 'The document could not be saved. Please, try again.'));
                }
            }
        }

        $counter = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->find()
            ->where(['DocumentsCounters.id' => $invoice->counter_id])
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

        $currentCounter = $invoice->counter_id;

        $this->set(compact('invoice', 'counter', 'vatLevels', 'projects', 'currentCounter'));

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
        $invoice = $this->Invoices->get($id);
        $this->Authorization->authorize($invoice);

        if ($this->Invoices->delete($invoice)) {
            $this->Flash->success(__d('documents', 'The invoice has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The invoice could not be deleted. Please, try again.'));
        }

        $redirectUrl = $this->getRequest()->getQuery('redirect');
        if (!empty($redirectUrl)) {
            return $this->redirect(base64_decode($redirectUrl));
        } else {
            return $this->redirect(['action' => 'index', '?' => ['counter' => $invoice->counter_id]]);
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
        $invoice = $this->Invoices->get($id);
        $this->Authorization->authorize($invoice);

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

                $invoice = $this->Invoices->patchEntity($invoice, $this->getRequest()->getData());
                $invoice->signed = $signer->getXml();

                if ($this->Invoices->save($invoice)) {
                    $this->Flash->success(__d('documents', 'The document has been signed.'));

                    $redirectUrl = $this->getRequest()->getQuery('redirect');
                    if (!empty($redirectUrl)) {
                        return $this->redirect(base64_decode($redirectUrl));
                    } else {
                        return $this->redirect(['action' => 'view', $invoice->id]);
                    }
                } else {
                    $this->Flash->error(__d('documents', 'Signing document failed. Please, try again.'));
                }
            }
        }

        $this->set(compact('id', 'invoice'));
        $this->set('name', $invoice->title);
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
        $invoice = $this->parseRequest($this->getRequest()->getData('id'));
        $assocModels = ['InvoicesTaxes', 'InvoicesItems', 'DocumentsAttachments', 'Issuers', 'Buyers', 'Receivers'];
        $invoice = $this->Invoices->patchEntity($invoice, $this->getRequest()->getData(), [
            'associated' => $assocModels,
        ]);

        $this->Authorization->authorize($invoice, 'view');

        $Exporter = new DocumentsExport();
        $data = $Exporter->export('pdf', [$invoice]);

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
        $invoice = $this->Invoices->get($id);
        $this->Authorization->authorize($invoice, 'edit');

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $invoice = $this->Invoices->patchEntity($invoice, $this->getRequest()->getData());

            if ($this->Invoices->save($invoice)) {
                $this->Flash->success(__d('documents', 'The document has been saved.'));

                return $this->redirect(['action' => 'view', $invoice->id]);
            }
        }

        /** @var \Documents\Model\Table\DocumentsTemplatesTable $DocumentsTemplatesTable */
        $DocumentsTemplatesTable = TableRegistry::getTableLocator()->get('Documents.DocumentsTemplates');
        $templates = $DocumentsTemplatesTable->findForOwner($this->getCurrentUser()->get('company_id'));
        $this->set(compact('invoice', 'templates'));

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
        $params = $this->Invoices->filter($filter);

        $attachments = $this->Authorization->applyScope($this->Invoices->find('list'), 'index')
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
                $this->Invoices->filter($filter)
            );
            $data = $this->Authorization->applyScope($this->Invoices->find(), 'index')
                ->where($params['conditions'])
                ->contain($params['contain'])
                ->order(['DocumentsCounters.title', 'Invoices.no'])
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
                'Invoices.title LIKE' => '%' . $term . '%',
                'DocumentsCounters.active' => true,
            ];

            $result = $this->Authorization->applyScope($this->Invoices->find(), 'index')
                ->where($conditions)
                ->contain('DocumentsCounters')
                ->order('Invoices.title')
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
        $invoice = $this->Invoices->get($id);
        $this->Authorization->authorize($invoice, 'view');

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
     * @return \Documents\Model\Entity\Invoice
     */
    private function parseRequest($id = null)
    {
        if (!empty($id)) {
            $invoice = $this->Invoices->get($id, ['contain' => ['Issuers', 'Buyers', 'Receivers',
                'InvoicesTaxes', 'InvoicesItems', 'DocumentsCounters']]);
        } else {
            /** @var \Documents\Model\Table\DocumentsClientsTable $DocumentsClients */
            $DocumentsClients = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');
            /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
            $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

            $sourceId = $this->getRequest()->getQuery('duplicate');
            if (!empty($sourceId)) {
                // clone
                $invoice = $this->Invoices->get($sourceId, ['contain' => ['Issuers', 'Buyers', 'Receivers',
                    'InvoicesTaxes', 'InvoicesItems', 'DocumentsCounters']]);

                $invoice->setNew(true);
                unset($invoice->id);

                foreach ($invoice->invoices_items as &$item) {
                    $item->setNew(true);
                    unset($item->id);
                    unset($item->document_id);
                }

                foreach ($invoice->invoices_taxes as &$tax) {
                    $tax->setNew(true);
                    unset($tax->id);
                    unset($tax->document_id);
                }

                $invoice->issuer->setNew(true);
                unset($invoice->issuer->id);
                unset($invoice->issuer->document_id);

                $invoice->buyer->setNew(true);
                unset($invoice->buyer->id);
                unset($invoice->buyer->document_id);

                $invoice->receiver->setNew(true);
                unset($invoice->receiver->id);
                unset($invoice->receiver->document_id);

                $counterId = $this->getRequest()->getQuery('counter', $invoice->counter_id);

                $invoice->documents_counter = $DocumentsCounters->get($counterId);

                $invoice->counter_id = $invoice->documents_counter->id;
                $invoice->doc_type = $invoice->documents_counter->doc_type;
            } else {
                // new entity
                $invoice = $this->Invoices->newEmptyEntity();

                $invoice->owner_id = $this->getCurrentUser()->get('company_id');

                $invoice->issuer = $DocumentsClients->newEntity(['kind' => 'II']);
                $invoice->receiver = $DocumentsClients->newEntity(['kind' => 'IV']);
                $invoice->buyer = $DocumentsClients->newEntity(['kind' => 'BY']);

                $counterId = $this->getRequest()->getQuery('counter');
                if (empty($counterId)) {
                    $counterId = $this->getRequest()->getData('counter_id');
                }

                $invoice->documents_counter = $DocumentsCounters->get($counterId);

                $invoice->counter_id = $invoice->documents_counter->id;
                $invoice->doc_type = $invoice->documents_counter->doc_type;

                switch ($invoice->documents_counter->kind) {
                    case 'issued':
                        $invoice->issuer->patchWithAuth($this->getCurrentUser());
                        break;
                    case 'received':
                        $invoice->receiver->patchWithAuth($this->getCurrentUser());
                        $invoice->buyer->patchWithAuth($this->getCurrentUser());
                        break;
                }
            }

            $invoice->no = $DocumentsCounters->generateNo($invoice->counter_id);
        }

        return $invoice;
    }
}
