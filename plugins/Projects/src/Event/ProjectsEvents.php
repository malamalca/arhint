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
