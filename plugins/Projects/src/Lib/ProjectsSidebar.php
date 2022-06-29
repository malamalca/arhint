<?php
declare(strict_types=1);

namespace Projects\Lib;

class ProjectsSidebar
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

        $sidebarProjects['title'] = __d('projects', 'Projects');
        $sidebarProjects['visible'] = true;
        $sidebarProjects['active'] = $request->getParam('plugin') == 'Projects';
        $sidebarProjects['url'] = [
            'plugin' => 'Projects',
            'controller' => 'Projects',
            'action' => 'index',
        ];

        $sidebarProjects['items'] = [
            'projects' => [
                'title' => __d('projects', 'Projects'),
                'visible' => true,
                'url' => [
                    'plugin' => 'Projects',
                    'controller' => 'Projects',
                    'action' => 'index',
                ],
                'active' => $request->getParam('controller') == 'Projects',
            ],
            'reports' => [
                'visible' => $currentUser->hasRole('admin'),
                'title' => __d('projects', 'Reports'),
                'url' => false,
                'params' => [],
                'active' => $request->getParam('controller') == 'ProjectsWorkhours' &&
                    $request->getParam('action') == 'report',
                'submenu' => [
                    'report' => [
                        'visible' => true,
                        'title' => __d('projects', 'Workhours'),
                        'url' => [
                            'plugin' => 'Projects',
                            'controller' => 'ProjectsWorkhours',
                            'action' => 'report',
                        ],
                        'active' => $request->getParam('controller') == 'ProjectsWorkhours' &&
                            $request->getParam('action') == 'report',
                    ],
                ],
            ],
            'lookups' => [
                'visible' => $currentUser->hasRole('admin'),
                'title' => __d('projects', 'Lookups'),
                'url' => false,
                'active' => in_array($request->getParam('controller'), ['ProjectsStatuses',]),
                'submenu' => [
                    'projects_statuses' => [
                        'visible' => true,
                        'title' => __d('projects', 'Statuses'),

                        'url' => [
                            'plugin' => 'Projects',
                            'controller' => 'ProjectsStatuses',
                            'action' => 'index',
                        ],
                        'active' => $request->getParam('controller') == 'ProjectsStatuses',
                    ],
                ],
            ],
        ];

        $sidebar->append($sidebarProjects);
        $event->setResult(['sidebar' => $sidebar]);

        return true;
    }
}
