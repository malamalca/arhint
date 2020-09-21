<?php
declare(strict_types=1);

namespace LilTasks\Lib;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Lil\Lib\Lil;

class LilTasksSidebar
{
    /**
     * Returns number of open tasks for specified due string.
     *
     * @param string $due Due string
     * @param string $owner_id Owner id
     * @return int
     */
    public static function countOpenTasks($due, $owner_id)
    {
        $count = Cache::remember('LilTasks.OpenTasks.' . $owner_id . '.' . $due, function () use ($due, $owner_id) {
            /** @var \LilTasks\Model\Table\TasksTable $TasksTable */
            $TasksTable = TableRegistry::get('LilTasks.Tasks');

            $filter = ['due' => $due, 'completed' => 'notyet'];
            $params = $TasksTable->filter($filter);

            $count = $TasksTable->find()->select()
                ->where(['Tasks.owner_id' => $owner_id])
                ->andWhere($params['conditions'])
                ->count();

            return $count;
        });

        return $count;
    }

    /**
     * Returns number of open tasks for specified folder.
     *
     * @param string $folder_id Folder id
     * @param string $owner_id Owner id
     * @return int
     */
    public static function countOpenTasksInFolder($folder_id, $owner_id)
    {
        $count = Cache::remember(
            'LilTasks.OpenTasksInFolder.' . $owner_id . '.' . $folder_id,
            function () use ($folder_id, $owner_id) {
                /** @var \LilTasks\Model\Table\TasksTable $TasksTable */
                $TasksTable = TableRegistry::get('LilTasks.Tasks');

                $filter = ['completed' => 'notyet'];
                $params = $TasksTable->filter($filter);

                $count = $TasksTable->find()->select()
                    ->where(['Tasks.owner_id' => $owner_id, 'Tasks.folder_id' => $folder_id])
                    ->andWhere($params['conditions'])
                    ->count();

                return $count;
            }
        );

        return $count;
    }

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

        $tasks['title'] = __d('lil_tasks', 'Tasks');
        $tasks['visible'] = !empty($currentUser);
        $tasks['active'] = $request->getParam('plugin') == 'LilTasks';
        $tasks['url'] = [
            'plugin' => 'LilTasks',
            'controller' => 'Tasks',
            'action' => 'index',
        ];

        $countToday = self::countOpenTasks('today', $currentUser['company_id']);
        $countTomorrow = self::countOpenTasks('tomorrow', $currentUser['company_id']);
        $countFuture = self::countOpenTasks('morethan2days', $currentUser['company_id']);
        $countEmpty = self::countOpenTasks('empty', $currentUser['company_id']);

        $tasks['items'] = [
            'filters' => [
                'title' => __d('lil_tasks', 'Filters'),
                'visible' => true,
                'url' => false,
                'active' => true,
                'expand' => true,
                'submenu' => [
                    'todays' => [
                        'title' => __d('lil_tasks', 'Today\'s Tasks'),
                        'badge' => $countToday == 0 ? '' : $countToday,
                        'visible' => true,
                        'url' => [
                            'plugin' => 'LilTasks',
                            'controller' => 'Tasks',
                            'action' => 'index',
                            '?' => ['due' => 'today'],
                        ],
                        'active' => $request->getQuery('due') == 'today',
                        'params' => ['escape' => false],
                    ],
                    'tomorrows' => [
                        'title' => __d('lil_tasks', 'Tomorrow\'s Tasks'),
                        'badge' => $countTomorrow == 0 ? '' : $countTomorrow,
                        'visible' => true,
                        'url' => [
                            'plugin' => 'LilTasks',
                            'controller' => 'Tasks',
                            'action' => 'index',
                            '?' => ['due' => 'tomorrow'],
                        ],
                        'active' => $request->getQuery('due') == 'tomorrow',
                        'params' => ['escape' => false],
                    ],
                    'week' => [
                        'title' => __d('lil_tasks', 'Further on'),
                        'badge' => $countFuture == 0 ? '' : $countFuture,
                        'visible' => true,
                        'url' => [
                            'plugin' => 'LilTasks',
                            'controller' => 'Tasks',
                            'action' => 'index',
                            '?' => ['due' => 'morethan2days'],
                        ],
                        'active' => $request->getQuery('due') == 'morethan2days',
                        'params' => ['escape' => false],
                    ],
                    'nodue' => [
                        'title' => __d('lil_tasks', 'No Due Date'),
                        'badge' => $countEmpty == 0 ? '' : $countEmpty,
                        'visible' => true,
                        'url' => [
                            'plugin' => 'LilTasks',
                            'controller' => 'Tasks',
                            'action' => 'index',
                            '?' => ['due' => 'empty'],
                        ],
                        'active' => $request->getQuery('due') == 'empty',
                        'params' => ['escapeTitle' => false],
                    ],
                    'all' => [
                        'title' => __d('lil_tasks', 'All Tasks'),
                        'visible' => true,
                        'url' => [
                            'plugin' => 'LilTasks',
                            'controller' => 'Tasks',
                            'action' => 'index',
                        ],
                        'active' => !$request->getQuery('due') &&
                            !$request->getQuery('folder') &&
                            !$request->getQuery('completed'),
                    ],
                    'completed' => [
                        'title' => __d('lil_tasks', 'Completed Tasks'),
                        'visible' => true,
                        'url' => [
                            'plugin' => 'LilTasks',
                            'controller' => 'Tasks',
                            'action' => 'index',
                            '?' => ['completed' => 'only'],
                        ],
                        'active' => $request->getQuery('completed') == 'only',
                    ],
                ],
            ],
            'folders' => [
                'title' => __d('lil_tasks', 'Folders'),
                'visible' => true,
                'url' => false,
                'active' => true,
                'expand' => true,
            ],
        ];

        /** @var \LilTasks\Model\Table\TasksFoldersTable $TasksFolders */
        $TasksFolders = TableRegistry::get('LilTasks.TasksFolders');

        $folders = $TasksFolders->findForOwner($currentUser->company_id);

        foreach ($folders as $folder) {
            $countFolder = self::countOpenTasksInFolder($folder->id, $currentUser->company_id);
            $tasks['items']['folders']['submenu'][] = [
                'title' => h($folder->title),
                'badge' => $countFolder == 0 ? '' : $countFolder,
                'visible' => true,
                'url' => [
                    'plugin' => 'LilTasks',
                    'controller' => 'Tasks',
                    'action' => 'index',
                    '?' => ['folder' => $folder->id],
                ],
                'active' => $request->getQuery('folder') == $folder->id,
                'params' => ['escape' => false],
            ];
        }

        // insert into sidebar right after welcome panel
        Lil::insertIntoArray($sidebar, ['tasks' => $tasks], ['after' => 'welcome']);

        $event->setResult(['sidebar' => $sidebar]);
    }
}
