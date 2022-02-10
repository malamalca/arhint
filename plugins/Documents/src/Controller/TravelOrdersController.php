<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;

/**
 * TravelOrders Controller
 *
 * @property \Documents\Model\Table\TravelOrdersTable $TravelOrders
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 */
class TravelOrdersController extends BaseDocumentsController
{
    /**
     * @var string $documentsScope
     */
    public $documentsScope = 'TravelOrders';

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        /** @var \Documents\Model\Entity\DocumentsCounter|\Cake\Http\Response $counter */
        $counter = parent::index();

        if ($counter instanceof \Cake\Http\Response) {
            return $counter;
        }

        // fetch travel orders
        $filter['counter'] = $counter->id;
        $filter['order'] = 'TravelOrders.counter DESC';
        $params = $this->TravelOrders->filter($filter);

        $query = $this->Authorization->applyScope($this->TravelOrders->find())
            ->select(['id', 'no', 'counter', 'dat_order', 'location', 'total', 'attachments_count'])
            ->where($params['conditions'])
            ->order($params['order']);

        $data = $this->paginate($query);

        $dateSpan = $this->TravelOrders->maxSpan($filter['counter']);

        $this->set(compact('data', 'dateSpan'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Travel Order id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $containTables = ['DocumentsCounters'];
        if (Plugin::isLoaded('Projects')) {
            $containTables[] = 'Projects';
        }

        parent::view($id, $containTables);
    }

    /**
     * Edit method
     *
     * @param string|null $id Travel Order id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $containTables = [];

        $document = $this->TravelOrders->parseRequest($this->getRequest(), $id);

        parent::edit($document, $containTables);

        // for sidebar
        $this->set('currentCounter', $document->documents_counter->id);

        $projects = [];
        if (Plugin::isLoaded('Projects')) {
            /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
            $projectsQuery = $this->Authorization->applyScope($ProjectsTable->find(), 'index');
            $projects = $ProjectsTable->findForOwner($this->getCurrentUser()->company_id, $projectsQuery);
        }

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));

        $this->set(compact('projects', 'users'));

        return null;
    }
}
