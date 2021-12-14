<?php
declare(strict_types=1);

namespace LilProjects\Event;

use Cake\Event\EventListenerInterface;
use LilProjects\Lib\LilProjectsSidebar;

class LilProjectsEvents implements EventListenerInterface
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
        echo $view->Html->css('LilProjects.lil_projects');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'LilProjects') {
            $view->set('admin_title', __d('lil_projects', 'Projects'));
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
        LilProjectsSidebar::setAdminSidebar($event, $sidebar);
    }
}
