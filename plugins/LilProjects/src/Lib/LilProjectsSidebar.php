<?php
declare(strict_types=1);

namespace LilProjects\Lib;

class LilProjectsSidebar
{
    /**
     * setAdminSidebar method
     *
     * Add admin sidebar elements.
     *
     * @param mixed $event Event object.
     * @param \ArrayObject $sidebar Sidebar array.
     * @return bool
     */
    public static function setAdminSidebar($event, $sidebar)
    {
        if (!$event->getSubject() instanceof \App\Controller\AppController) {
            return false;
        }

        $request = $event->getSubject()->getRequest();
        $currentUser = $event->getSubject()->getCurrentUser();

        if (empty($currentUser)) {
            return false;
        }

        $sidebarProjects['title'] = __d('lil_projects', 'Projects');
        $sidebarProjects['visible'] = !empty($currentUser);
        $sidebarProjects['active'] = $request->getParam('plugin') == 'LilProjects';
        $sidebarProjects['url'] = [
            'plugin' => 'LilProjects',
            'controller' => 'Projects',
            'action' => 'index',
        ];

        // insert into sidebar right after welcome panel
        //Lil::insertIntoArray($sidebar, ['projects' => $sidebarProjects], ['after' => 'welcome']);
        $sidebar->append($sidebarProjects);

        $event->setResult(['sidebar' => $sidebar]);

        return true;
    }
}
