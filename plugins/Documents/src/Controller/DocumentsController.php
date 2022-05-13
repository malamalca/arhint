<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Documents Controller
 *
 * @property \Documents\Model\Table\DocumentsTable $Documents
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 */
class DocumentsController extends BaseDocumentsController
{
    /**
     * @var string $documentsScope
     */
    public $documentsScope = 'Documents';

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
                    ['receiver', 'issuer']
                );
            }
        }

        return null;
    }

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

        // fetch documents
        $filter = [];
        $filter = array_merge($filter, $this->getRequest()->getQuery());
        $filter['counter'] = $counter->id;
        $filter['order'] = 'Documents.counter DESC';
        $params = $this->Documents->filter($filter);

        $query = $this->Authorization->applyScope($this->Documents->find())
            ->select(['id', 'no', 'dat_issue', 'title', 'descript', 'project_id', 'attachments_count', 'client.title'])
            ->join([
                'table' => 'documents_clients',
                'alias' => 'Client',
                'type' => 'INNER',
                'conditions' => [
                    'Client.document_id = Documents.id',
                    'Client.kind' => $counter->direction == 'received' ? 'II' : 'IV',
                ],
            ])
            ->where($params['conditions'])
            ->order($params['order']);

        $data = $this->paginate($query);

        $dateSpan = $this->Documents->maxSpan($filter['counter']);

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

        $this->set(compact('data', 'dateSpan', 'filter', 'projects'));

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

        $params = $this->Documents->filter($filter);

        $query = $this->Authorization->applyScope($this->Documents->find(), 'index')
            ->select(['id', 'no', 'counter', 'counter_id', 'dat_issue', 'title', 'project_id',
                'attachments_count'])
            ->where($params['conditions'])
            ->order($params['order']);

        $data = $this->paginate($query, ['limit' => 5]);

        $this->set(compact('data', 'sourceRequest'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $containTables = ['DocumentsCounters', 'DocumentsAttachments', 'DocumentsLinks', 'Issuers', 'Receivers'];
        if (Plugin::isLoaded('Projects')) {
            $containTables[] = 'Projects';
        }

        parent::view($id, $containTables);
    }

    /**
     * Edit method
     *
     * @param string|null $id Document id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $containTables = ['Issuers', 'Receivers', 'DocumentsAttachments'];

        $document = $this->Documents->parseRequest($this->getRequest(), $id);

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

        return parent::edit($document, $containTables);
    }
}
