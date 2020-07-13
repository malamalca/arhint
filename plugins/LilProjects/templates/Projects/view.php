<?php
    $projectView = [
        'title_for_layout' => '<span class="small">' . $project->no . ' </span><br />' . $project->title,
        'menu' => [
            'edit' => [
                'title' => __d('lil_projects', 'Edit'),
                'visible' => true,
                'url' => [
                    'action' => 'edit',
                    $project->id,
                ],
            ],
            'delete' => [
                'title' => __d('lil_projects', 'Delete'),
                'visible' => true,
                'url' => [
                    'action' => 'delete',
                    $project->id,
                ],
                'params' => [
                    'confirm' => __d('lil_projects', 'Are you sure you want to delete this project?'),
                ],
            ],
            'log' => [
                'title' => __d('lil_projects', 'Add Log'),
                'visible' => true,
                'url' => [
                    'controller' => 'ProjectsLogs',
                    'action' => 'add',
                    '?' => ['project' => $project->id],
                ],
                'params' => ['id' => 'add-projects-log'],
            ],
        ],
        'entity' => $project,
        'panels' => [
            'logo' => sprintf(
                '<div id="project-logo">%1$s</div>',
                $this->Html->image(['action' => 'picture', $project->id])
            ),
            'properties' => [
                'lines' => [
                    'status' => [
                        'label' => __d('lil_projects', 'Status') . ':',
                        'text' => empty($project->status_id) ? '' : ('<div class="chip z-depth-1">' . h($projectsStatuses[$project->status_id]) . '</div>'),
                    ],
                    'work_duration' => [
                        'label' => __d('lil_projects', 'Work Duration') . ':',
                        'text' => $this->Html->link($this->Arhint->duration($workDuration), [
                            'controller' => 'ProjectsWorkhours',
                            'action' => 'index',
                            '?' => ['project' => $project->id],
                        ]),
                    ],
                ],
            ],
            'logs_title' => '<h3>' . __d('lil_projects', 'Logs') . '</h3>',
            'logs' => [
                'params' => ['id' => 'projects-logs'],
                'table' => [
                    'params' => ['class' => 'striped', 'id' => 'projects-logs'],
                    'body' => ['rows' => []],
                ],
            ],
        ],
    ];

    /*$projectView['panels']['logs']['table']['body']['rows'][] = ['columns' => [
        h($this->getCurrentUser()->get('name')),
        $this->Form->create(null, ['url' => ['controller' => 'ProjectsLogs', 'action' => 'add', '?' => ['project' => $project->id]], 'id' => 'add-log']) .
        $this->Form->textarea('descript', ['rows' => 2]) .
        $this->Form->button(__d('lil_projects', 'Save'), ['id' => 'submit-logs-btn']) .
        $this->Form->end()
    ]];*/

    if ($logs->count() == 0) {
        $projectView['panels']['logs'] = '<i>' . __d('lil_projects', 'No logs created.') . '</i>';
    }
    foreach ($logs as $log) {
        $projectView['panels']['logs']['table']['body']['rows'][] = ['columns' => [
            'user' => h($log->user->name),
            'descript' =>
                sprintf(
                    '<div class="logs-header">%2$s %1$s</div>',
                    $this->Time->i18nFormat($log->created),
                    $this->Html->link(__d('lil_projects', 'delete'), ['controller' => 'ProjectsLogs', 'action' => 'delete', $log->id])
                ) .
                $this->Lil->autop($log->descript),
        ]];
    }

    echo $this->Lil->panels($projectView, 'LilProjects.Projects.view');
    ?>
<script type="text/javascript">
    $(document).ready(function() {
        $("#add-projects-log").each(function() {
            $(this).modalPopup({
                title: "<?= __d('lil_projects', 'Add Log') ?>",
                onOpen: function(popup) { $("#projects-logs-descript", popup).focus(); }
            });
        });
    });
</script>
