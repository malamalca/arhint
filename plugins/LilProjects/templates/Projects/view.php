<?php
    $projectView = [
        'title_for_layout' => $project->no . ' - ' . $project->title,
        'menu' => [
            'edit' => [
                'title' => __d('lil_projects', 'Edit'),
                'visible' => true,
                'url' => [
                    'action' => 'edit',
                    $project->id,
                ]
            ],
            'delete' => [
                'title' => __d('lil_projects', 'Delete'),
                'visible' => true,
                'url' => [
                    'action' => 'delete',
                    $project->id,
                ],
                'params' => [
                    'confirm' => __d('lil_projects', 'Are you sure you want to delete this project?')
                ]
            ],
            'log' => [
                'title' => __d('lil_projects', 'Add Log'),
                'visible' => true,
                'url' => [
                    'controller' => 'ProjectsLogs',
                    'action' => 'add',
                    $project->id,
                    'project' => $project->id
                ]
            ],
        ],
        'entity' => $project,
        'panels' => [
            'logo' => sprintf(
                '<div id="project-logo">%1$s</div>',
                $this->Html->image(['action' => 'picture', $project->id])
            ),
            'logs' => [
                'params' => ['id' => 'projects-logs'],
                'table' => [
                    'params' => ['class' => 'index-static', 'id' => 'projects-logs'],
                    'body' => ['rows' => []]
                ]
            ]
        ]
    ];

    $projectView['panels']['logs']['table']['body']['rows'][] = ['columns' => [
        h($this->getCurrentUser()->get('title')),
        $this->Form->create(null, ['url' => ['controller' => 'ProjectsLogs', 'action' => 'add', '?' => ['project' => $project->id]], 'id' => 'add-log']) .
        $this->Form->control('descript', ['type' => 'textarea', 'label' => false, 'rows' => 2]) .
        $this->Form->button(__d('lil_projects', 'Save'), ['id' => 'submit-logs-btn']) .
        $this->Form->end()
    ]];

    foreach ($logs as $log) {
        $projectView['panels']['logs']['table']['body']['rows'][] = ['columns' => [
            'user' => h($log->user->name),
            'descript' =>
                sprintf(
                    '<div class="logs-header">%2$s %1$s</div>',
                    $this->Time->i18nFormat($log->created),
                    $this->Html->link(__d('lil_projects', 'delete'), ['controller' => 'ProjectsLogs', 'action' => 'delete', $log->id])
                ) .
                $this->Lil->autop($log->descript)
        ]];
    }

    echo $this->Lil->panels($projectView, 'LilProjects.Projects.view');
