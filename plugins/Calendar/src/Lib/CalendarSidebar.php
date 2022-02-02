<?php
declare(strict_types=1);

namespace Calendar\Lib;

class CalendarSidebar
{
    /**
     * setAdminSidebar method
     *
     * Add admin sidebar elements.
     *
     * @param mixed $event Event object.
     * @param \ArrayObject $sidebar Sidebar data.
     * @return void
     */
    public static function setAdminSidebar($event, $sidebar)
    {
        if (!$event->getSubject() instanceof \App\Controller\AppController) {
            return;
        }

        $controller = $event->getSubject();
        $request = $event->getSubject()->getRequest();
        $currentUser = $event->getSubject()->getCurrentUser();

        if (empty($currentUser)) {
            return;
        }

        $calendar['title'] = __d('calendar', 'Calendar');
        $calendar['visible'] = true;
        $calendar['active'] = $request->getParam('plugin') == 'Calendar';
        $calendar['url'] = [
            'plugin' => 'Calendar',
            'controller' => 'Events',
            'action' => 'index',
        ];

        if ($request->getParam('plugin') == 'Calendar') {
        }

        $sidebar->append($calendar);

        $event->setResult(['sidebar' => $sidebar]);
    }
}
