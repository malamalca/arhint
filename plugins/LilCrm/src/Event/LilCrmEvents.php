<?php
declare(strict_types=1);

namespace LilCrm\Event;

use Cake\Core\Configure;
use Cake\Event\EventListenerInterface;
use LilCrm\Lib\LilCrmSidebar;

class LilCrmEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
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
     * Add css script to main layout.
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function addScripts($event)
    {
        $view = $event->getSubject();
        $view->append('script');
        echo $view->Html->css('LilCrm.lil_crm');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'LilCrm') {
            $view->set('admin_title', __d('lil_crm', 'Costumers'));
        }
    }

    /**
     * Modify Lil sidebar.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \ArrayObject $sidebar Sidebar.
     * @return void
     */
    public function modifySidebar($event, $sidebar)
    {
        $ret = $sidebar;
        if (Configure::read('LilCrm.showSidebar')) {
            LilCrmSidebar::setAdminSidebar($event, $sidebar);
        }
    }
}
