<?php

$pageTitle = __d('projects', 'Tasks');
if (!empty($filter['project'])) {
    $pageTitle = sprintf(
        '<div class="small">%1$s</div>%2$s',
        $this->Html->link((string)$projects[$filter['project']], [
            'controller' => 'Projects',
            'action' => 'view',
            $filter['project'],
            '?' => ['tab' => 'milestones'],
        ]),
        __d('projects', 'Tasks'),
    );
}

// FILTER by project
$activeProject = $filter['project'] ?? null;
$projectLink = $this->Html->link(
    $projects[$activeProject] ?? __d('projects', 'All Projects'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-projects', 'data-target' => 'dropdown-projects']
);
$popupProjects = ['items' => [[
    'title' => __d('projects', 'All Projects'),
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['project' => null])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($projects as $project) {
    $popupProjects['items'][] = [
        'title' => $project->title,
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['project' => $project->id])],
        'active' => ($activeProject == $project->id),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupProjects = $this->Lil->popup('projects', $popupProjects, true);

// FILTER by project
$activeUser = $filter['user'] ?? null;
$usersLink = $this->Html->link(
    $users[$activeUser] ?? __d('projects', 'All Users'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-users', 'data-target' => 'dropdown-users']
);
$popupUsers = ['items' => [[
    'title' => __d('projects', 'All Users'),
    'active' => false,
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['user' => null])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($users as $user) {
    $popupUsers['items'][] = [
        'title' => $user->name,
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['user' => $user->id])],
        'active' => ($activeUser == $user->id),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupUsers = $this->Lil->popup('users', $popupUsers, true);

$pageTitle = __d('projects', 'Tasks for {0} by {1}', $projectLink, $usersLink);

$tableIndex = [
    'title_for_layout' => '<div class="small">' . (string)$projects[$filter['project']] . '</div>',
    'actions' => ['lines' => [$popupProjects, $popupUsers]],
    'menu' => [
        'add' => empty($filter['project']) ? null : [
            'title' => __d('projects', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'Projects',
                'controller' => 'ProjectsTasks',
                'action' => 'edit',
                '?' => ['project' => $filter['project'] ?: null],
            ],
        ],
    ],
    'pre' => '<div id="tasks-index">',
    'post' => '</div>',
    'panels' => [
        'search' => '<div id="tasks-search"><input name="q" id="query" />' .
            '<button class="btn btn-small tonal" id="btn-search"><i class="material-icons">search</i></button>' .
            '<button class="btn btn-small" id="btn-add"><i class="material-icons">add</i>New Task</button>' .
            '</div>',
        'filter' => '<div id="tasks-filter"><ul>' .
            '<li><a href="#" class="btn text">Author</a></li>' .
            '<li><a href="#" class="btn text">Milestone</a></li>' .
            '<li><a href="#" class="btn text">Newest</a></li>' .
            '</ul>' .
            '<div id="">Open 1  Closed 1</div>' .
            '</div>',
        'tasks' => [
            'params' => ['id' => 'tasks-list'],
            'lines' => [],
        ],
    ],
];

foreach ($projectsTasks as $task) {
    $canEdit = $this->getCurrentUser()->hasRole('admin');

    $tableIndex['panels']['tasks']['lines'][] = $this->element('Projects.task_row', [
        'task' => $task,
        'canEdit' => $canEdit,
    ]);
}

echo $this->Lil->panels($tableIndex, 'Projects.ProjectsTasks.index');
