<?php
declare(strict_types=1);

namespace Calendar\Event;

use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Calendar\Lib\CalendarSidebar;

class CalendarEvents implements EventListenerInterface
{
    /**
     * List of implemented events
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
            echo $view->Html->css('Calendar.calendar_mobile');
        } else {
            echo $view->Html->css('Calendar.calendar');
        }
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Calendar') {
            $view->set('admin_title', __d('calendar', 'Calendar'));
        }
    }

    /**
     * Add Calendar items to sidebar
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $sidebar Sidebar array;
     * @return void
     */
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        CalendarSidebar::setAdminSidebar($event, $sidebar);
    }
}
