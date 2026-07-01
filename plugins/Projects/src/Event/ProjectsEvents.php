<?php
declare(strict_types=1);

namespace Projects\Event;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Projects\Lib\ProjectsSidebar;
use Throwable;

class ProjectsEvents implements EventListenerInterface
{
    /**
     * Return implemented events array.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'View.beforeRender' => 'addScripts',
            'App.Sidebar.beforeRender' => 'modifySidebar',
            'Documents.Dashboard.queryDocuments' => 'filterDashboardDocuments',
            'Documents.Documents.indexQuery' => 'filterDashboardDocuments',
            'App.Form.Crm.Adremas.edit' => 'addProjectToAdremas',
            'App.Index.Crm.Adremas.index' => 'addProjectToAdremas',
            'Model.afterSave' => 'logProjectDocument',
        ];
    }

    /**
     * Create a project log entry when a document or invoice is saved to a project.
     *
     * Fired on every Model.afterSave; only acts when the saved entity is a Document
     * or Invoice whose project_id changed in this save (i.e. it was just assigned or
     * reassigned to a project). The log
     * is stored as a project comment (model='Project', action='Comment') so
     * it shows up in the project's logs tab, with a link to the document/invoice.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Cake\Datasource\EntityInterface $entity Saved entity.
     * @param \ArrayObject $options Save options.
     * @return void
     */
    public function logProjectDocument(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        $map = [
            'Documents\\Model\\Table\\DocumentsTable' => [
                'controller' => 'Documents',
                'label' => __d('projects', 'Document'),
            ],
            'Documents\\Model\\Table\\InvoicesTable' => [
                'controller' => 'Invoices',
                'label' => __d('projects', 'Invoice'),
            ],
        ];

        $subjectClass = get_class($event->getSubject());
        if (!isset($map[$subjectClass])) {
            return;
        }

        // Documents/invoices are created first (without a project) and the project is
        // assigned in a separate, later save. So only log when project_id actually
        // changed in this save and now points at a project.
        $projectId = $entity->get('project_id');
        if (empty($projectId) || !$entity->isDirty('project_id')) {
            return;
        }

        $config = $map[$subjectClass];

        // Resolve the acting user from the current request, when available.
        $userId = null;
        $identity = Router::getRequest()?->getAttribute('identity');
        if ($identity !== null) {
            $userId = $identity->getOriginalData()?->get('id');
        }

        $url = Router::url([
            'plugin' => 'Documents',
            'controller' => $config['controller'],
            'action' => 'view',
            $entity->get('id'),
        ], true);

        $title = trim(sprintf('%s %s', (string)$entity->get('no'), (string)$entity->get('title')));
        if ($title === '') {
            $title = $config['label'];
        }

        // dat_issue is a Cake\I18n\Date (or DateTime); format it deterministically.
        $datIssue = $entity->get('dat_issue');
        $dateStr = '';
        if (is_object($datIssue) && method_exists($datIssue, 'i18nFormat')) {
            $dateStr = ' <span class="small">(' . h((string)$datIssue->i18nFormat('yyyy-MM-dd')) . ')</span>';
        }

        $descript = __d('projects', 'A new {0} has been assigned to this project. Document: {1}. Issue date: {2}.',
            h($config['label']),
            sprintf('<a href="%1$s">%2$s</a>', $url, h($title)),
            $dateStr,
        );

        try {
            $logsTable = TableRegistry::getTableLocator()->get('App.Logs');
            $log = $logsTable->newEntity([
                'model' => 'Project',
                'foreign_id' => $projectId,
                'user_id' => $userId,
                'action' => 'Comment',
                'descript' => $descript,
            ]);
            $logsTable->save($log);
        } catch (Throwable $e) {
            // Never break the document/invoice save because of logging.
        }
    }

    /**
     * Adds css script to layout
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function addScripts(Event $event): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();
        $view->append('script');
        echo $view->Html->css('Projects.projects');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Projects') {
            $view->set('admin_title', __d('projects', 'Projects'));
        }
    }

    /**
     * Add Tasks items to sidebar
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $sidebar Sidebar array.
     * @return void
     */
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        ProjectsSidebar::setAdminSidebar($event, $sidebar);
    }

    /**
     * Add project to adremas index.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param mixed $data LilForm or LilPanels object.
     * @return void
     */
    public function addProjectToAdremas(Event $event, mixed $data): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();
        if (!$view->hasCurrentUser()) {
            return;
        }

        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
        $projectsQuery = $view->getCurrentUser()->applyScope('index', $ProjectsTable->find());

        if ($event->getName() == 'App.Form.Crm.Adremas.edit') {
            $projects = $ProjectsTable->findForOwner($view->getCurrentUser()->company_id, $projectsQuery);

            $projectField = [
                'method' => 'control',
                'parameters' => [
                    'field' => 'project_id', [
                        'type' => 'select',
                        'label' => __d('projects', 'Project') . ':',
                        'options' => $projects,
                        'empty' => '-- ' . __d('projects', 'no project') . ' --',
                        'default' => $view->getRequest()->getQuery('project'),
                    ],
                ],
            ];

            $view->set(compact('projects'));
            $view->Lil->insertIntoArray($data->form['lines'], ['project' => $projectField], ['before' => 'submit']);
        }

        if ($event->getName() == 'App.Index.Crm.Adremas.index') {
            $projects = [];

            // extract unqiue project ids from adremas
            $uniqueProjectIds = array_filter(array_unique($view->get('adremas')->extract('project_id')->toArray()));

            // fetch projects with these ids
            if (count($uniqueProjectIds) > 0) {
                $projects = $projectsQuery
                    ->where(['id IN' => $uniqueProjectIds])
                    ->orderBy(['no DESC', 'title'])
                    ->all()
                    ->combine('id', function ($entity) {
                        return $entity;
                    })
                    ->toArray();
            }

            // add project to table header
            $view->Lil->insertIntoArray(
                $data->table['head']['rows'][0]['columns'],
                ['project' => __d('projects', 'Project')],
                ['after' => 'title'],
            );

            // add project to each table line
            foreach ($view->get('adremas') as $k => $adrema) {
                $view->Lil->insertIntoArray(
                    $data->table['body']['rows'][$k]['columns'],
                    ['project' => $adrema->project_id ? (string)$projects[$adrema->project_id] : '&nbsp;'],
                    ['after' => 'title'],
                );
            }
        }
    }

    /**
     * Filter dashboard documents
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Cake\ORM\Query\SelectQuery $q Query object.
     * @return void
     */
    public function filterDashboardDocuments(Event $event, SelectQuery $q): void
    {
        /** @var \App\Controller\AppController $controller */
        $controller = $event->getSubject();
        if (!$controller->hasCurrentUser()) {
            return;
        }

        $user = $controller->getCurrentUser();

        $q->contain(['Projects']);
        $q->select(TableRegistry::getTableLocator()->get('Projects.Projects'));

        // if user is not admin than filter documents by user access
        if (!$controller->getCurrentUser()->hasRole('admin')) {
            /** @var \Projects\Model\Table\ProjectsUsersTable $ProjectsUsersTable */
            $ProjectsUsersTable = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers');

            $projectsList = $ProjectsUsersTable->find()
                ->where(['user_id' => $user->id])
                ->all()
                ->combine('project_id', 'user_id')
                ->toArray();

            $q->where(['Projects.id IN' => array_keys($projectsList)]);
        }
    }
}
