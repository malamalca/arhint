<?php
declare(strict_types=1);

namespace Projects\Event;

use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Projects\Lib\ProjectsSidebar;

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
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
            'Documents.Dashboard.queryDocuments' => 'filterDashboardDocuments',
            'Documents.Documents.indexQuery' => 'filterDashboardDocuments',
            'Lil.Form.Crm.Adremas.edit' => 'addProjectToAdremas',
            'Lil.Index.Crm.Adremas.index' => 'addProjectToAdremas',
        ];
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

        if ($event->getName() == 'Lil.Form.Crm.Adremas.edit') {
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

        if ($event->getName() == 'Lil.Index.Crm.Adremas.index') {
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
