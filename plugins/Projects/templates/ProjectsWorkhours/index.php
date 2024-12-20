<?php

$pageTitle = __d('projects', 'Workhours');
if (!empty($filter['project'])) {
    $pageTitle = sprintf(
        '<div class="small">%1$s</div>%2$s',
        $this->Html->link((string)$projects[$filter['project']], [
            'controller' => 'Projects',
            'action' => 'view',
            $filter['project'],
            '?' => ['tab' => 'workhours']
        ]),
        __d('projects', 'Workhours'),
    );
}

// FILTER by project
$activeProject =  $filter['project'] ?? null;
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
$activeUser =  $filter['user'] ?? null;
if ($this->getCurrentUser()->hasRole('admin')) {
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
} else {
    $usersLink = __d('projects', 'Me');
    $popupUsers = '';
}


$pageTitle = __d('projects', 'Workhours for {0} by {1}', $projectLink, $usersLink);

$tableIndex = [
    'title_for_layout' => $pageTitle,
    'actions' => ['lines' => [$popupProjects, $popupUsers]],
    'menu' => [
        'add' => empty($filter['project']) ? null : [
            'title' => __d('projects', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'Projects',
                'controller' => 'ProjectsWorkhours',
                'action' => 'edit',
                '?' => ['project' => $filter['project'] ?: null],
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0,
        ],
        'head' => ['rows' => [['columns' => [
            'user' => __d('projects', 'Descript'),
            'date' =>  [
                'params' => ['class' => 'center-align'],
                'html' => $this->Paginator->sort('started', __d('projects', 'Started')),
            ],
            'started' => [
                'params' => ['class' => 'center-align'],
                'html' => __d('projects', 'Started'),
            ],
            'duration' => [
                'params' => ['class' => 'center-align'],
                'html' => __d('projects', 'Duration'),
            ],
            'confirmed' => [
                'params' => ['class' => 'center-align'],
                'html' => __d('projects', ''),
            ],
            'actions' => [],
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'sum' => [
                'params' => ['colspan' => '4', 'class' => 'right-align'],
                'html' => __d('projects', 'Total duration') . ':',
            ],
            'total' => [
                'params' => ['class' => 'center-align'],
                'html' => $this->Arhint->duration($totalDuration ?? 0),
            ],
            'actions' => [],
        ]]]],
    ],
];

foreach ($projectsWorkhours as $workhour) {
    $descriptData = [];
    if (empty($filter['project'])) {
        $descriptData[] = h((string)$workhour->project);
    }
    if (empty($filter['user']) && count($users) > 1) {
        $descriptData[] = h((string)$workhour->user->name);
    }

    $canEdit = $this->getCurrentUser()->hasRole('admin') || 
            (empty($workhour->dat_confirmed) && ($this->getCurrentUser()->id == $workhour->user_id));

    $descript = '<div class="small">' . implode(' :: ', $descriptData) . '</div>';
    $descript .= h($workhour->descript);
    $tableIndex['table']['body']['rows'][]['columns'] = [
        'user' => $descript,
        'date' => [
            'params' => ['class' => 'center-align'],
            'html' => $this->Arhint->calendarDay($workhour->started),
        ],
        'started' => [
            'params' => ['class' => 'center-align'],
            'html' => $this->Arhint->timePanel($workhour->started),
        ],
        'duration' => [
            'params' => ['class' => 'center-align'],
            'html' => $this->Arhint->duration($workhour->duration),
        ],
        'confirmed' => [
            'params' => ['class' => 'center-align'],
            'html' => empty($workhour->dat_confirmed) ? '&nbsp;' : '<i class="material-icons small red-text text-lighten-2">beenhere</i>',
        ],
        'actions' => [
            'parameters' => ['class' => 'right-align'],
            'html' => !$canEdit ? '' : $this->Lil->editLink($workhour->id) . ' ' . $this->Lil->deleteLink($workhour->id),
        ],
    ];
}

echo $this->Lil->index($tableIndex, 'Projects.ProjectsWorkhours.index');
