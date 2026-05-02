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
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
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
                    'description' => 'Search term to filter projects by number or title.',
                ],
                'inactive' => [
                    'type' => 'boolean',
                    'description' => 'Include inactive projects. Defaults to false.',
                ],
            ],
            description: 'Searches accessible projects by number or title. Returns id, number, title, '
                . 'active state, milestone counts, and status.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.get_project',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the project to retrieve.'],
            ],
            description: 'Fetches full details of a single project including description, status, '
                . 'team members, and milestones with task counts.',
        ));

        $toolsList->append(new AITool(
            name: 'Projects.get_project_tasks',
            arguments: [
                'project_id' => ['type' => 'string', 'description' => 'UUID of the project.'],
                'status' => [
                    'type' => 'string',
                    'description' => 'Filter by status: "open" for unclosed tasks, "closed" for completed. '
                        . 'Omit for all.',
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
                'milestone_id' => ['type' => 'string', 'description' => 'UUID of the milestone to assign.'],
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

        $filter = [];
        if (!empty($arguments['search'])) {
            $filter['search'] = $arguments['search'];
        }
        if (!empty($arguments['inactive'])) {
            $filter['inactive'] = true;
        }
        $params = $projectsTable->filter($filter);

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

        $event->setResult($projects);
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

        $event->setResult($project);
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
     * Load a project accessible to the current user via authorization scope.
     *
     * @param mixed $currentUser Current user with applyScope.
     * @param string $projectId Project UUID.
     * @return \Projects\Model\Entity\Project|null
     */
    private function loadAccessibleProject(mixed $currentUser, string $projectId): mixed
    {
        $projectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $currentUser->applyScope('index', $projectsTable->find())
            ->where(['Projects.id' => $projectId])
            ->first();
    }
}
