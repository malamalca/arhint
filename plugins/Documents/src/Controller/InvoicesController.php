<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Documents\Lib\DocumentsUpnQr;
use Documents\Lib\InvoicesExport;

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
    public $documentsScope = 'Invoices';

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
        /** @var \Documents\Model\Entity\DocumentsCounter|\Cake\Http\Response $counter */
        $counter = parent::index();

        if ($counter instanceof \Cake\Http\Response) {
            return $counter;
        }

        // fetch invoices
        $filter = (array)$this->getRequest()->getQuery();
        $filter['counter'] = $counter->id;
        $filter['order'] = 'Invoices.counter DESC';
        $params = $this->Invoices->filter($filter);

        $query = $this->Authorization->applyScope($this->Invoices->find())
            ->select(['id', 'no', 'counter', 'counter_id', 'dat_issue', 'title', 'net_total', 'total', 'project_id',
                'attachments_count', 'client.title'])
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
        $request = new \Cake\Http\ServerRequest(['url' => $this->getRequest()->getQuery('source')]);  
        $sourceRequest = Router::parseRequest($request);

        $filter = [];
        switch ($sourceRequest['plugin']) {
            case 'Projects':
                $filter['project'] = $sourceRequest['pass'][0] ?? null;
                break;
            case 'Crm':
                $filter['contact_id'] = $sourceRequest['pass'][0] ?? null;
                break;
        }

        $params = $this->Invoices->filter($filter);

        $query = $this->Authorization->applyScope($this->Invoices->find(), 'index')
            ->select(['id', 'no', 'counter', 'counter_id', 'dat_issue', 'title', 'net_total', 'total', 'project_id',
                'attachments_count'])
            ->where($params['conditions'])
            ->order($params['order']);

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

        $this->set(compact('data', 'invoicesTotals', 'sourceRequest'));

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

        return parent::view($id, $containTables);
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
        $containTables = [
            'Issuers', 'Buyers', 'Receivers',
            'InvoicesItems', 'InvoicesTaxes', 'DocumentsAttachments',
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
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function editPreview()
    {
        $invoice = $this->Invoices->parseRequest($this->getRequest(), $this->getRequest()->getData('id'));
        $assocModels = ['InvoicesTaxes', 'InvoicesItems', 'DocumentsAttachments', 'Issuers', 'Buyers', 'Receivers'];

        return parent::editPreview($invoice, $assocModels);
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
     * Upnqr method
     *
     * @param string $kind XML kind as of eslog20, eslog, sepa,..
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function validate($kind, $id)
    {
        if (!in_array($kind, ['sepa', 'eslog', 'eslog20'])) {
            die('Invalid extension!');
        }
        $filter = ['id' => $id];

        $Exporter = new InvoicesExport();

        $documents = $Exporter->find($filter);
        $this->Authorization->applyScope($documents, 'index');

        $documents = $documents->toArray();
        $data = $Exporter->export($kind, $documents);

        $xml = new \DOMDocument();
        $xml->loadXml($data);

        switch ($kind) {
            case 'sepa':
                $xsd = Plugin::path('Documents') . 'webroot' . DS . 'schema' . DS . 'pain.001.001.03.xsd';
                break;
            case 'eslog':
                $xsd = Plugin::path('Documents') . 'webroot' . DS . 'schema' . DS . 'eSLOG_1-6_EnostavniRacun.xsd';
                break;
            case 'eslog20':
                $xsd = Plugin::path('Documents') . 'webroot' . DS . 'schema' . DS . 'eSLOG20_INVOIC_v200.xsd';
                break;
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
}
