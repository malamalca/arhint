<?php

$tableIndex = [
    'title_for_layout' => __d('lil_projects', 'Workhours'),
    'menu' => [
        'add' => [
            'title' => __d('lil_projects', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'LilProjects',
                'controller' => 'ProjectsWorkhours',
                'action' => 'add'
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0
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
