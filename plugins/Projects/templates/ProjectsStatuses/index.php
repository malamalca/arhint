<?php

$tableIndex = [
    'title_for_layout' => __d('projects', 'Statuses'),
    'menu' => [
        'add' => [
            'title' => __d('projects', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'Projects',
                'controller' => 'ProjectsStatuses',
                'action' => 'edit',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0,
        ],
        'head' => ['rows' => [['columns' => [
            'descript' => __d('projects', 'Title'),
            'actions' => [],
        ]]]],
    ],
];

foreach ($projectsStatuses as $status) {
    $tableIndex['table']['body']['rows'][]['columns'] = [
        'descript' => $this->Html->link($status->title, ['action' => 'edit', $status->id]),
        'actions' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($status->id) . ' ' . $this->Lil->deleteLink($status->id),
        ],
    ];
}

echo $this->Lil->index($tableIndex, 'Projects.ProjectsStatuses.index');
