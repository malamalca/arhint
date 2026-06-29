<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Documents\Form\EslogImportForm;
use Documents\Form\PdfImportForm;
use Documents\Lib\InvoicesExport;
use Documents\Lib\InvoicesExportEracuni;
use Documents\Model\Entity\Invoice;
use Documents\Model\Entity\Vat;
use DOMDocument;

/**
 * Invoices Controller
 *
 * @property \Documents\Model\Table\InvoicesTable $Invoices
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 */
class InvoicesController extends BaseDocumentsController
{
    /**
     * @var string $documentsScope
     */
    public string $documentsScope = 'Invoices';

    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if (in_array($this->getRequest()->getParam('action'), ['edit', 'editPreview'])) {
            $this->FormProtection->setConfig(
                'unlockedFields',
                ['invoices_taxes', 'invoices_items', 'receiver', 'buyer', 'issuer'],
            );
        }

        if (in_array($this->getRequest()->getParam('action'), ['importEslog', 'importPdf'])) {
            $this->FormProtection->setConfig('validatePost', false);
            // Skip authorization - no entity to authorize, user just needs to be logged in
            $this->Authorization->skipAuthorization();
        }
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        /** @var \Documents\Model\Entity\DocumentsCounter|\Cake\Http\Response $counter */
        $counter = parent::index();

        if ($counter instanceof Response) {
            return $counter;
        }

        // fetch invoices
        $filter = (array)$this->getRequest()->getQuery();
        $filter['counter'] = $counter->id;
        $filter['order'] = 'Invoices.counter DESC';
        $params = $this->Invoices->filter($filter);

        $query = $this->Authorization->applyScope($this->Invoices->find())
            ->select(['id', 'no', 'counter', 'counter_id', 'dat_issue', 'title', 'net_total', 'total', 'project_id',
                'attachments_count', 'Client.title'])
            ->join([
                'table' => 'documents_clients',
                'alias' => 'Client',
                'type' => 'INNER',
                'conditions' => [
                    'Client.document_id = Invoices.id',
                    'Client.kind' => $counter->direction == 'received' ? 'II' : 'IV',
                ],
            ])
            ->where($params['conditions'])
            ->orderBy($params['order']);

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
                    'Client.kind' => $counter->direction == 'received' ? 'II' : 'IV',
                ],
            ])
            ->where($params['conditions'])
            ->disableHydration()
            ->first();

        $dateSpan = $this->Invoices->maxSpan($filter['counter']);

        $projects = [];
        if (Plugin::isLoaded('Projects')) {
            /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

            $projectsIds = array_filter(array_unique($query->all()->extract('project_id')->toList()));

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

        $this->set(compact('data', 'filter', 'dateSpan', 'invoicesTotals', 'projects'));

        return null;
    }

    /**
     * List method
     *
     * @return \Cake\Http\Response|null
     */
    public function list(): ?Response
    {
        $request = new ServerRequest(['url' => $this->getRequest()->getQuery('source')]);
        $sourceRequest = Router::parseRequest($request);

        $filter = [];
        $filter['order'] = $this->getRequest()->getQuery('sort') ? null : 'dat_issue DESC';
        switch ($sourceRequest['plugin']) {
            case 'Projects':
                $filter['project'] = $sourceRequest['pass'][0] ?? null;
                break;
            case 'Crm':
                $filter['contact_id'] = $sourceRequest['pass'][0] ?? null;
                break;
        }
        if (isset($sourceRequest['?']['counter'])) {
            $filter['counter'] = $sourceRequest['?']['counter'];
        }

        $sourceRequest = array_merge($sourceRequest, $sourceRequest['pass']);
        unset($sourceRequest['_matchedRoute']);
        unset($sourceRequest['_route']);
        unset($sourceRequest['pass']);

        $params = $this->Invoices->filter($filter);

        $query = $this->Authorization->applyScope($this->Invoices->find(), 'index')
            ->select(['id', 'no', 'counter', 'counter_id', 'dat_issue', 'title', 'net_total', 'total', 'project_id',
                'attachments_count'])
            ->where($params['conditions'])
            ->orderBy($params['order']);

        $data = $this->paginate($query, ['limit' => 5]);

        $sumQuery = $this->Authorization->applyScope($this->Invoices->find(), 'index');
        $invoicesTotals = $sumQuery
            ->select([
                'sumTotal' => $sumQuery->func()->sum('Invoices.total'),
                'sumNetTotal' => $sumQuery->func()->sum('Invoices.net_total'),
            ])
            ->where($params['conditions'])
            ->disableHydration()
            ->first();

        $subQueryCounters = $this->Authorization->applyScope($this->Invoices->find(), 'index')
            ->select(['counter_id'])
            ->distinct(['counter_id'])
            ->where($params['conditions']);

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCountersTable */
        $DocumentsCountersTable = $this->fetchTable('Documents.DocumentsCounters');
        $counters = $DocumentsCountersTable->find()
            ->where(['id IN' => $subQueryCounters])
            ->orderBy(['title' => 'ASC'])
            ->all()
            ->combine('id', fn($entity) => $entity)
            ->toArray();

        $this->set(compact('data', 'invoicesTotals', 'sourceRequest', 'counters'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $containTables = [
            'Issuers', 'Buyers', 'Receivers', 'Logs' => ['Users'],
            'DocumentsCounters', 'InvoicesItems', 'InvoicesTaxes', 'Attachments',
        ];
        if (Plugin::isLoaded('Projects')) {
            $containTables[] = 'Projects';
        }

        return parent::view($id, $containTables);
    }

    /**
     * Edit method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $containTables = [
            'Issuers', 'Buyers', 'Receivers',
            'InvoicesItems', 'InvoicesTaxes', 'Attachments',
        ];

        $document = $this->Invoices->parseRequest($this->getRequest(), $id);

        // for sidebar
        $this->set('currentCounter', $document->documents_counter->id);

        // Check if data was imported from eSlog XML
        $importFromEslog = (bool)$this->getRequest()->getQuery('importFromEslog');
        if ($importFromEslog && $document->isNew()) {
            /** @var array<string, mixed>|null $importData */
            $importData = $this->getRequest()->getSession()->consume('ImportEslogData');
            if (!empty($importData)) {
                $document = $this->_applyEslogImportData($document, $importData);
            }
        }

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

        $this->set(compact('vatLevels', 'projects'));

        return parent::edit($document, $containTables);
    }

    /**
     * Apply parsed eSlog import data to a new invoice entity.
     *
     * @param \Documents\Model\Entity\Invoice $document Invoice entity.
     * @param array<string, mixed> $importData Parsed eSlog data.
     * @return \Documents\Model\Entity\Invoice
     */
    private function _applyEslogImportData(
        Invoice $document,
        array $importData,
    ): Invoice {
        // Apply invoice header fields
        $invoiceData = $importData['invoice'] ?? [];

        if (!empty($invoiceData['no'])) {
            $document->no = $invoiceData['no'];
        }
        if (!empty($invoiceData['dat_issue'])) {
            $parsedDate = Date::parseDate($invoiceData['dat_issue'], 'yyyy-MM-dd');
            if ($parsedDate) {
                $document->dat_issue = $parsedDate;
                // Fall back to the issue date when no explicit service date was parsed
                $document->dat_service = $parsedDate;
            }
        }
        if (!empty($invoiceData['dat_service'])) {
            $serviceDate = Date::parseDate($invoiceData['dat_service'], 'yyyy-MM-dd');
            if ($serviceDate) {
                $document->dat_service = $serviceDate;
            }
        }
        if (!empty($invoiceData['dat_expire'])) {
            $expireDate = Date::parseDate($invoiceData['dat_expire'], 'yyyy-MM-dd');
            if ($expireDate) {
                $document->dat_expire = $expireDate;
            }
        }
        if (!empty($invoiceData['pmt_type'])) {
            $document->pmt_type = $invoiceData['pmt_type'];
        }
        if (!empty($invoiceData['pmt_module'])) {
            $document->pmt_module = $invoiceData['pmt_module'];
        }
        if (!empty($invoiceData['pmt_ref'])) {
            $document->pmt_ref = $invoiceData['pmt_ref'];
        }

        // Apply client data using newEntity to create proper DocumentsClient entities
        /** @var \Documents\Model\Table\DocumentsClientsTable $DocumentsClients */
        $DocumentsClients = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');

        // Apply issuer data (seller)
        $issuerData = $importData['issuer'] ?? [];
        if (!empty($issuerData)) {
            $document->issuer = $DocumentsClients->newEntity(array_merge($issuerData, ['kind' => 'II']));
        }

        // Apply receiver/buyer data. In the eSlog XML the seller (issuer) is the
        // external party and the buyer/invoicee (receiver) is "us"; that mapping
        // holds regardless of the counter direction. The issuer above is filled
        // from the seller, the receiver/buyer below from the invoicee.
        $receiverData = $importData['receiver'] ?? $importData['buyer'] ?? [];
        if (!empty($receiverData)) {
            $document->receiver = $DocumentsClients->newEntity(array_merge($receiverData, ['kind' => 'IV']));
        }
        $buyerData = $importData['buyer'] ?? $importData['receiver'] ?? [];
        if (!empty($buyerData)) {
            $document->buyer = $DocumentsClients->newEntity(array_merge($buyerData, ['kind' => 'BY']));
        }

        $counterDirection = $document->documents_counter->direction ?? 'issued';

        // Apply line items
        $items = $importData['items'] ?? [];

        /** @var \Documents\Model\Table\VatsTable $VatsTable */
        $VatsTable = TableRegistry::getTableLocator()->get('Documents.Vats');
        $vatLevels = $VatsTable->levels($this->getCurrentUser()->get('company_id'));

        if (!empty($items)) {
            /** @var \Documents\Model\Table\InvoicesItemsTable $InvoicesItems */
            $InvoicesItems = TableRegistry::getTableLocator()->get('Documents.InvoicesItems');
            /** @var \Documents\Model\Table\InvoicesTaxesTable $InvoicesTaxes */
            $InvoicesTaxes = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxes');

            $invoiceItems = [];
            $taxGroups = [];
            $netTotal = 0;
            $totalWithVat = 0;

            foreach ($items as $itemData) {
                $vatPercent = (float)($itemData['vat_percent'] ?? 0);
                $matchedVat = $this->_findVatByPercent($vatLevels, $vatPercent);

                $qty = (float)($itemData['qty'] ?? 1);
                $price = (float)($itemData['price'] ?? 0);
                $discount = (float)($itemData['discount'] ?? 0);
                $vatId = $matchedVat?->id;
                $vatTitle = $matchedVat ? $matchedVat->descript : '';

                // Marshal as a proper entity so the edit template can read it as an object
                $invoiceItems[] = $InvoicesItems->newEntity([
                    'descript' => $itemData['descript'] ?? '',
                    'qty' => $qty,
                    'unit' => $itemData['unit'] ?? 'pcs',
                    'price' => $price,
                    'discount' => $discount,
                    'vat_id' => $vatId,
                    'vat_title' => $vatTitle,
                    'vat_percent' => $vatPercent,
                ]);

                $itemNet = round($qty * $price, 2);
                if ($discount > 0) {
                    $itemNet = round($itemNet * (1 - $discount / 100), 2);
                }
                $netTotal += $itemNet;
                $totalWithVat += round($itemNet * (1 + $vatPercent / 100), 2);

                // Group net amounts by VAT rate for the tax breakdown
                if (!isset($taxGroups[(string)$vatPercent])) {
                    $taxGroups[(string)$vatPercent] = [
                        'vat_percent' => $vatPercent,
                        'vat_title' => $vatTitle,
                        'vat_id' => $vatId,
                        'base' => 0,
                    ];
                }
                $taxGroups[(string)$vatPercent]['base'] = round(
                    $taxGroups[(string)$vatPercent]['base'] + $itemNet,
                    2,
                );
            }

            $document->invoices_items = $invoiceItems;

            $invoicesTaxes = [];
            foreach ($taxGroups as $group) {
                $invoicesTaxes[] = $InvoicesTaxes->newEntity($group);
            }
            $document->invoices_taxes = $invoicesTaxes;

            $document->net_total = round($netTotal, 2);
            $document->total = round($totalWithVat, 2);
        }

        // For received invoices with taxes section instead of items
        if ($counterDirection === 'received' && empty($items)) {
            $totalData = $importData['invoice'] ?? [];
            if (!empty($totalData['total'])) {
                $document->total = (float)$totalData['total'];
            }
            if (!empty($totalData['net_total'])) {
                $document->net_total = (float)$totalData['net_total'];
            }
        }

        return $document;
    }

    /**
     * Find a VAT level entity matching the given percentage.
     *
     * @param iterable<\Documents\Model\Entity\Vat> $vatLevels Available VAT levels.
     * @param float $percent The VAT percentage to match.
     * @return \Documents\Model\Entity\Vat|null
     */
    private function _findVatByPercent(iterable $vatLevels, float $percent): ?Vat
    {
        foreach ($vatLevels as $vat) {
            if (abs((float)$vat->percent - $percent) < 0.01) {
                return $vat;
            }
        }

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
        $invoice = $this->Invoices->parseRequest($this->getRequest(), $this->getRequest()->getData('id'));
        $assocModels = ['InvoicesTaxes', 'InvoicesItems', 'Attachments', 'Issuers', 'Buyers', 'Receivers'];

        return parent::editPreview([$invoice, $assocModels]);
    }

    /**
     * Import eSlog 2.0 XML invoice.
     *
     * GET: Show upload form.
     * POST: Parse uploaded XML, validate, and redirect to edit() with prefilled data.
     *
     * @return \Cake\Http\Response|null
     */
    public function importEslog(): ?Response
    {
        $counterId = (string)$this->getRequest()->getQuery('counter');
        if (empty($counterId)) {
            $this->Flash->error(__d('documents', 'Counter ID is required.'));

            return $this->redirect(['action' => 'index']);
        }

        /** @var \Documents\Form\EslogImportForm $form */
        $form = new EslogImportForm($this->getRequest());

        if ($this->getRequest()->is('post')) {
            // Add counter_id to data for form execution
            $data = $this->getRequest()->getData();
            $data['counter_id'] = $counterId;

            if ($form->execute($data)) {
                if (!$form->clientExists) {
                    // Show prompt to create new client
                    $this->set('counterId', $counterId);
                    $this->set('missingClientTaxNo', $form->missingClientInfo['tax_no'] ?? '');
                    $this->set('missingClientTitle', $form->missingClientInfo['title'] ?? '');
                    $this->viewBuilder()->setTemplate('import_eslog_new_client');

                    return null;
                }

                // Redirect to edit with prefilled data
                $this->Flash->success(__d('documents', 'eSlog XML imported successfully.'));

                return $this->redirect([
                    'action' => 'edit',
                    '?' => ['counter' => $counterId, 'importFromEslog' => '1'],
                ]);
            }

            // Validation failed - show form with errors
        }

        $this->set('counterId', $counterId);
        $this->set('form', $form);

        return null;
    }

    /**
     * Import an invoice from a PDF file using AI.
     *
     * GET: Show upload form.
     * POST: Send the PDF to the configured AI provider, which converts it to eSlog 2.0 XML,
     * then process that XML exactly like an uploaded eSlog file (prefill the invoice edit form).
     *
     * @return \Cake\Http\Response|null
     */
    public function importPdf(): ?Response
    {
        $counterId = (string)$this->getRequest()->getQuery('counter');
        if (empty($counterId)) {
            $this->Flash->error(__d('documents', 'Counter ID is required.'));

            return $this->redirect(['action' => 'index']);
        }

        $form = new PdfImportForm($this->getRequest());

        if ($this->getRequest()->is('post')) {
            $data = $this->getRequest()->getData();
            $data['counter_id'] = $counterId;

            if ($form->execute($data)) {
                if (!$form->clientExists) {
                    // Show prompt to create new client
                    $this->set('counterId', $counterId);
                    $this->set('missingClientTaxNo', $form->missingClientInfo['tax_no'] ?? '');
                    $this->set('missingClientTitle', $form->missingClientInfo['title'] ?? '');
                    $this->viewBuilder()->setTemplate('import_eslog_new_client');

                    return null;
                }

                // Redirect to edit with prefilled data (same hand-off as the eSlog XML import)
                $this->Flash->success(__d('documents', 'Invoice imported from PDF successfully.'));

                return $this->redirect([
                    'action' => 'edit',
                    '?' => ['counter' => $counterId, 'importFromEslog' => '1'],
                ]);
            }

            // Surface AI/extraction failures to the user (validation errors render inline).
            if ($form->aiError !== null) {
                $this->Flash->error($form->aiError);
            }
        }

        $this->set('counterId', $counterId);
        $this->set('form', $form);

        return null;
    }

    /**
     * Validate method
     *
     * @param string|null $id Document id.
     * @param string $kind XML kind as of eslog20, eslog, sepa,..
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function validate(?string $id, string $kind = 'sepa'): ?Response
    {
        if (!in_array($kind, ['sepa', 'eslog', 'eslog20'])) {
            die('Invalid extension!');
        }
        $filter = ['id' => $id];

        $Exporter = new InvoicesExport();

        $invoice = $Exporter->find($filter)->first();
        $this->Authorization->authorize($invoice, 'view');

        $data = $Exporter->export($kind, [$invoice]);

        $xml = new DOMDocument();
        $xml->loadXml($data);

        switch ($kind) {
            case 'sepa':
                $xsd = Plugin::path('Documents') . 'webroot' . DS . 'schema' . DS . 'pain.001.001.03.xsd';
                break;
            case 'eslog':
                $xsd = Plugin::path('Documents') . 'webroot' . DS . 'schema' . DS . 'eSLOG_1-6_EnostavniRacun.xsd';
                break;
            //case 'eslog20':
            default:
                $xsd = Plugin::path('Documents') . 'webroot' . DS . 'schema' . DS . 'eSLOG20_INVOIC_v200.xsd';
        }

        $errors = [];

        libxml_use_internal_errors(true);

        if (!$xml->schemaValidate($xsd)) {
            $errors = libxml_get_errors();

            libxml_clear_errors();
        }

        $this->set(compact('errors', 'id'));

        return null;
    }

    /**
     * report method
     *
     * @return \Cake\Http\Response|void
     */
    public function exportEracuni()
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
                'contain' => ['DocumentsCounters', 'InvoicesItems', 'InvoicesTaxes']],
                $this->{$this->documentsScope}->filter($filter),
            );
            $data = $this->Authorization->applyScope($this->{$this->documentsScope}->find(), 'index')
                ->where($params['conditions'])
                ->contain($params['contain'])
                ->orderBy(['DocumentsCounters.title', 'Invoices.no'])
                ->all();

            $report = new InvoicesExportEracuni();
            $report->export($data);
        }

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counters = $DocumentsCounters->rememberForUser(
            $this->getCurrentUser()->id,
            $this->Authorization->applyScope($DocumentsCounters->find(), 'index'),
            $this->documentsScope,
        )->combine('id', 'title');

        $this->set(compact('counters'));
    }
}
