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
            'lookups' => [
                'visible' => $currentUser->hasRole('admin'),
                'title' => __d('projects', 'Lookups'),
                'url' => false,
                'active' => in_array($request->getParam('controller'), ['ProjectsStatuses', 'ProjectsMaterials']),
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
                    'projects_materials' => [
                        'visible' => true,
                        'title' => __d('projects', 'Materials'),

                        'url' => [
                            'plugin' => 'Projects',
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