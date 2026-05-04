<?php
declare(strict_types=1);

namespace Projects\Event;

use App\Lib\AITool;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;
use Projects\Model\Table\ProjectsTasksCommentsTable;

class ProjectsAIToolsEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'App.AIAssistant.registerModule' => 'aiAssistantRegisterModule',
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    /**
     * Register the Projects module for AI assistant module detection.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $modulesList Modules list to append to.
     * @return void
     */
    public function aiAssistantRegisterModule(Event $event, ArrayObject $modulesList): void
    {
        $modulesList['Projects'] = 'Project management tools for searching projects, managing milestones, tasks, ' .
            'project documents, project invoices, project users and logging work.';
    }

    /**
     * Add AI assistant tools.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $toolsList List of tools.
     * @return void
     */
    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        $toolsList->append(new AITool(
            name: 'Projects.search_projects',
            arguments: [
                'search' => [
                    'type' => 'string',
                    'description' => 'Search term to filter by number or title. Omit to list all projects.',
                ],
                'inactive' => [
                    'type' => 'boolean',
                    'description' => 'Include inactive projects. Defaults to false.',
                ],
            ],
            description: 'Lists or searches accessible projects. Returns id, no, title, active state, '
                . 'milestone counts, and status. Each result includes view_url; render no as [no](view_url).',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.get_project',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the project to retrieve.'],
            ],
            description: 'Fetches full details of a single project including description, status, '
                . 'team members, and milestones with task counts. Includes a view_url field; '
                . 'always render no as a markdown link: [no](view_url).',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.get_project_tasks',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project.'],
                'status' => [
                    'type' => 'string',
                    'description' => '"open" for unclosed tasks, "closed" for completed, or omit for all.',
                ],
                'milestone_id' => [
                    'type' => 'string',
                    'description' => 'Filter tasks by milestone UUID.',
                ],
                'user_id' => [
                    'type' => 'string',
                    'description' => 'Filter tasks assigned to this user UUID.',
                ],
            ],
            description: 'Lists tasks for a project, optionally filtered by status, milestone, or '
                . 'assigned user. Returns task number, title, assigned user, milestone, and closed date.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.get_task',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the task to retrieve.'],
                'with_comments' => [
                    'type' => 'boolean',
                    'description' => 'Include task comments and audit history. Defaults to false.',
                ],
            ],
            description: 'Fetches full details of a single project task including description, '
                . 'milestone, assigned user, and optionally comments.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.create_task',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project. Required.'],
                'title' => ['type' => 'string', 'description' => 'Task title. Required.'],
                'descript' => ['type' => 'string', 'description' => 'Task description (HTML allowed).'],
                'milestone_id' => ['type' => 'string', 'description' => 'UUID of the milestone to assign. Required.'],
                'user_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the user to assign the task to. '
                        . 'Defaults to the current user.',
                ],
            ],
            description: 'Creates a new task in a project.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.update_task',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the task to update. Required.'],
                'title' => ['type' => 'string', 'description' => 'Task title.'],
                'descript' => ['type' => 'string', 'description' => 'Task description.'],
                'milestone_id' => ['type' => 'string', 'description' => 'UUID of the milestone.'],
                'user_id' => ['type' => 'string', 'description' => 'UUID of the assigned user.'],
                'closed' => [
                    'type' => 'boolean',
                    'description' => 'True to close the task, false to reopen it.',
                ],
            ],
            description: 'Updates a project task. Can change title, description, milestone, assigned '
                . 'user, or open/close state.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.add_task_comment',
            arguments: [
                'task_id' => ['type' => 'string', 'description' => 'UUID of the task. Required.'],
                'descript' => [
                    'type' => 'string',
                    'description' => 'Comment text (HTML allowed). Required.',
                ],
            ],
            description: 'Adds a comment to a project task.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.add_project_log',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project. Required.'],
                'descript' => [
                    'type' => 'string',
                    'description' => 'Log entry text (HTML allowed). Required.',
                ],
            ],
            description: 'Adds a manual activity log entry to a project.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.log_workhours',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project. Required.'],
                'duration' => [
                    'type' => 'integer',
                    'description' => 'Duration in minutes. Required.',
                ],
                'descript' => ['type' => 'string', 'description' => 'Description of work performed.'],
                'started' => [
                    'type' => 'string',
                    'description' => 'Start datetime in YYYY-MM-DD HH:MM:SS format. '
                        . 'Defaults to current date and time.',
                ],
            ],
            description: 'Logs time worked on a project by the current user.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.create_milestone',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project. Required.'],
                'title' => ['type' => 'string', 'description' => 'Milestone title. Required.'],
                'date_due' => [
                    'type' => 'string',
                    'description' => 'Due date in YYYY-MM-DD format.',
                ],
            ],
            description: 'Creates a new milestone in a project.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.get_project_logs',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project.'],
            ],
            description: 'Lists activity logs for a project. Returns id, created datetime, user, and descript.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.get_project_users',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project.'],
            ],
            description: 'Lists users assigned to a project. Returns project-user relation id and user details.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.get_project_documents',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project.'],
            ],
            description: 'Lists documents linked to a project across invoices, documents, and travel orders. '
                . 'Each item includes view_url; always render no as a markdown link: [no](view_url).',
        ));
    }

    /**
     * Execute AI assistant tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param string $tool Tool name.
     * @param array<mixed> $arguments Tool arguments.
     * @return void
     */
    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        $currentUser = $event->getData()[2] ?? null;

        match ($tool) {
            'Projects.search_projects' => $this->executeSearchProjects($event, $arguments, $currentUser),
            'Projects.get_project' => $this->executeGetProject($event, $arguments, $currentUser),
            'Projects.get_project_tasks' => $this->executeGetProjectTasks($event, $arguments, $currentUser),
            'Projects.get_task' => $this->executeGetTask($event, $arguments, $currentUser),
            'Projects.create_task' => $this->executeCreateTask($event, $arguments, $currentUser),
            'Projects.update_task' => $this->executeUpdateTask($event, $arguments, $currentUser),
            'Projects.add_task_comment' => $this->executeAddTaskComment($event, $arguments, $currentUser),
            'Projects.add_project_log' => $this->executeAddProjectLog($event, $arguments, $currentUser),
            'Projects.log_workhours' => $this->executeLogWorkhours($event, $arguments, $currentUser),
            'Projects.create_milestone' => $this->executeCreateMilestone($event, $arguments, $currentUser),
            'Projects.get_project_logs' => $this->executeGetProjectLogs($event, $arguments, $currentUser),
            'Projects.get_project_users' => $this->executeGetProjectUsers($event, $arguments, $currentUser),
            'Projects.get_project_documents' => $this->executeGetProjectDocuments($event, $arguments, $currentUser),
            default => null,
        };
    }

    /**
     * Execute Projects.search_projects tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeSearchProjects(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Projects\Model\Table\ProjectsTable $projectsTable */
        $projectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        $filter = ['inactive' => !empty($arguments['inactive'])];

        $search = $this->normalizeProjectSearch($arguments['search'] ?? null);
        if ($search !== null) {
            $filter['search'] = $search;
        }
        if (!empty($arguments['inactive'])) {
            $filter['inactive'] = true;
        }
        $params = $projectsTable->filter($filter);

        if (empty($arguments['inactive'])) {
            $params['conditions'] = array_merge_recursive(
                ['Projects.active IN' => [true]],
                $params['conditions'],
            );
        }

        $projects = $currentUser->applyScope('index', $projectsTable->find())
            ->select([
                'Projects.id',
                'Projects.no',
                'Projects.title',
                'Projects.active',
                'Projects.milestones_open',
                'Projects.milestones_done',
            ])
            ->contain(['ProjectsStatuses'])
            ->where($params['conditions'])
            ->limit(20)
            ->all()
            ->toArray();

        foreach ($projects as $project) {
            $project->view_url = $this->projectViewUrl((string)$project->id);
        }

        $event->setResult($projects);
    }

    /**
     * Normalize prompt-style project search input into a title/number search term.
     *
     * @param mixed $search Search argument from the AI tool call.
     * @return string|null
     */
    private function normalizeProjectSearch(mixed $search): ?string
    {
        if (!is_string($search)) {
            return null;
        }

        $search = trim($search);
        if ($search === '') {
            return null;
        }

        $isPromptStyle = preg_match('/^\s*(list|show|find|get|search)\b/i', $search) === 1
            || preg_match('/\ball\b/i', $search) === 1;
        if (!$isPromptStyle) {
            return $search;
        }

        $normalized = preg_replace(
            '/\b(list|show|find|get|search|all|active|inactive|projects?|please|me|the)\b/i',
            ' ',
            $search,
        );
        $normalized = preg_replace('/\s+/', ' ', (string)$normalized);
        $normalized = trim((string)$normalized);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Execute Projects.get_project tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetProject(Event $event, array $arguments, mixed $currentUser): void
    {
        $projectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        $project = $currentUser->applyScope('index', $projectsTable->find())
            ->contain(['ProjectsStatuses', 'Users', 'ProjectsMilestones'])
            ->where(['Projects.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $project->view_url = $this->projectViewUrl((string)$project->id);

        $event->setResult($project);
    }

    /**
     * Build a project view URL for AI responses.
     *
     * @param string $projectId Project UUID.
     * @return string
     */
    private function projectViewUrl(string $projectId): string
    {
        try {
            return Router::url([
                'plugin' => 'Projects', 'controller' => 'Projects', 'action' => 'view', $projectId,
            ], true);
        } catch (MissingRouteException) {
            return '/projects/view/' . $projectId;
        }
    }

    /**
     * Execute Projects.get_project_tasks tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetProjectTasks(Event $event, array $arguments, mixed $currentUser): void
    {
        $project = $this->loadAccessibleProject($currentUser, $arguments['project_id'] ?? '');
        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $conditions = ['ProjectsTasks.project_id' => $project->id];

        $status = $arguments['status'] ?? '';
        if ($status === 'open') {
            $conditions['ProjectsTasks.closed IS'] = null;
        } elseif ($status === 'closed') {
            $conditions['ProjectsTasks.closed IS NOT'] = null;
        }

        if (!empty($arguments['milestone_id'])) {
            $conditions['ProjectsTasks.milestone_id'] = $arguments['milestone_id'];
        }
        if (!empty($arguments['user_id'])) {
            $conditions['ProjectsTasks.user_id'] = $arguments['user_id'];
        }

        $tasks = TableRegistry::getTableLocator()->get('Projects.ProjectsTasks')
            ->find()
            ->select(['ProjectsTasks.id', 'ProjectsTasks.no', 'ProjectsTasks.title',
                'ProjectsTasks.closed', 'ProjectsTasks.user_id', 'ProjectsTasks.milestone_id'])
            ->contain(['Users', 'Milestones'])
            ->where($conditions)
            ->orderBy(['ProjectsTasks.created' => 'DESC'])
            ->limit(50)
            ->all()
            ->toArray();

        $event->setResult($tasks);
    }

    /**
     * Execute Projects.get_task tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetTask(Event $event, array $arguments, mixed $currentUser): void
    {
        $tasksTable = TableRegistry::getTableLocator()->get('Projects.ProjectsTasks');

        $contain = ['Users', 'Milestones', 'Projects'];
        if (!empty($arguments['with_comments'])) {
            $contain[] = 'Comments';
        }

        $task = $tasksTable->find()
            ->contain($contain)
            ->where(['ProjectsTasks.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$task) {
            $event->setResult(['error' => 'Task not found.']);

            return;
        }

        if (!$currentUser->can('view', $task)) {
            $event->setResult(['error' => 'Access denied.']);

            return;
        }

        $event->setResult($task);
    }

    /**
     * Execute Projects.create_task tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeCreateTask(Event $event, array $arguments, mixed $currentUser): void
    {
        $project = $this->loadAccessibleProject($currentUser, $arguments['project_id'] ?? '');
        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $tasksTable = TableRegistry::getTableLocator()->get('Projects.ProjectsTasks');

        /** @var \Projects\Model\Entity\ProjectsTask $task */
        $task = $tasksTable->newEntity([
            'project_id' => $project->id,
            'user_id' => $arguments['user_id'] ?? $currentUser->get('id'),
            'title' => $arguments['title'] ?? '',
            'descript' => $arguments['descript'] ?? null,
            'milestone_id' => $arguments['milestone_id'] ?? null,
        ]);

        if (!$currentUser->can('edit', $task)) {
            $event->setResult(['error' => 'You are not authorized to create tasks in this project.']);

            return;
        }

        if (!$task->getErrors() && $tasksTable->save($task, ['auditUserId' => $currentUser->get('id')])) {
            $event->setResult(['id' => $task->id, 'no' => $task->no, 'title' => $task->title]);
        } else {
            $event->setResult(['error' => 'Failed to create task.', 'errors' => $task->getErrors()]);
        }
    }

    /**
     * Execute Projects.update_task tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeUpdateTask(Event $event, array $arguments, mixed $currentUser): void
    {
        $tasksTable = TableRegistry::getTableLocator()->get('Projects.ProjectsTasks');

        /** @var \Projects\Model\Entity\ProjectsTask $task */
        $task = $tasksTable->find()
            ->where(['ProjectsTasks.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$task) {
            $event->setResult(['error' => 'Task not found.']);

            return;
        }

        if (!$currentUser->can('edit', $task)) {
            $event->setResult(['error' => 'You are not authorized to edit this task.']);

            return;
        }

        $updateData = array_intersect_key(
            $arguments,
            array_flip(['title', 'descript', 'milestone_id', 'user_id']),
        );

        if (array_key_exists('closed', $arguments)) {
            $updateData['closed'] = $arguments['closed'] ? Date::now() : null;
        }

        $tasksTable->patchEntity($task, $updateData);

        if (!$task->getErrors() && $tasksTable->save($task, ['auditUserId' => $currentUser->get('id')])) {
            $event->setResult(['id' => $task->id, 'no' => $task->no, 'title' => $task->title]);
        } else {
            $event->setResult(['error' => 'Failed to update task.', 'errors' => $task->getErrors()]);
        }
    }

    /**
     * Execute Projects.add_task_comment tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddTaskComment(Event $event, array $arguments, mixed $currentUser): void
    {
        $tasksTable = TableRegistry::getTableLocator()->get('Projects.ProjectsTasks');
        $task = $tasksTable->find()
            ->where(['ProjectsTasks.id' => $arguments['task_id'] ?? ''])
            ->first();

        if (!$task) {
            $event->setResult(['error' => 'Task not found.']);

            return;
        }

        if (!$currentUser->can('view', $task)) {
            $event->setResult(['error' => 'Access denied.']);

            return;
        }

        $commentsTable = TableRegistry::getTableLocator()->get('Projects.ProjectsTasksComments');
        $comment = $commentsTable->newEntity([
            'task_id' => $task->id,
            'user_id' => $currentUser->get('id'),
            'kind' => ProjectsTasksCommentsTable::KIND_TASK_COMMENT,
            'descript' => $arguments['descript'] ?? '',
        ]);

        if (!$comment->getErrors() && $commentsTable->save($comment)) {
            $event->setResult(['id' => $comment->id]);
        } else {
            $event->setResult(['error' => 'Failed to save comment.', 'errors' => $comment->getErrors()]);
        }
    }

    /**
     * Execute Projects.add_project_log tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddProjectLog(Event $event, array $arguments, mixed $currentUser): void
    {
        $project = $this->loadAccessibleProject($currentUser, $arguments['project_id'] ?? '');
        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $logsTable = TableRegistry::getTableLocator()->get('Projects.ProjectsLogs');
        $log = $logsTable->newEntity([
            'project_id' => $project->id,
            'user_id' => $currentUser->get('id'),
            'descript' => $arguments['descript'] ?? '',
        ]);

        if (!$currentUser->can('edit', $log)) {
            $event->setResult(['error' => 'You are not authorized to add logs to this project.']);

            return;
        }

        if (!$log->getErrors() && $logsTable->save($log)) {
            $event->setResult(['id' => $log->id]);
        } else {
            $event->setResult(['error' => 'Failed to save log entry.', 'errors' => $log->getErrors()]);
        }
    }

    /**
     * Execute Projects.log_workhours tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeLogWorkhours(Event $event, array $arguments, mixed $currentUser): void
    {
        $project = $this->loadAccessibleProject($currentUser, $arguments['project_id'] ?? '');
        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $duration = (int)($arguments['duration'] ?? 0);
        if ($duration <= 0) {
            $event->setResult(['error' => 'Duration must be a positive number of minutes.']);

            return;
        }

        $workhoursTable = TableRegistry::getTableLocator()->get('Projects.ProjectsWorkhours');

        /** @var \Projects\Model\Entity\ProjectsWorkhour $workhour */
        $workhour = $workhoursTable->newEntity([
            'project_id' => $project->id,
            'user_id' => $currentUser->get('id'),
            'duration' => $duration * 60, // convert minutes to seconds
            'descript' => $arguments['descript'] ?? null,
            'started' => !empty($arguments['started']) ? $arguments['started'] : DateTime::now(),
        ]);

        if (!$currentUser->can('edit', $workhour)) {
            $event->setResult(['error' => 'You are not authorized to log hours to this project.']);

            return;
        }

        if (!$workhour->getErrors() && $workhoursTable->save($workhour)) {
            $event->setResult(['id' => $workhour->id, 'duration' => $workhour->duration]);
        } else {
            $event->setResult(['error' => 'Failed to save workhours.', 'errors' => $workhour->getErrors()]);
        }
    }

    /**
     * Execute Projects.create_milestone tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeCreateMilestone(Event $event, array $arguments, mixed $currentUser): void
    {
        $project = $this->loadAccessibleProject($currentUser, $arguments['project_id'] ?? '');
        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $milestonesTable = TableRegistry::getTableLocator()->get('Projects.ProjectsMilestones');

        /** @var \Projects\Model\Entity\ProjectsMilestone $milestone */
        $milestone = $milestonesTable->newEntity([
            'project_id' => $project->id,
            'user_id' => $currentUser->get('id'),
            'title' => $arguments['title'] ?? '',
            'date_due' => $arguments['date_due'] ?? null,
        ]);

        if (!$currentUser->can('edit', $milestone)) {
            $event->setResult(['error' => 'You are not authorized to create milestones in this project.']);

            return;
        }

        if (!$milestone->getErrors() && $milestonesTable->save($milestone)) {
            $event->setResult(['id' => $milestone->id, 'title' => $milestone->title]);
        } else {
            $event->setResult(['error' => 'Failed to create milestone.', 'errors' => $milestone->getErrors()]);
        }
    }

    /**
     * Execute Projects.get_project_logs tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetProjectLogs(Event $event, array $arguments, mixed $currentUser): void
    {
        $project = $this->loadAccessibleProject($currentUser, $arguments['project_id'] ?? '');
        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $logs = TableRegistry::getTableLocator()->get('Projects.ProjectsLogs')
            ->find()
            ->select(['ProjectsLogs.id', 'ProjectsLogs.project_id', 'ProjectsLogs.user_id',
                'ProjectsLogs.descript', 'ProjectsLogs.created'])
            ->contain(['Users'])
            ->where(['ProjectsLogs.project_id' => $project->id])
            ->orderBy(['ProjectsLogs.created' => 'DESC'])
            ->limit(50)
            ->all()
            ->toArray();

        $event->setResult($logs);
    }

    /**
     * Execute Projects.get_project_users tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetProjectUsers(Event $event, array $arguments, mixed $currentUser): void
    {
        $project = $this->loadAccessibleProject($currentUser, $arguments['project_id'] ?? '');
        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $users = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers')
            ->find()
            ->select(['ProjectsUsers.id', 'ProjectsUsers.project_id', 'ProjectsUsers.user_id'])
            ->contain(['Users'])
            ->where(['ProjectsUsers.project_id' => $project->id])
            ->orderBy(['ProjectsUsers.user_id' => 'ASC'])
            ->all()
            ->toArray();

        $event->setResult($users);
    }

    /**
     * Execute Projects.get_project_documents tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetProjectDocuments(Event $event, array $arguments, mixed $currentUser): void
    {
        $project = $this->loadAccessibleProject($currentUser, $arguments['project_id'] ?? '');
        if (!$project) {
            $event->setResult(['error' => 'Project not found or access denied.']);

            return;
        }

        $result = [
            'invoices' => [],
            'documents' => [],
            'travel_orders' => [],
        ];

        $invoices = TableRegistry::getTableLocator()->get('Documents.Invoices')
            ->find()
            ->select(['Invoices.id', 'Invoices.no', 'Invoices.title', 'Invoices.dat_issue'])
            ->where(['Invoices.project_id' => $project->id])
            ->orderBy(['Invoices.dat_issue' => 'DESC'])
            ->limit(50)
            ->all()
            ->toArray();
        foreach ($invoices as $invoice) {
            $invoice->view_url = $this->documentsViewUrl('Invoices', (string)$invoice->id);
        }
        $result['invoices'] = $invoices;

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents')
            ->find()
            ->select(['Documents.id', 'Documents.no', 'Documents.title', 'Documents.dat_issue'])
            ->where(['Documents.project_id' => $project->id])
            ->orderBy(['Documents.dat_issue' => 'DESC'])
            ->limit(50)
            ->all()
            ->toArray();
        foreach ($documents as $document) {
            $document->view_url = $this->documentsViewUrl('Documents', (string)$document->id);
        }
        $result['documents'] = $documents;

        $travelOrders = TableRegistry::getTableLocator()->get('Documents.TravelOrders')
            ->find()
            ->select(['TravelOrders.id', 'TravelOrders.no', 'TravelOrders.title',
                'TravelOrders.dat_task', 'TravelOrders.status', 'TravelOrders.total'])
            ->where(['TravelOrders.project_id' => $project->id])
            ->orderBy(['TravelOrders.dat_task' => 'DESC'])
            ->limit(50)
            ->all()
            ->toArray();
        foreach ($travelOrders as $travelOrder) {
            $travelOrder->view_url = $this->documentsViewUrl('TravelOrders', (string)$travelOrder->id);
        }
        $result['travel_orders'] = $travelOrders;

        $event->setResult($result);
    }

    /**
     * Load a project accessible to the current user via authorization scope.
     *
     * Accepts a UUID or falls back to a case-insensitive title / number search
     * so the model can pass a project name when a UUID is not available.
     *
     * @param mixed $currentUser Current user with applyScope.
     * @param string $projectId Project UUID, number, or title.
     * @return \Projects\Model\Entity\Project|null
     */
    private function loadAccessibleProject(mixed $currentUser, string $projectId): mixed
    {
        $projectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        $isUuid = (bool)preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $projectId,
        );

        if ($isUuid) {
            return $currentUser->applyScope('index', $projectsTable->find())
                ->where(['Projects.id' => $projectId])
                ->first();
        }

        // Fall back to title or project number search when the model passes a name.
        return $currentUser->applyScope('index', $projectsTable->find())
            ->where([
                'OR' => [
                    ['Projects.title LIKE' => $projectId],
                    ['Projects.no LIKE' => $projectId],
                ],
            ])
            ->first();
    }

    /**
     * Build a Documents plugin view URL for AI responses.
     *
     * @param string $controller Controller name in Documents plugin.
     * @param string $id Entity UUID.
     * @return string
     */
    private function documentsViewUrl(string $controller, string $id): string
    {
        try {
            return Router::url([
                'plugin' => 'Documents', 'controller' => $controller, 'action' => 'view', $id,
            ], true);
        } catch (MissingRouteException) {
            return '/documents/' . strtolower($controller) . '/view/' . $id;
        }
    }
}
