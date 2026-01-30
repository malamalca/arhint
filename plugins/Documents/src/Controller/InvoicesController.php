<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Documents\Lib\DocumentsUpnQr;
use Documents\Lib\InvoicesExport;
use Documents\Lib\InvoicesExportEracuni;
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
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (in_array($this->getRequest()->getParam('action'), ['edit', 'editPreview'])) {
            $this->FormProtection->setConfig(
                'unlockedFields',
                ['invoices_taxes', 'invoices_items', 'receiver', 'buyer', 'issuer'],
            );
        }
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
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
    public function list()
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
     * Upnqr method
     *
     * @param string $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function upnqr(string $id)
    {
        $invoice = $this->Invoices->get($id);
        $this->Authorization->authorize($invoice, 'view');

        $imageData = DocumentsUpnQr::generateUpnQr($id);

        $response = $this->response;
        $response = $response->withStringBody($imageData);
        $response = $response->withType('gif');

        return $response;
    }

    /**
     * Upnqr method
     *
     * @param string|null $id Document id.
     * @param string $kind XML kind as of eslog20, eslog, sepa,..
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function validate(?string $id, string $kind = 'sepa')
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
