<?php
    $projectView = [
        'title_for_layout' => $project->no . ' - ' . $project->title,
        'entity' => $project,
        'panels' => [
            'logo' => sprintf('<h1>%1$s</h1>', $this->Html->link($project->title, ['action' => 'view', $project->id])),
            'logs' => [
                'params' => ['id' => 'projects-logs'],
                'table' => [
                    'params' => ['class' => 'index-static', 'id' => 'projects-logs'],
                    'body' => ['rows' => []],
                ],
            ],
        ],
    ];

    foreach ($logs as $log) {
        $projectView['panels']['logs']['table']['body']['rows'][] = ['columns' => [
            'user' => h($log->user->name),
            'descript' =>
                sprintf('<div class="logs-header">%s</div>', $this->Time->i18nFormat($log->created)) .
                $this->Lil->autop($log->descript),
        ]];
    }

    $projectView['panels']['logs']['table']['body']['rows'][] = ['columns' => [
        h($currentUser['name']),
        $this->Form->create(null, ['url' => ['controller' => 'ProjectsLogs', 'action' => 'add', 'project' => $project->id], 'id' => 'add-log']) .
        $this->Form->control('descript', ['type' => 'textarea', 'label' => false, 'rows' => 2]) .
        $this->Form->button(__d('lil_projects', 'Save'), ['id' => 'submit-logs-btn']) .
        $this->Form->end(),
    ]];

    echo $this->Lil->panels($projectView, 'LilProjects.Projects.map_popup');
