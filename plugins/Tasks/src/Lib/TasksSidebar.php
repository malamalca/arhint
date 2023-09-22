<?php
declare(strict_types=1);

namespace Tasks\Lib;

use App\Controller\AppController;
use ArrayObject;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;

class TasksSidebar
{
    /**
     * Returns number of open tasks for specified due string.
     *
     * @param string $userId User id
     * @return array<string, int>
     */
    public static function countOpenTasks(string $userId): array
    {
        $counters = Cache::remember(
            'Tasks.' . $userId . '.OpenTasks',
            function () use ($userId) {
                /** @var \Tasks\Model\Table\TasksTable $TasksTable */
                $TasksTable = TableRegistry::getTableLocator()->get('Tasks.Tasks');

                $taskDues = ['today', 'tomorrow', 'morethan2days', 'empty'];
                foreach ($taskDues as $due) {
                    $filter = ['due' => $due, 'completed' => 'notyet', 'user' => $userId];
                    $params = $TasksTable->filter($filter);

                    $count = $TasksTable->find()->select()
                        ->andWhere($params['conditions'])
                        ->count();

                    $ret[$due] = $count;
                }

                $filter = ['completed' => 'notyet', 'user' => $userId];
                $params = $TasksTable->filter($filter);

                $q = $TasksTable->find();
                $countByFolder = $q
                    ->select(['id', 'folder_id', 'count' => $q->func()->count('*')])
                    ->andWhere($params['conditions'])
                    ->group('Tasks.folder_id')
                    ->all()
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

        $request = $event->getSubject()->getRequest();
        $currentUser = $event->getSubject()->getCurrentUser();

        $tasks['title'] = __d('tasks', 'Tasks');
        $tasks['visible'] = true;
        $tasks['active'] = $request->getParam('plugin') == 'Tasks';
        $tasks['url'] = [
            'plugin' => 'Tasks',
            'controller' => 'Tasks',
            'action' => 'index',
        ];

        if ($request->getParam('plugin') == 'Tasks') {
            $openTasksCounters = self::countOpenTasks($currentUser->id);
            $countToday = $openTasksCounters['today'];
            $countTomorrow = $openTasksCounters['tomorrow'];
            $countFuture = $openTasksCounters['morethan2days'];
            $countEmpty = $openTasksCounters['empty'];

            $tasks['items'] = [
                'filters' => [
                    'title' => __d('tasks', 'Filters'),
                    'visible' => true,
                    'url' => false,
                    'active' => true,
                    'expand' => true,
                    'submenu' => [
                        'todays' => [
                            'title' => __d('tasks', 'Today\'s Tasks'),
                            'badge' => $countToday == 0 ? '' : $countToday,
                            'visible' => true,
                            'url' => [
                                'plugin' => 'Tasks',
                                'controller' => 'Tasks',
                                'action' => 'index',
                                '?' => ['due' => 'today'],
                            ],
                            'active' => $request->getQuery('due') == 'today',
                            'params' => ['escape' => false],
                        ],
                        'tomorrows' => [
                            'title' => __d('tasks', 'Tomorrow\'s Tasks'),
                            'badge' => $countTomorrow == 0 ? '' : $countTomorrow,
                            'visible' => true,
                            'url' => [
                                'plugin' => 'Tasks',
                                'controller' => 'Tasks',
                                'action' => 'index',
                                '?' => ['due' => 'tomorrow'],
                            ],
                            'active' => $request->getQuery('due') == 'tomorrow',
                            'params' => ['escape' => false],
                        ],
                        'week' => [
                            'title' => __d('tasks', 'Further on'),
                            'badge' => $countFuture == 0 ? '' : $countFuture,
                            'visible' => true,
                            'url' => [
                                'plugin' => 'Tasks',
                                'controller' => 'Tasks',
                                'action' => 'index',
                                '?' => ['due' => 'morethan2days'],
                            ],
                            'active' => $request->getQuery('due') == 'morethan2days',
                            'params' => ['escape' => false],
                        ],
                        'nodue' => [
                            'title' => __d('tasks', 'No Due Date'),
                            'badge' => $countEmpty == 0 ? '' : $countEmpty,
                            'visible' => true,
                            'url' => [
                                'plugin' => 'Tasks',
                                'controller' => 'Tasks',
                                'action' => 'index',
                                '?' => ['due' => 'empty'],
                            ],
                            'active' => $request->getQuery('due') == 'empty',
                            'params' => ['escapeTitle' => false],
                        ],
                        'all' => [
                            'title' => __d('tasks', 'All Tasks'),
                            'visible' => true,
                            'url' => [
                                'plugin' => 'Tasks',
                                'controller' => 'Tasks',
                                'action' => 'index',
                            ],
                            'active' => !$request->getQuery('due') &&
                                !$request->getQuery('folder') &&
                                !$request->getQuery('completed'),
                        ],
                        'completed' => [
                            'title' => __d('tasks', 'Completed Tasks'),
                            'visible' => true,
                            'url' => [
                                'plugin' => 'Tasks',
                                'controller' => 'Tasks',
                                'action' => 'index',
                                '?' => ['completed' => 'only'],
                            ],
                            'active' => $request->getQuery('completed') == 'only',
                        ],
                    ],
                ],
                /*'folders' => [
                    'title' => __d('tasks', 'Folders'),
                    'visible' => true,
                    'url' => [
                        'plugin' => 'Tasks',
                        'controller' => 'TasksFolders',
                        'action' => 'index',
                    ],
                    'active' => true,
                    'expand' => true,
                ],*/
            ];

            /*$owner_id = $currentUser->id;
            $folders = Cache::remember(
                'Tasks.' . $owner_id . '.Folders',
                function () use ($owner_id, $openTasksCounters, $request) {
                    $TasksFolders = TableRegistry::getTableLocator()->get('Tasks.TasksFolders');

                    $folders = $TasksFolders->findForOwner($owner_id);

                    foreach ($folders as $folder) {
                        $countFolder = $openTasksCounters['folders'][$folder->id] ?? 0;
                        $tasks['items']['folders']['submenu'][] = [
                            'title' => h($folder->title),
                            'badge' => $countFolder == 0 ? '' : $countFolder,
                            'visible' => true,
                            'url' => [
                                'plugin' => 'Tasks',
                                'controller' => 'Tasks',
                                'action' => 'index',
                                '?' => ['folder' => $folder->id],
                            ],
                            'active' => $request->getQuery('folder') == $folder->id,
                            'params' => ['escape' => false],
                        ];
                    }

                    return $folders;
                }
            );*/
        }

        $sidebar->append($tasks);

        $event->setResult(['sidebar' => $sidebar]);
    }
}
