<?php
declare(strict_types=1);

namespace Tasks\Event;

use App\Lib\AITool;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Tasks\Lib\TasksSidebar;
use Tasks\Lib\TasksUtils;

class TasksEvents implements EventListenerInterface
{
    /**
     * List of implemented events
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'App.dashboard' => 'dashboardPanels',
            'View.beforeRender' => 'addScripts',
            'App.Sidebar.beforeRender' => 'modifySidebar',
            'App.AIAssistant.registerModule' => 'aiAssistantRegisterModule',
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    /**
     * Dashboard panels
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $panels Panels data.
     * @return void
     */
    public function dashboardPanels(Event $event, ArrayObject $panels): void
    {
        /** @var \App\Controller\AppController $controller */
        $controller = $event->getSubject();

        /** @var \App\View\AppView $view */
        $view = $controller->createView();

        /** @var \App\Model\Entity\User $user */
        $user = $controller->getCurrentUser();

        /** @var \Tasks\Model\Table\TasksTable $TasksTable */
        $TasksTable = TableRegistry::getTableLocator()->get('Tasks.Tasks');

        $filter = [
            'user' => $user->id,
            'completed' => 'notyet',
        ];
        $params = array_merge_recursive([
            'contain' => ['TasksFolders'],
            'conditions' => [],
            'order' => ['TasksFolders.title ASC', 'Tasks.completed'],
        ], $TasksTable->filter($filter));

        $tasks = $TasksTable->find()
            ->select()
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->orderBy($params['order'])
            ->all();

        if (!$tasks->isEmpty()) {
            $panels['panels']['tasks'] = [
                'params' => ['class' => 'dashboard-panel'],
                'lines' => [
                    '<h5>' . __d('tasks', 'Open Tasks') . '</h5>',
                ],
            ];

            foreach ($tasks as $task) {
                /** @var \Tasks\Model\Entity\Task $task */
                $panels['panels']['tasks']['lines'][] =
                    $view->Lil->panels(['panels' => [TasksUtils::taskPanel($task, $view)]]);
            }
        }

        $event->setResult(['panels' => $panels]);
    }

    /**
     * Add plugins css file to global layout.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function addScripts(Event $event): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();
        $view->append('script');
        if ($view->getRequest()->is('mobile')) {
            echo $view->Html->css('Tasks.tasks_mobile');
        } else {
            echo $view->Html->css('Tasks.tasks');
        }
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Tasks') {
            $view->set('admin_title', __d('tasks', 'Tasks'));
        }
    }

    /**
     * Add Tasks items to sidebar
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $sidebar Sidebar array;
     * @return void
     */
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        TasksSidebar::setAdminSidebar($event, $sidebar);
    }

    /**
     * Register the Tasks module for AI assistant module detection.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $modulesList Modules list to append to.
     * @return void
     */
    public function aiAssistantRegisterModule(Event $event, ArrayObject $modulesList): void
    {
        $modulesList['Tasks'] = 'Personal task management tools for tracking to-do items and deadlines.';
    }

    /**
     * Add AI assistant tools
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $toolsList List of tools
     * @return void
     */
    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        $toolsList->append(new AITool(
            name: 'Tasks.get_tasks',
            arguments: [
                'due_date' => [
                    'type' => 'string',
                    'description' => '"today", "tomorrow", "thisWeek", or YYYY-MM-DD. Optional.',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => '"completed", "notyet", or "all". Defaults to "notyet".',
                ],
                'folder_id' => [
                    'type' => 'string',
                    'description' => 'The ID (UUID) of the folder to filter tasks by. Optional.',
                ],
                'user_id' => [
                    'type' => 'string',
                    'description' => 'The ID (UUID) of the user who created the task. Optional.',
                ],
                'tasker_id' => [
                    'type' => 'string',
                    'description' => 'The ID (UUID) of the user who must complete the task. Optional.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Case-insensitive partial match on task title. Optional.',
                ],
            ],
            description: 'This function retrieves tasks.',
        ));

        $toolsList->append(new AITool(
            name: 'Tasks.get_folders',
            arguments: [],
            description: 'This function retrieves folders in which tasks are organized.',
        ));

        $toolsList->append(new AITool(
            name: 'Tasks.save_task',
            arguments: [
                'id' => [
                    'type' => 'string',
                    'description' => 'Task UUID to update. Omit or leave empty to create a new task.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Task title (required for new tasks).',
                ],
                'folder_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the folder to assign the task to (required for new tasks).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional task description.',
                ],
                'deadline' => [
                    'type' => 'string',
                    'description' => 'Optional deadline in YYYY-MM-DD or YYYY-MM-DD HH:MM:SS format.',
                ],
                'priority' => [
                    'type' => 'integer',
                    'description' => 'Optional task priority (0 = normal, higher = more urgent).',
                ],
                'completed' => [
                    'type' => 'string',
                    'description' => 'Must be a datetime string "YYYY-MM-DD HH:MM:SS". ' .
                        'To mark as done use the current datetime. Never use true, false, 1, or 0.',
                ],
                'tasker_id' => [
                    'type' => 'string',
                    'description' => 'UUID from App.get_users. The user who must complete the task. ' .
                        'Never a name or username.',
                ],
            ],
            description: 'Creates a new task or updates an existing one. ' .
                'Returns the saved task data or validation errors.',
        ));

        $toolsList->append(new AITool(
            name: 'Tasks.delete_task',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the task to delete.'],
            ],
            description: 'Deletes a task by its ID. Only tasks owned by the current user can be deleted.',
        ));
    }

    /**
     * Execute AI assistant tool
     *
     * @param \Cake\Event\Event $event Event object
     * @param string $tool Tool name
     * @param array<mixed> $arguments Tool arguments
     * @return void
     */
    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        $currentUser = $event->getData()[2] ?? null;

        switch ($tool) {
            case 'Tasks.get_tasks':
                /** @var \Tasks\Model\Table\TasksTable $tasksTable */
                $tasksTable = TableRegistry::getTableLocator()->get('Tasks.Tasks');

                $filter = [];
                if (!empty($arguments['due_date'])) {
                    $dueAliasMap = [
                        'thisweek' => 'week',
                        'this_week' => 'week',
                        'this week' => 'week',
                        'nextweek' => 'week',
                        'next_week' => 'week',
                        'next week' => 'week',
                        'morethan2days' => 'morethan2days',
                        'more_than_2_days' => 'morethan2days',
                    ];
                    $rawDue = $arguments['due_date'];
                    $normalized = strtolower(trim($rawDue));
                    if (isset($dueAliasMap[$normalized])) {
                        $filter['due'] = $dueAliasMap[$normalized];
                    } elseif (in_array($normalized, ['today', 'tomorrow', 'week', 'morethan2days', 'empty'], true)) {
                        $filter['due'] = $normalized;
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDue)) {
                        $filter['due'] = $rawDue;
                    }
                    // unrecognised value: omit the filter rather than crash
                }
                if (!empty($arguments['status'])) {
                    $filter['completed'] = $arguments['status'] === 'completed' ? 'only' : 'notyet';
                }
                if (!empty($arguments['folder_id'])) {
                    $filter['folder'] = $arguments['folder_id'];
                }
                if (!empty($arguments['user_id'])) {
                    $filter['user'] = $arguments['user_id'];
                }
                $params = $tasksTable->filter($filter);

                $query = $currentUser->applyScope('index', $tasksTable->find())
                    ->select()
                    ->where($params['conditions']);

                if (!empty($arguments['tasker_id'])) {
                    $query->where(['Tasks.tasker_id' => $arguments['tasker_id']]);
                }
                if (!empty($arguments['title'])) {
                    $query->where(['Tasks.title LIKE' => '%' . $arguments['title'] . '%']);
                }

                $tasks = $query->all()->toArray();

                $event->setResult($tasks);
                break;
            case 'Tasks.get_folders':
                $tasksFoldersTable = TableRegistry::getTableLocator()->get('Tasks.TasksFolders');
                $query = $currentUser->applyScope('index', $tasksFoldersTable->find('list'));
                $query->all();

                $event->setResult($query->toArray());
                break;

            case 'Tasks.save_task':
                if (
                    !empty($arguments['tasker_id'])
                    && !preg_match(
                        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                        (string)$arguments['tasker_id'],
                    )
                ) {
                    $event->setResult([
                        'error' => 'tasker_id must be a UUID. "' . $arguments['tasker_id'] . '" looks like a name — '
                            . 'call App.get_users to find the user\'s UUID first.',
                    ]);
                    break;
                }

                /** @var \Tasks\Model\Table\TasksTable $tasksTable */
                $tasksTable = TableRegistry::getTableLocator()->get('Tasks.Tasks');

                // Normalize boolean-like completed values the model sometimes sends.
                if (isset($arguments['completed'])) {
                    $completedVal = strtolower(trim((string)$arguments['completed']));
                    if (in_array($completedVal, ['true', '1', 'yes', 'done'], true)) {
                        $arguments['completed'] = date('Y-m-d H:i:s');
                    } elseif (in_array($completedVal, ['false', '0', 'no', 'null', ''], true)) {
                        $arguments['completed'] = null;
                    }
                }

                if (!empty($arguments['id'])) {
                    $entity = $tasksTable->find()
                        ->where(['Tasks.id' => $arguments['id'], 'Tasks.owner_id' => $currentUser->get('company_id')])
                        ->first();
                    if (!$entity) {
                        $event->setResult(['error' => 'Task not found or access denied.']);
                        break;
                    }
                    if (!$currentUser->can('edit', $entity)) {
                        $event->setResult(['error' => 'You are not authorized to edit this task.']);
                        break;
                    }

                    $entity = $tasksTable->patchEntity($entity, $arguments);
                } else {
                    $data = $arguments;
                    $data['owner_id'] = $currentUser->get('company_id');
                    $data['user_id'] = $currentUser->get('id');
                    $entity = $tasksTable->newEntity($data);
                    if (!$currentUser->can('edit', $entity)) {
                        $event->setResult(['error' => 'You are not authorized to create tasks.']);
                        break;
                    }
                }

                if ($tasksTable->save($entity)) {
                    $event->setResult(['success' => true, 'id' => $entity->id, 'title' => $entity->get('title')]);
                } else {
                    $errors = $entity->getErrors();
                    $errorParts = [];
                    foreach ($errors as $field => $fieldErrors) {
                        foreach ((array)$fieldErrors as $rule => $message) {
                            $errorParts[] = "$field ($rule): $message";
                        }
                    }
                    $errorMessage = $errorParts !== []
                        ? implode('; ', $errorParts)
                        : 'Save failed without validation errors (possible DB or permission issue)';
                    $event->setResult(['success' => false, 'error' => $errorMessage]);
                }
                break;

            case 'Tasks.delete_task':
                if (empty($arguments['id'])) {
                    $event->setResult(['error' => 'Task ID is required.']);
                    break;
                }

                $tasksTable = TableRegistry::getTableLocator()->get('Tasks.Tasks');
                $entity = $tasksTable->find()
                    ->where(['Tasks.id' => $arguments['id'], 'Tasks.owner_id' => $currentUser->get('company_id')])
                    ->first();

                if (!$entity) {
                    $event->setResult(['error' => 'Task not found or access denied.']);
                    break;
                }

                if (!$currentUser->can('delete', $entity)) {
                    $event->setResult(['error' => 'You are not authorized to delete this task.']);
                    break;
                }

                if ($tasksTable->delete($entity)) {
                    $event->setResult(['success' => true]);
                } else {
                    $event->setResult(['success' => false, 'error' => 'Failed to delete the task.']);
                }
                break;
        }
    }
}
