<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Documents\Form\EmailForm;
use Documents\Lib\DocumentsExport;
use Documents\Lib\DocumentsSigner;
use InvalidArgumentException;

/**
 * BaseDocuments Controller
 *
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 */
class BaseDocumentsController extends AppController
{
    /**
     * @var string $documentsScope
     */
    public $documentsScope = null;

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
        $counters = $this->DocumentsCounters->rememberForUser(
            $this->getCurrentUser()->id,
            $this->Authorization->applyScope($this->DocumentsCounters->find(), 'index'),
            $this->documentsScope
        );

        if (!empty($filter['counter'])) {
            $counter = $this->DocumentsCounters->get($filter['counter']);
            $this->Authorization->authorize($counter, 'view');
        } else {
            $counter = $this->DocumentsCounters->findDefaultCounter(
                $this->Authorization->applyScope($this->DocumentsCounters->find(), 'index'),
                strtolower($this->documentsScope),
                $this->getRequest()->getQuery('direction')
            );
            if (!$counter) {
                $this->Authorization->skipAuthorization();
                $this->Flash->error(__d('documents', 'No counters found. Please activate or add a new one.'));

                return $this->redirect(['controller' => 'DocumentsCounters']);
            }
        }

        $this->set(compact('filter', 'counter', 'counters'));

        return $counter;
    }

    /**
     * View method
     *
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view()
    {
        $args = func_get_args();
        $id = $args[0];
        $containTables = $args[1];

        $document = $this->{$this->documentsScope}->get($id, ['contain' => $containTables]);

        $this->Authorization->authorize($document);

        /** @var \Documents\Model\Table\DocumentsLinksTable $LinksTable */
        $LinksTable = TableRegistry::getTableLocator()->get('Documents.DocumentsLinks');
        $links = $LinksTable->forDocument($id, $this->documentsScope);

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counters = $DocumentsCounters->rememberForUser(
            $this->getCurrentUser()->id,
            $this->Authorization->applyScope($DocumentsCounters->find(), 'index'),
            $this->documentsScope
        );

        $currentCounter = $document->documents_counter->id;

        $this->set(compact('document', 'counters', 'links', 'currentCounter'));

        return null;
    }

    /**
     * Edit method
     *
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit()
    {
        $args = func_get_args();
        $document = $args[0];
        $containTables = (array)$args[1];

        $this->Authorization->authorize($document);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            // patch document and existing sub elements
            $document = $this->{$this->documentsScope}->patchEntity($document, $this->getRequest()->getData(), [
                'associated' => $containTables,
            ]);

            if (!$document->getErrors()) {
                // commit/rollback session
                $conn = $this->{$this->documentsScope}->getConnection();
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

                if (!empty($document->documents_attachments)) {
                    $attachmentsModel = null;
                    switch ($this->documentsScope) {
                        case 'Invoices':
                            $attachmentsModel = 'Invoice';
                            break;
                        case 'Documents':
                            $attachmentsModel = 'Document';
                            break;
                    }
                    foreach ($document->documents_attachments as $k => $attachment) {
                        $document->documents_attachments[$k]['model'] = $attachmentsModel;
                    }
                }

                if (
                    $this->{$this->documentsScope}->save($document, [
                        'uploadedFilename' => $tmpNames,
                        'associated' => $containTables,
                    ])
                ) {
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

        $this->set(compact('document'));

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
        $invoice = $this->{$this->documentsScope}->get($id);
        $this->Authorization->authorize($invoice);

        if ($this->{$this->documentsScope}->delete($invoice)) {
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
        $invoice = $this->{$this->documentsScope}->get($id);
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

                $invoice = $this->{$this->documentsScope}->patchEntity($invoice, $this->getRequest()->getData());
                $invoice->signed = $signer->getXml();

                if ($this->{$this->documentsScope}->save($invoice)) {
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
        $args = func_get_args();
        $document = $args[0];
        $containTables = (array)$args[1];

        $document = $this->{$this->documentsScope}->patchEntity($document, $this->getRequest()->getData(), [
            'associated' => $containTables,
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
        $invoice = $this->{$this->documentsScope}->get($id);
        $this->Authorization->authorize($invoice, 'edit');

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $invoice = $this->{$this->documentsScope}->patchEntity($invoice, $this->getRequest()->getData());

            if ($this->{$this->documentsScope}->save($invoice)) {
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
        $params = $this->{$this->documentsScope}->filter($filter);

        $attachments = $this->Authorization->applyScope($this->{$this->documentsScope}->find('list'), 'index')
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
        if (!empty($id) && ($id !== 'invoices')) {
            $filter = array_merge($filter, ['id' => $id]);
        }

        $ExporterClass = '\\Documents\\Lib\\' . $this->documentsScope . 'Export';
        //$Exporter = new InvoicesExport();

        $Exporter = new $ExporterClass();
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
            if (empty($id) || $id == 'invoices') {
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
                $this->{$this->documentsScope}->filter($filter)
            );
            $data = $this->Authorization->applyScope($this->{$this->documentsScope}->find(), 'index')
                ->where($params['conditions'])
                ->contain($params['contain'])
                ->order(['DocumentsCounters.title', 'Invoices.no'])
                ->all();

            $this->set(compact('data', 'filter'));
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
                $this->documentsScope . '.title LIKE' => '%' . $term . '%',
                'DocumentsCounters.active' => true,
            ];

            $result = $this->Authorization->applyScope($this->{$this->documentsScope}->find(), 'index')
                ->where($conditions)
                ->contain('DocumentsCounters')
                ->order($this->documentsScope . '.title')
                ->limit(30)
                ->all();

            $ret = [];
            foreach ($result as $i) {
                $ret[] = [
                    'label' => $i->title,
                    'value' => $i->id,
                    'no' => $i->no,
                    'model' => Inflector::singularize($this->documentsScope),
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
}
