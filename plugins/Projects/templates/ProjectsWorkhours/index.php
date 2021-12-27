<?php

$pageTitle = __d('projects', 'Workhours');
if (!empty($project)) {
    $pageTitle = sprintf(
        '<div class="small">%1$s</div>%2$s',
        $this->Html->link((string)$project, [
            'controller' => 'Projects',
            'action' => 'view',
            $project->id,
            '?' => ['tab' => 'workhours']
        ]),
        __d('projects', 'Workhours'),
    );
}

$tableIndex = [
    'title_for_layout' => $pageTitle,
    'menu' => [
        'add' => [
            'title' => __d('projects', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'Projects',
                'controller' => 'ProjectsWorkhours',
                'action' => 'edit',
                '?' => ['project' => $project->id ?: null],
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0,
        ],
        'head' => ['rows' => [['columns' => [
            'user' => __d('projects', 'User'),
            'started' => __d('projects', 'Started'),
            'duration' => __d('projects', 'Duration'),
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

echo $this->Lil->index($tableIndex, 'Projects.ProjectsWorkhours.index');
