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
        $sidebarProjects['visible'] = true;
        $sidebarProjects['active'] = $request->getParam('plugin') == 'LilProjects';
        $sidebarProjects['url'] = [
            'plugin' => 'LilProjects',
            'controller' => 'Projects',
            'action' => 'index',
        ];

        $sidebarProjects['items'] = [
            'projects' => [
                'title' => __d('lil_projects', 'Projects'),
                'visible' => true,
                'url' => [
                    'plugin' => 'LilProjects',
                    'controller' => 'Projects',
                    'action' => 'index',
                ],
                'active' => $request->getParam('controller') == 'Projects',
            ],
            'lookups' => [
                'visible' => $currentUser->hasRole('admin'),
                'title' => __d('lil_projects', 'Lookups'),
                'url' => false,
                'active' => in_array($request->getParam('controller'), ['ProjectsStatuses', 'ProjectsMaterials']),
                'submenu' => [
                    'projects_statuses' => [
                        'visible' => true,
                        'title' => __d('lil_projects', 'Statuses'),

                        'url' => [
                            'plugin' => 'LilProjects',
                            'controller' => 'ProjectsStatuses',
                            'action' => 'index',
                        ],
                        'active' => $request->getParam('controller') == 'ProjectsStatuses',
                    ],
                    'projects_materials' => [
                        'visible' => true,
                        'title' => __d('lil_projects', 'Materials'),

                        'url' => [
                            'plugin' => 'LilProjects',
                            'controller' => 'ProjectsMaterials',
                            'action' => 'index',
                        ],
                        'active' => $request->getParam('controller') == 'ProjectsMaterials',
                    ],
                ],
            ],
        ];

        $sidebar->append($sidebarProjects);
        $event->setResult(['sidebar' => $sidebar]);

        return true;
    }
}
