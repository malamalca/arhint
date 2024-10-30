<?php
declare(strict_types=1);

namespace Calendar\Lib;

use App\Controller\AppController;
use ArrayObject;

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
    public static function setAdminSidebar(mixed $event, ArrayObject $sidebar): void
    {
        if (!$event->getSubject() instanceof AppController) {
            return;
        }

        /** @var \App\Controller\AppController $controller */
        $controller = $event->getSubject();
        if (!$controller->hasCurrentUser()) {
            return;
        }

        if (!$controller->getCurrentUser()->hasAccess(\App\AppPluginsEnum::Calendar)) {
            return;
        }

        $request = $controller->getRequest();

        $calendar = [];
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
