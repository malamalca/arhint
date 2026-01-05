<?php
declare(strict_types=1);

namespace Documents\Controller;

use App\Lib\ArhintReport;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Documents\Form\EmailForm;
use Documents\Lib\DocumentsSignatureInfo;
use Documents\Lib\DocumentsSigner;
use Exception;
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
    public string $documentsScope;

    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (in_array($this->getRequest()->getParam('action'), ['editPreview'])) {
            $this->FormProtection->setConfig('validate', false);
        }

        if (in_array($this->getRequest()->getParam('action'), ['sign'])) {
            $this->FormProtection->setConfig('validatePost', false);
            $this->FormProtection->setConfig('validate', false);
        }

        // post from external program like LilScan
        if (in_array($this->getRequest()->getParam('action'), ['edit']) && $this->request->hasHeader('Lil-Scan')) {
            $this->FormProtection->setConfig('validatePost', false);
        }
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $filter = (array)$this->getRequest()->getQuery();

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCountersTable */
        $DocumentsCountersTable = $this->fetchTable('Documents.DocumentsCounters');
        $counters = $DocumentsCountersTable->rememberForUser(
            $this->getCurrentUser()->id,
            $this->Authorization->applyScope($DocumentsCountersTable->find(), 'index'),
            $this->documentsScope,
        );

        if (!empty($filter['counter'])) {
            $counter = $DocumentsCountersTable->get($filter['counter']);
            $this->Authorization->authorize($counter, 'view');
        } else {
            $counter = $DocumentsCountersTable->findDefaultCounter(
                $this->Authorization->applyScope($DocumentsCountersTable->find(), 'index'),
                strtolower($this->documentsScope),
                $this->getRequest()->getQuery('direction'),
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
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view()
    {
        $args = func_get_args();
        $id = $args[0];
        $containTables = $args[1];

        $document = $this->{$this->documentsScope}->get($id, contain: $containTables);

        $this->Authorization->authorize($document);

        /** @var \Documents\Model\Table\DocumentsLinksTable $LinksTable */
        $LinksTable = TableRegistry::getTableLocator()->get('Documents.DocumentsLinks');
        $links = $LinksTable->forDocument($id, $this->documentsScope);

        /** @var \Documents\Model\Table\DocumentsLogsTable $LogsTable */
        $LogsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsLogs');
        $logs = $LogsTable->forDocument($id, $this->documentsScope);

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counters = $DocumentsCounters->rememberForUser(
            $this->getCurrentUser()->id,
            $this->Authorization->applyScope($DocumentsCounters->find(), 'index'),
            $this->documentsScope,
        );

        $currentCounter = $document->documents_counter->id;

        $this->set(compact('document', 'counters', 'links', 'logs', 'currentCounter'));
    }

    /**
     * Edit method
     *
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(): ?Response
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

                if (
                    $this->{$this->documentsScope}->save($document, ['associated' => $containTables])
                ) {
                    $conn->commit();
                    if ($this->getRequest()->is('ajax') || $this->getRequest()->is('lilScan')) {
                        $response = $this->getResponse()
                            ->withType('application/json')
                            ->withStringBody((string)json_encode(['document' => $document]));

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
                        ->withStringBody(
                            (string)json_encode(['document' => $document, 'errors' => $document->getErrors()]),
                        );

                    return $response;
                } else {
                    $this->Flash->error(__d('documents', 'The document could not be saved. Please, try again.'));
                }
            }
        }

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
    public function delete(?string $id = null): ?Response
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
    public function sign(string $id): ?Response
    {
        $invoice = $this->{$this->documentsScope}->get($id);
        $this->Authorization->authorize($invoice);

        if ($this->getRequest()->is(['post', 'put'])) {
            $signTimestamp = new DateTime($this->getRequest()->getData('dat_sign'));
            $cert = $this->getRequest()->getData('sign_cert');

            if (empty($cert)) {
                throw new InvalidArgumentException('Invalid Request Arguments');
            }

            $signer = new DocumentsSigner($id, $this->documentsScope);
            $signer->setSignatureDatetime($signTimestamp);
            $signer->setCertificate($cert);

            $signature = $this->getRequest()->getData('sign_signature');
            if (empty($signature)) {
                $digest = $signer->getSigningHash();

                /** Return digest for signing */
                if ($this->getRequest()->is('ajax')) {
                    return $this->response->withType('application/json')->withStringBody((string)json_encode([
                        'digest' => $digest,
                    ], JSON_UNESCAPED_SLASHES));
                }
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
     * @param array<mixed> $args Arguments
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function editPreview(array ...$args)
    {
        $document = $args[0][0];
        $containTables = (array)$args[0][1];

        $document = $this->{$this->documentsScope}->patchEntity($document, $this->getRequest()->getData(), [
            'associated' => $containTables,
        ]);

        $this->Authorization->authorize($document, 'view');

        $ExporterClass = '\\Documents\\Lib\\' . $this->documentsScope . 'Export';
        /** @var \Documents\Lib\InvoicesExport|\Documents\Lib\DocumentsExport $Exporter */
        $Exporter = new $ExporterClass();
        $data = $Exporter->export('pdf', [$document]);

        if (!empty($data)) {
            return $Exporter->response('pdf', $data);
        }
    }

    /**
     * Templates method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function templates(?string $id = null): ?Response
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
    public function email(): ?Response
    {
        $exporterClass = '\\Documents\\Lib\\' . $this->documentsScope . 'Export';
        $email = new EmailForm($this->getRequest(), $exporterClass);

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

        $attachments = $this->Authorization->applyScope($this->{$this->documentsScope}->find(), 'index')
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->all();

        $this->set(compact('email', 'attachments'));

        return null;
    }

    /**
     * preview method
     *
     * @param string|null $id Document id.
     * @param string|null $name Display name.
     * @return \Cake\Http\Response|void
     */
    public function preview(?string $id = null, ?string $name = null)
    {
        $this->Authorization->skipAuthorization();
        if (empty($id)) {
            $this->set('filter', $this->getRequest()->getQuery());
        } else {
            $name = base64_decode((string)$name);
            $this->set(compact('id', 'name'));
        }
    }

    /**
     * Export method
     *
     * @param string|null $id Document id.
     * @param string|null $name Document slug.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function export(?string $id = null, ?string $name = null)
    {
        $filter = (array)$this->getRequest()->getQuery();
        if (!empty($id) && ($id !== 'invoices')) {
            $filter = array_merge($filter, ['id' => $id]);
        }

        $ExporterClass = '\\Documents\\Lib\\' . $this->documentsScope . 'Export';

        /** @var \Documents\Lib\InvoicesExport|\Documents\Lib\DocumentsExport $Exporter */
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
                $options['filename'] = Text::slug($name ?? $first->title);
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
    }

    /**
     * report method
     *
     * @return \Cake\Http\Response|void
     */
    public function report()
    {
        $this->Authorization->skipAuthorization();

        if (!empty($this->getRequest()->getQuery('kind'))) {
            /** @var array<string, mixed> $filter */
            $filter = $this->getRequest()->getQuery();

            if ($filter['kind'] == 'span') {
                unset($filter['month']);
                $filter['start'] = $filter['start'];
                $filter['end'] = $filter['end'];
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
                $this->{$this->documentsScope}->filter($filter),
            );
            $data = $this->Authorization->applyScope($this->{$this->documentsScope}->find(), 'index')
                ->where($params['conditions'])
                ->contain($params['contain'])
                ->orderBy(['DocumentsCounters.title', 'Invoices.no'])
                ->all();

            $report = new ArhintReport(
                'Invoices.index',
                $this->request,
                ['title' => __d('documents', 'Documents')],
            );
            $report->set(compact('data', 'filter'));

            $tmpName = $report->export();

            $this->redirect([
                'plugin' => false,
                'controller' => 'Pages',
                'action' => 'report',
                'Invoices.index',
                substr($tmpName, 0, -4),
            ]);
        }

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counters = $DocumentsCounters->rememberForUser(
            $this->getCurrentUser()->id,
            $this->Authorization->applyScope($DocumentsCounters->find(), 'index'),
            $this->documentsScope,
        );

        $this->set(compact('counters'));
    }

    /**
     * autocomplete method
     *
     * @return \Cake\Http\Response
     */
    public function autocomplete(): Response
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $term = $this->getRequest()->getQuery('term');

            $conditions = [
                $this->documentsScope . '.title LIKE' => '%' . $term . '%',
                'DocumentsCounters.active' => true,
            ];

            $result = $this->Authorization->applyScope($this->{$this->documentsScope}->find(), 'index')
                ->where($conditions)
                ->contain('DocumentsCounters')
                ->orderBy($this->documentsScope . '.title')
                ->limit(30)
                ->all();

            $ret = [];
            foreach ($result as $i) {
                $ret[] = [
                    'id' => $i->id,
                    'text' => '<span class="document-autocomplete-no">' . $i->no . '</span>' .
                        ' - ' . $i->title .
                        '<span class="document-autocomplete-counter">' .
                        '(' . $i->documents_counter->title . ')' .
                        '</span>',
                    'title' => $i->title,
                    'no' => $i->no,
                    'model' => Inflector::singularize($this->documentsScope),
                    'counter' => $i->documents_counter->title,
                ];
            }

            $response = $this->getResponse()
                ->withType('application/json')
                ->withStringBody((string)json_encode($ret));

            return $response;
        } else {
            throw new NotFoundException(__d('documents', 'Invalid request.'));
        }
    }

    /**
     * Check signature validity
     *
     * @param string $id Document id
     * @return \Cake\Http\Response
     */
    public function checkSignature(string $id): ?Response
    {
        $containTables = [
            'Issuers', 'Buyers', 'Receivers',
            'DocumentsCounters', 'InvoicesItems', 'InvoicesTaxes', 'Attachments',
        ];
        $invoice = $this->{$this->documentsScope}->get($id, contain: $containTables);
        $this->Authorization->authorize($invoice, 'view');

        /** @var \Documents\Model\Entity\Document|\Documents\Model\Entity\Invoice $invoice */
        $checkStatus = 'unknown';
        $errors = [];
        $signatureInfo = null;

        if (empty($invoice->signed)) {
            $checkStatus = 'nosignature';
            $errors[] = DocumentsSignatureInfo::validationErrorMessage('nosignature');
        } else {
            try {
                // Generate fresh XML from current invoice data
                $ExporterClass = '\\Documents\\Lib\\' . $this->documentsScope . 'Export';
                /** @var \Documents\Lib\InvoicesExport|\Documents\Lib\DocumentsExport $Exporter */
                $Exporter = new $ExporterClass();
                $currentXml = $Exporter->export('xml', [$invoice]);

                // Create signature info instance and compare with current data
                $signatureInfo = new DocumentsSignatureInfo($invoice->signed);
                $validationResult = $signatureInfo->compareWithCurrent(
                    $currentXml,
                    $invoice->dat_sign ?? null,
                );

                $checkStatus = $validationResult['errorCode'];
                $errors = $validationResult['errors'];
            } catch (Exception $e) {
                $checkStatus = 'error';
                $errors = [$e->getMessage()];
            }
        }

        $result = [
            'status' => $checkStatus,
            'errors' => $errors,
        ];

        // Add signature info if validation was successful
        if ($checkStatus === 'valid' && $signatureInfo !== null) {
            $result['signatureInfo'] = [
                'signatureDate' => $signatureInfo->getSignatureDate(),
                'certificate' => $signatureInfo->getCertificateInfo(),
            ];
        }

        $result['message'] = DocumentsSignatureInfo::signatureStatusMessage($result);

        $response = $this->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode($result));

        return $response;
    }
}
