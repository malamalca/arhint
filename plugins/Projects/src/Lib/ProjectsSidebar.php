<?php
declare(strict_types=1);

namespace Projects\Lib;

use App\AppPluginsEnum;
use App\Controller\AppController;
use ArrayObject;

class ProjectsSidebar
{
    /**
     * setAdminSidebar method
     *
     * Add admin sidebar elements.
     *
     * @param mixed $event Event object.
     * @param \ArrayObject $sidebar Sidebar array.
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

        if (!$controller->getCurrentUser()->hasAccess(AppPluginsEnum::Projects)) {
            return;
        }

        $request = $controller->getRequest();
        $currentUser = $controller->getCurrentUser();

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
            'workhours' => [
                'title' => __d('projects', 'Workhours'),
                'visible' => $currentUser->hasRole('editor'),
                'url' => [
                    'plugin' => 'Projects',
                    'controller' => 'ProjectsWorkhours',
                    'action' => 'index',
                ],
                'active' => $request->getParam('controller') == 'ProjectsWorkhours',
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
    }
}
