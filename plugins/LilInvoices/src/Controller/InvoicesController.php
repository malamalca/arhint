<?php
declare(strict_types=1);

namespace LilInvoices\Controller;

use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use InvalidArgumentException;
use LilInvoices\Form\EmailForm;
use LilInvoices\Lib\LilInvoicesExport;
use LilInvoices\Lib\LilInvoicesSigner;

/**
 * Invoices Controller
 *
 * @property \LilInvoices\Model\Table\InvoicesTable $Invoices
 * @property \LilInvoices\Model\Table\InvoicesCountersTable $InvoicesCounters
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
            if (in_array($this->getRequest()->getParam('action'), ['add', 'edit', 'editPreview'])) {
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

        $this->loadModel('LilInvoices.InvoicesCounters');
        if (!empty($filter['counter'])) {
            $counter = $this->InvoicesCounters->get($filter['counter']);
        } else {
            $counter = $this->InvoicesCounters->findDefaultCounter(
                $this->getCurrentUser()->get('company_id'),
                $this->getRequest()->getQuery('kind')
            );
            if (!$counter) {
                $this->Flash->error(__d('lil_invoices', 'No counters found. Please activate or add a new one.'));

                return $this->redirect(['controller' => 'InvoicesCounters']);
            }
        }

        $this->Authorization->authorize($counter, 'view');

        // fetch invoices
        $filter['counter'] = $counter->id;
        $filter['order'] = 'Invoices.counter DESC';
        $params = $this->Invoices->filter($filter);

        $invoiceFields = ['id', 'no', 'counter', 'dat_issue', 'title', 'net_total', 'total', 'project_id'];
        $clientFields = ['Issuers.title', 'Receivers.title'];
        $params['contain'] = ['Issuers', 'Receivers'];

        $query = $this->Authorization->applyScope($this->Invoices->find())
            ->select($invoiceFields)
            ->contain($params['contain'])
            ->where($params['conditions']);

        // use original query for SUM()
        $sumQuery = clone $query;
        $invoicesTotals = $sumQuery->select([
            'sumTotal' => $sumQuery->func()->sum('Invoices.total'),
            'sumNetTotal' => $sumQuery->func()->sum('Invoices.net_total'),
        ])
            ->disableHydration()
            ->first();

        // add contain and order to original query
        $query
            ->select($clientFields)
            ->order($params['order']);

        $data = $this->paginate($query);

        $dateSpan = $this->Invoices->maxSpan($filter['counter']);

        $counters = [];
        $controller = $this;
        $counters = Cache::remember(
            'LilInvoices.sidebarCounters.' . $this->getCurrentUser()->id,
            function () use ($controller) {
                $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');

                return $controller->Authorization->applyScope($InvoicesCounters->find(), 'index')
                    ->where(['active' => true])
                    ->order(['active', 'kind DESC', 'title'])
                    ->all();
            },
            'Lil'
        );

        $projects = [];
        if (Plugin::isLoaded('LilProjects')) {
            /** @var \LilProjects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('LilProjects.Projects');
            $projectsQuery = $this->Authorization->applyScope($ProjectsTable->find(), 'index');
            $projects = $ProjectsTable->findForOwner($this->getCurrentUser()->id, $projectsQuery);
        }

        $this->set(compact('data', 'filter', 'counter', 'projects', 'dateSpan', 'invoicesTotals', 'counters'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $containTables = [
            'Issuers', 'Buyers', 'Receivers',
            'InvoicesCounters', 'InvoicesItems', 'InvoicesTaxes', 'InvoicesAttachments',
        ];
        if (Plugin::isLoaded('LilProjects')) {
            $containTables[] = 'Projects';
        }
        $invoice = $this->Invoices->get($id, ['contain' => $containTables]);

        $this->Authorization->authorize($invoice);

        /** @var \LilInvoices\Model\Table\InvoicesLinksTable $LinksTable */
        $LinksTable = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesLinks');
        $links = $LinksTable->forInvoice($id);

        $controller = $this;
        $counters = Cache::remember(
            'LilInvoices.sidebarCounters.' . $this->getCurrentUser()->id,
            function () use ($controller) {
                $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');

                return $controller->Authorization->applyScope($InvoicesCounters->find(), 'index')
                    ->where(['active' => true])
                    ->order(['active', 'kind DESC', 'title'])
                    ->all();
            },
            'Lil'
        );

        $currentCounter = $invoice->invoices_counter->id;

        $this->set(compact('invoice', 'counters', 'links', 'currentCounter'));

        return null;
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null
     */
    public function add()
    {
        if (!$this->getRequest()->getQuery('counter')) {
            throw new NotFoundException(__d('lil_invoices', 'Counter does not exist.'));
        }
        $this->setAction('edit');

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $invoice = $this->parseRequest($id);

        $this->Authorization->authorize($invoice);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            // patch invoice and existing sub elements
            $assocModels = ['InvoicesTaxes', 'InvoicesItems', 'InvoicesAttachments', 'Issuers', 'Buyers', 'Receivers'];
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

                // if there is no uploaded file, unset invoices_attachments
                $tmpName = $this->getRequest()->getData('invoices_attachments.0.filename.tmp_name');
                $scannedData = $this->getRequest()->getData('invoices_attachments.0.scanned');
                if (empty($tmpName) && empty($scannedData)) {
                    unset($invoice->invoices_attachments);
                }
                if (!empty($scannedData)) {
                    $tmpName = tempnam(constant('TMP'), 'LilScan') . '.pdf';
                    file_put_contents($tmpName, base64_decode($scannedData));
                }

                if ($this->Invoices->save($invoice, ['uploadedFilename' => $tmpName])) {
                    $conn->commit();
                    if ($this->getRequest()->is('ajax') || $this->getRequest()->is('lilScan')) {
                        $response = $this->getResponse()
                            ->withType('application/json')
                            ->withStringBody(json_encode(['invoice' => $invoice]));

                        return $response;
                    } else {
                        $this->Flash->success(__d('lil_invoices', 'The invoice has been saved.'));

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
                    $this->Flash->error(__d('lil_invoices', 'The invoice could not be saved. Please, try again.'));
                }
            }
        }

        $counter = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters')
            ->find()
            ->where(['InvoicesCounters.id' => $invoice->counter_id])
            ->first();

        $projects = [];
        if (Plugin::isLoaded('LilProjects')) {
            /** @var \LilProjects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('LilProjects.Projects');
            $projectsQuery = $this->Authorization->applyScope($ProjectsTable->find(), 'index');
            $projects = $ProjectsTable->findForOwner($this->getCurrentUser()->id, $projectsQuery);
        }

        /** @var \LilInvoices\Model\Table\VatsTable $VatsTable */
        $VatsTable = TableRegistry::getTableLocator()->get('LilInvoices.Vats');
        $vatLevels = $VatsTable->levels($this->getCurrentUser()->get('company_id'));

        $currentCounter = $invoice->counter_id;

        $this->set(compact('invoice', 'counter', 'vatLevels', 'projects', 'currentCounter'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $invoice = $this->Invoices->get($id);
        $this->Authorization->authorize($invoice);

        if ($this->Invoices->delete($invoice)) {
            $this->Flash->success(__d('lil_invoices', 'The invoice has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_invoices', 'The invoice could not be deleted. Please, try again.'));
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
     * Signs invoice after editing
     *
     * @param string $id Invoice id
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

            $signer = new LilInvoicesSigner($id);
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
                    $this->Flash->success(__d('lil_invoices', 'The invoice has been signed.'));

                    $redirectUrl = $this->getRequest()->getQuery('redirect');
                    if (!empty($redirectUrl)) {
                        return $this->redirect(base64_decode($redirectUrl));
                    } else {
                        return $this->redirect(['action' => 'view', $invoice->id]);
                    }
                } else {
                    $this->Flash->error(__d('lil_invoices', 'Signing invoice failed. Please, try again.'));
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
        $assocModels = ['InvoicesTaxes', 'InvoicesItems', 'InvoicesAttachments', 'Issuers', 'Buyers', 'Receivers'];
        $invoice = $this->Invoices->patchEntity($invoice, $this->getRequest()->getData(), [
            'associated' => $assocModels,
        ]);

        $this->Authorization->authorize($invoice, 'view');

        $Exporter = new LilInvoicesExport();
        $data = $Exporter->export('pdf', [$invoice]);

        if (!empty($data)) {
            return $Exporter->response('pdf', $data);
        }

        return null;
    }

    /**
     * Templates method
     *
     * @param string|null $id Invoice id.
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
                $this->Flash->success(__d('lil_invoices', 'The invoice has been saved.'));

                return $this->redirect(['action' => 'view', $invoice->id]);
            }
        }

        /** @var \LilInvoices\Model\Table\InvoicesTemplatesTable $InvoicesTemplatesTable */
        $InvoicesTemplatesTable = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesTemplates');
        $templates = $InvoicesTemplatesTable->findForOwner($this->getCurrentUser()->get('company_id'));
        $this->set(compact('invoice', 'templates'));

        return null;
    }

    /**
     * Send invoice via email
     *
     * @return \Cake\Http\Response|null
     */
    public function email()
    {
        $email = new EmailForm($this->getRequest());

        if ($this->getRequest()->is('post')) {
            if ($email->execute($this->getRequest()->getData())) {
                $this->Flash->success(__d('lil_invoices', 'The invoice(s) has been sent.'));

                return $this->redirect($this->getRequest()->getData('referer') ?? ['action' => 'index']);
            } else {
                $this->Flash->error(__d('lil_invoices', 'Please correct all marked fields below.'));
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
     * @param string|null $id Invoice id.
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
     * @param string|null $id Invoice id.
     * @param string|null $name Invoice slug.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function export($id = null, $name = null)
    {
        $filter = (array)$this->getRequest()->getQuery();
        if (!empty($id) && ($id !== 'invoices')) {
            $filter = array_merge($filter, ['id' => $id]);
        }

        $Exporter = new LilInvoicesExport();
        $invoices = $Exporter->find($filter);
        $this->Authorization->applyScope($invoices, 'index');
        $invoices = $invoices->toArray();

        $ext = $this->getRequest()->getParam('_ext');
        if (!empty($name) && substr($name, -4) == 'sepa') {
            $ext = 'sepa';
        }

        $data = $Exporter->export($ext, $invoices);
        if (!empty($data)) {
            $options = ['download' => $this->getRequest()->getQuery('download')];
            if (count($invoices) == 1) {
                $first = reset($invoices);
                $options['filename'] = Text::slug($first->title);
            }

            return $Exporter->response($ext, $data, $options);
        } else {
            $this->Flash->error(__d('lil_invoices', 'Error exporting to specified format.'));
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
                    'InvoicesCounters.active' => true,
                ],
                'contain' => ['InvoicesCounters']],
                $this->Invoices->filter($filter)
            );
            $data = $this->Authorization->applyScope($this->Invoices->find(), 'index')
                ->where($params['conditions'])
                ->contain($params['contain'])
                ->order(['InvoicesCounters.title', 'Invoices.no'])
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
                'InvoicesCounters.active' => true,
            ];

            $result = $this->Authorization->applyScope($this->Invoices->find(), 'index')
                ->where($conditions)
                ->contain('InvoicesCounters')
                ->order('Invoices.title')
                ->limit(30)
                ->all();

            $ret = [];
            foreach ($result as $i) {
                $ret[] = [
                    'label' => $i->title,
                    'value' => $i->id,
                    'no' => $i->no,
                    'counter' => $i->invoices_counter->title,
                ];
            }

            $response = $this->getResponse()
                ->withType('application/json')
                ->withStringBody(json_encode($ret));

            return $response;
        } else {
            throw new NotFoundException(__d('lil_invoices', 'Invalid request.'));
        }
    }

    /**
     * parseRequest method
     *
     * @param string|null $id Invoice id.
     * @return \LilInvoices\Model\Entity\Invoice
     */
    private function parseRequest($id = null)
    {
        if (!empty($id)) {
            $invoice = $this->Invoices->get($id, ['contain' => ['Issuers', 'Buyers', 'Receivers',
                'InvoicesTaxes', 'InvoicesItems', 'InvoicesCounters']]);
        } else {
            /** @var \LilInvoices\Model\Table\InvoicesClientsTable $InvoicesClients */
            $InvoicesClients = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesClients');
            /** @var \LilInvoices\Model\Table\InvoicesCountersTable $InvoicesCounters */
            $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');

            $sourceId = $this->getRequest()->getQuery('duplicate');
            if (!empty($sourceId)) {
                // clone
                $invoice = $this->Invoices->get($sourceId, ['contain' => ['Issuers', 'Buyers', 'Receivers',
                    'InvoicesTaxes', 'InvoicesItems', 'InvoicesCounters']]);

                $invoice->setNew(true);
                unset($invoice->id);

                foreach ($invoice->invoices_items as &$item) {
                    $item->setNew(true);
                    unset($item->id);
                    unset($item->invoice_id);
                }

                foreach ($invoice->invoices_taxes as &$tax) {
                    $tax->setNew(true);
                    unset($tax->id);
                    unset($tax->invoice_id);
                }

                $invoice->issuer->setNew(true);
                unset($invoice->issuer->id);
                unset($invoice->issuer->invoice_id);

                $invoice->buyer->setNew(true);
                unset($invoice->buyer->id);
                unset($invoice->buyer->invoice_id);

                $invoice->receiver->setNew(true);
                unset($invoice->receiver->id);
                unset($invoice->receiver->invoice_id);

                $counterId = $this->getRequest()->getQuery('counter', $invoice->counter_id);

                $invoice->invoices_counter = $InvoicesCounters->get($counterId);

                $invoice->counter_id = $invoice->invoices_counter->id;
                $invoice->doc_type = $invoice->invoices_counter->doc_type;
            } else {
                // new entity
                $invoice = $this->Invoices->newEmptyEntity();

                $invoice->owner_id = $this->getCurrentUser()->get('company_id');

                $invoice->issuer = $InvoicesClients->newEntity(['kind' => 'II']);
                $invoice->receiver = $InvoicesClients->newEntity(['kind' => 'IV']);
                $invoice->buyer = $InvoicesClients->newEntity(['kind' => 'BY']);

                $counterId = $this->getRequest()->getQuery('counter');
                if (empty($counterId)) {
                    $counterId = $this->getRequest()->getData('counter_id');
                }

                $invoice->invoices_counter = $InvoicesCounters->get($counterId);

                $invoice->counter_id = $invoice->invoices_counter->id;
                $invoice->doc_type = $invoice->invoices_counter->doc_type;

                switch ($invoice->invoices_counter->kind) {
                    case 'issued':
                        $invoice->issuer->patchWithAuth($this->getCurrentUser());
                        break;
                    case 'received':
                        $invoice->receiver->patchWithAuth($this->getCurrentUser());
                        $invoice->buyer->patchWithAuth($this->getCurrentUser());
                        break;
                }
            }

            $invoice->no = $InvoicesCounters->generateNo($invoice->counter_id);
        }

        return $invoice;
    }
}
