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
     * @param string $owner_id Owner id
     * @return int
     */
    public static function countOpenTasks($owner_id)
    {
        $counters = Cache::remember(
            'LilTasks.' . $owner_id . '.OpenTasks',
            function () use ($owner_id) {
                /** @var \LilTasks\Model\Table\TasksTable $TasksTable */
                $TasksTable = TableRegistry::get('LilTasks.Tasks');

                $taskDues = ['today', 'tomorrow', 'morethan2days', 'empty'];
                foreach ($taskDues as $due) {
                    $filter = ['due' => $due, 'completed' => 'notyet'];
                    $params = $TasksTable->filter($filter);

                    $count = $TasksTable->find()->select()
                        ->where(['Tasks.owner_id' => $owner_id])
                        ->andWhere($params['conditions'])
                        ->count();

                    $ret[$due] = $count;
                }

                $filter = ['completed' => 'notyet'];
                $params = $TasksTable->filter($filter);

                $q = $TasksTable->find();
                $countByFolder = $q
                    ->select(['id', 'folder_id', 'count' => $q->func()->count('*')])
                    ->where(['Tasks.owner_id' => $owner_id])
                    ->andWhere($params['conditions'])
                    ->group('Tasks.folder_id')
                    ->combine('folder_id', 'count')
                    ->toArray();

                $ret['folders'] = $countByFolder;

                return $ret;
            }
        );

        return $counters;
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

        $openTasksCounters = self::countOpenTasks($currentUser['company_id']);
        $countToday = $openTasksCounters['today'];
        $countTomorrow = $openTasksCounters['tomorrow'];
        $countFuture = $openTasksCounters['morethan2days'];
        $countEmpty = $openTasksCounters['empty'];

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
            $countFolder = $openTasksCounters['folders'][$folder->id] ?? 0;
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
