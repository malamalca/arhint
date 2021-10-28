<?php

$pageTitle = __d('lil_projects', 'Workhours');
if (!empty($project)) {
    $pageTitle = sprintf(
        '<div class="small">%1$s</div>%2$s',
        $this->Html->link((string)$project, [
            'controller' => 'Projects',
            'action' => 'view',
            $project->id,
            '?' => ['tab' => 'workhours']
        ]),
        __d('lil_projects', 'Workhours'),
    );
}

$tableIndex = [
    'title_for_layout' => $pageTitle,
    'menu' => [
        'add' => [
            'title' => __d('lil_projects', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'LilProjects',
                'controller' => 'ProjectsWorkhours',
                'action' => 'add',
                '?' => ['project' => $project->id ?: null],
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0,
        ],
        'head' => ['rows' => [['columns' => [
            'user' => __d('lil_projects', 'User'),
            'started' => __d('lil_projects', 'Started'),
            'duration' => __d('lil_projects', 'Duration'),
            'actions' => [],
        ]]]],
    ],
];

foreach ($projectsWorkhours as $workhour) {
    $tableIndex['table']['body']['rows'][]['columns'] = [
        'user' => h($workhour->user->name),
        'started' => (string)$workhour->started,
        'duration' => $this->Arhint->duration($workhour->duration),
        'actions' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($workhour->id) . ' ' . $this->Lil->deleteLink($workhour->id),
        ],
    ];
}

echo $this->Lil->index($tableIndex, 'LilProjects.ProjectsWorkhours.index');
