<?php
declare(strict_types=1);

namespace LilTasks\Event;

use Cake\Event\EventListenerInterface;
use LilTasks\Lib\LilTasksSidebar;

class LilTasksEvents implements EventListenerInterface
{
    /**
     * List of implemented events
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'View.beforeRender' => 'addScripts',
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
        ];
    }

    /**
     * Add plugins css file to global layout.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function addScripts($event)
    {
        $view = $event->getSubject();
        $view->append('script');
        echo $view->Html->css('LilTasks.lil_tasks');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'LilTasks') {
            $view->set('admin_title', __d('lil_tasks', 'Tasks'));
        }
    }

    /**
     * Add Tasks items to sidebar
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $sidebar Sidebar array;
     * @return void
     */
    public function modifySidebar($event, $sidebar)
    {
        LilTasksSidebar::setAdminSidebar($event, $sidebar);
    }
}
