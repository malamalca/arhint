<?php
declare(strict_types=1);

namespace LilInvoices\Controller;

use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use LilInvoices\Form\EmailForm;
use LilInvoices\Lib\LilInvoicesExport;

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
                $this->Security->setConfig('unlockedFields', ['invoices_taxes', 'invoices_items']);
            }

            if (in_array($this->getRequest()->getParam('action'), ['editPreview'])) {
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
        $params = $this->Invoices->filter($filter);

        $query = $this->Authorization->applyScope($this->Invoices->find())
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->order($params['order']);

        $data = $this->paginate($query);

        $dateSpan = $this->Invoices->maxSpan($filter['counter']);

        $this->set(compact('data', 'filter', 'counter', 'dateSpan'));

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
        $invoice = $this->Invoices->get($id, [
            'contain' => [
                'Issuers', 'Buyers', 'Receivers',
                'InvoicesCounters', 'InvoicesItems', 'InvoicesTaxes', 'InvoicesAttachments',
            ],
        ]);

        $this->Authorization->authorize($invoice);

        /** @var \LilInvoices\Model\Table\InvoicesLinksTable $LinksTable */
        $LinksTable = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesLinks');
        $links = $LinksTable->forInvoice($id);

        $counters = TableRegistry::get('LilInvoices.InvoicesCounters')
            ->find()
            ->where(['owner_id' => $this->getCurrentUser()->get('company_id'), 'active' => true])
            ->all();

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
            if (!$invoice->getErrors()) {
                // commit/rollback session
                $conn = $this->Invoices->getConnection();
                $conn->begin();

                if ($invoice->isNew()) {
                    $invoice->getNextCounterNo();
                }

                // file upload
                $tmpName = $this->getRequest()->getData('invoices_attachments.0.filename.tmp_name');
                if (empty($tmpName)) {
                    unset($invoice->invoices_attachments);
                }

                $saveOptions = ['uploadedFilename' => $tmpName];

                if ($this->Invoices->save($invoice, $saveOptions)) {
                    $conn->commit();
                    if (!$this->getRequest()->is('ajax')) {
                        $this->Flash->success(__d('lil_invoices', 'The invoice has been saved.'));

                        return $this->redirect(['action' => 'view', $invoice->id]);
                    } else {
                        $response = $this->getResponse()
                            ->withType('application/json')
                            ->withStringBody(json_encode(['invoice' => $invoice]));

                        return $response;
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

        $counter = TableRegistry::get('LilInvoices.InvoicesCounters')
            ->find()
            ->where(['InvoicesCounters.id' => $invoice->counter_id])
            ->first();

        /** @var \LilInvoices\Model\Table\VatsTable $VatsTable */
        $VatsTable = TableRegistry::getTableLocator()->get('LilInvoices.Vats');
        $vatLevels = $VatsTable->levels($this->getCurrentUser()->get('company_id'));

        $this->set(compact('invoice', 'counter', 'vatLevels'));

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
     * @return \Cake\Http\Response|null
     */
    public function sign()
    {
        if (!$this->getRequest()->is(['patch', 'post', 'put'])) {
            throw new NotFoundException(__d('lil_invoices', 'No data to sign.'));
        }

        $invoice = $this->parseRequest();

        $this->Authorization->authorize($invoice);

        $Exporter = new LilInvoicesExport();
        $xml = $Exporter->export('html', [$invoice]);

        $this->set('xml', $xml);

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

        $filter = (array)$this->getRequest()->getQuery(null, []);

        $params = array_merge_recursive(
            ['conditions' => [
                'Invoices.owner_id' => $this->getCurrentUser()->get('company_id')],
            ],
            $this->Invoices->filter($filter)
        );

        $selectedInvoices = $this->getRequest()->getQuery('invoices');
        if (!empty($selectedInvoices)) {
            $params['conditions'] = ['Invoices.id IN' => (array)$selectedInvoices];
        }

        $attachments = $this->Invoices
            ->find('list')
            ->where($params['conditions'])
            ->contain($params['contain'])
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
        $this->set(compact('id', 'name'));

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

        $Exporter = new LilInvoicesExport();
        $invoices = $Exporter->find(array_merge($filter, ['id' => $id]));
        $this->Authorization->applyScope($invoices, 'index');
        $invoices = $invoices->toArray();

        $ext = $this->getRequest()->getParam('_ext');
        if (substr($name, -4) == 'sepa') {
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
            $this->redirect(['action' => 'view', $id]);
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

                $invoice->isNew(true);
                unset($invoice->id);

                foreach ($invoice->invoices_items as &$item) {
                    $item->isNew(true);
                    unset($item->id);
                    unset($item->invoice_id);
                }

                foreach ($invoice->invoices_taxes as &$tax) {
                    $tax->isNew(true);
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

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $presentItemsBefore = [];
            if (!empty($invoice->invoices_items)) {
                $presentItemsBefore = (new Collection($invoice->invoices_items))->extract('id')->toArray();
            }

            $presentTaxesBefore = [];
            if (!empty($invoice->invoices_taxes)) {
                $presentTaxesBefore = (new Collection($invoice->invoices_taxes))->extract('id')->toArray();
            }

            // patch invoice and existing sub elements
            $assocModels = ['InvoicesTaxes', 'InvoicesItems', 'InvoicesAttachments', 'Issuers', 'Buyers', 'Receivers'];
            $invoice = $this->Invoices->patchEntity($invoice, $this->getRequest()->getData(), [
                'associated' => $assocModels,
            ]);

            // always recalculate net total sum
            $invoice->net_total = 0;

            ////////////////////////////////////////////////////////////////////////////////////////////
            // create new entities or extract entities that are still valid
            $presentTaxesAfter = [];
            $presentTaxesAfter = (new Collection((array)$invoice->invoices_taxes))->extract('id')->toArray();

            // create delete list
            $deleteTaxesList = array_diff($presentTaxesBefore, $presentTaxesAfter);
            $invoice->deleteTaxesList = $deleteTaxesList;

            // remove entities which were deleted in form
            if (!empty($invoice->invoices_taxes)) {
                foreach ($invoice->invoices_taxes as $i => $tax) {
                    if (in_array($tax->id, $deleteTaxesList)) {
                        unset($invoice->invoices_taxes[$i]);
                    } else {
                        $invoice->net_total += $tax->base;
                    }
                }
            }

            $invoice->setDirty('invoices_taxes', true);

            ////////////////////////////////////////////////////////////////////////////////////////////
            // create new entities or extract entities that are still valid
            $presentItemsAfter = [];
            $presentItemsAfter = (new Collection((array)$invoice->invoices_items))->extract('id')->toArray();

            // create delete list
            $deleteItemsList = array_diff($presentItemsBefore, $presentItemsAfter);
            $invoice->deleteItemsList = $deleteItemsList;

            // remove entities which were deleted in form
            // calculate total only when there are items
            if (!empty($invoice->invoices_items)) {
                $invoice->total = 0;
                $invoice->net_total = 0;
                foreach ($invoice->invoices_items as $i => $item) {
                    if (in_array($item->id, $deleteItemsList)) {
                        unset($invoice->invoices_items[$i]);
                    } else {
                        $invoice->net_total += $item->net_total;
                        $invoice->total += $item->total;
                    }
                }

                $invoice->setDirty('invoices_items', true);
            }
        }

        return $invoice;
    }
}
