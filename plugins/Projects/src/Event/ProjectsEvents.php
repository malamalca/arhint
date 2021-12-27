<?php
declare(strict_types=1);

namespace Projects\Event;

use Cake\Event\EventListenerInterface;
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
        ];
    }

    /**
     * Adds css script to layout
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function addScripts($event)
    {
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
    public function modifySidebar($event, $sidebar)
    {
        ProjectsSidebar::setAdminSidebar($event, $sidebar);
    }
}
