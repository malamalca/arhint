<?php
declare(strict_types=1);

namespace Tasks\Event;

use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Tasks\Lib\TasksSidebar;
use Tasks\Lib\TasksUtils;

class TasksEvents implements EventListenerInterface
{
    /**
     * List of implemented events
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'App.dashboard' => 'dashboardPanels',
            'View.beforeRender' => 'addScripts',
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
        ];
    }

    /**
     * Dashboard panels
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $panels Panels data.
     * @return void
     */
    public function dashboardPanels(Event $event, ArrayObject $panels): void
    {
        /** @var \App\Controller\AppController $controller */
        $controller = $event->getSubject();

        /** @var \App\View\AppView $view */
        $view = $controller->createView();

        /** @var \App\Model\Entity\User $user */
        $user = $controller->getCurrentUser();

        /** @var \Tasks\Model\Table\TasksTable $TasksTable */
        $TasksTable = TableRegistry::getTableLocator()->get('Tasks.Tasks');

        $filter = [
            'user' => $user->id,
            'completed' => 'notyet',
        ];
        $params = array_merge_recursive([
            'contain' => ['TasksFolders'],
            'conditions' => [],
            'order' => ['TasksFolders.title ASC', 'Tasks.completed'],
        ], $TasksTable->filter($filter));

        $tasks = $TasksTable->find()
            ->select()
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->order($params['order'])
            ->all();

        if (!$tasks->isEmpty()) {
            $panels['panels']['tasks'] = ['lines' => [
                '<h5>' . __d('tasks', 'Open Tasks') . '</h5>',
            ]];

            foreach ($tasks as $task) {
                $panels['panels']['tasks-' . $task->id] = TasksUtils::taskPanel($task, $view);
            }
        }

        $event->setResult(['panels' => $panels]);
    }

    /**
     * Add plugins css file to global layout.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function addScripts(Event $event): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();
        $view->append('script');
        if ($view->getRequest()->is('mobile')) {
            echo $view->Html->css('Tasks.tasks_mobile');
        } else {
            echo $view->Html->css('Tasks.tasks');
        }
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Tasks') {
            $view->set('admin_title', __d('tasks', 'Tasks'));
        }
    }

    /**
     * Add Tasks items to sidebar
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $sidebar Sidebar array;
     * @return void
     */
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        TasksSidebar::setAdminSidebar($event, $sidebar);
    }
}
