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
            'composite' => [
                'title' => __d('lil_projects', 'Add Composite'),
                'visible' => true,
                'url' => [
                    'controller' => 'ProjectsComposites',
                    'action' => 'add',
                    '?' => ['project' => $project->id],
                ],
                'params' => ['id' => 'add-projects-composite'],
            ],
        ],
        'entity' => $project,
        'panels' => [
            //'logo' => sprintf(
            //    '<div id="project-logo">%1$s</div>',
            //    $this->Html->image(['action' => 'picture', $project->id])
            //),
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
            'tabs' => ['lines' => [
                'pre' => '<div class="row view-panel"><div class="col s12"><ul class="tabs">',
                'logs' => '<li class="tab col"><a href="#tabc_logs">' . __d('lil_projects', 'Logs') . '</a></li>',
                'composites' => sprintf('<li class="tab col"><a href="#tabc_composites"%s>' . __d('lil_projects', 'Composites') . '</a></li>',
                $this->getRequest()->getQuery('tab') == 'composites' ? ' class="active"' : ''),
                'post' => '</ul></div>',
            ]],
            'logs' => [
                'params' => ['id' => 'projects-logs'],
                'table' => [
                    'pre' => '<div id="tabc_logs" class="col s12">',
                    'post' => '</div>',
                    'params' => ['class' => 'striped', 'id' => 'projects-logs'],
                    'body' => ['rows' => []],
                ],
            ],
            'composites' => [
                'params' => ['id' => 'projects-composites'],
                'table' => [
                    'pre' => '<div id="tabc_composites" class="col s12">',
                    'post' => '</div>',
                    'params' => ['class' => 'striped', 'id' => 'projects-composites'],
                    'body' => ['rows' => []],
                ],
            ],
            'tabs_end' => '</div>',
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
        //unset($projectView['panels']['logs']['table']);
        //$projectView['panels']['logs']['lines'][] = '<i>' . __d('lil_projects', 'No logs created.') . '</i>';
    }
    foreach ($logs as $log) {
        $projectView['panels']['logs']['table']['body']['rows'][] = ['columns' => [
            'user' => h($users[$log->user_id]->name),
            'descript' =>
                sprintf(
                    '<div class="logs-header">%2$s %1$s</div>',
                    $this->Time->i18nFormat($log->created),
                    $this->Html->link(__d('lil_projects', 'delete'), ['controller' => 'ProjectsLogs', 'action' => 'delete', $log->id])
                ) .
                $this->Lil->autop($log->descript),
        ]];
    }

    foreach ($composites as $composite) {
        $projectView['panels']['composites']['table']['body']['rows'][] = ['columns' => [
            'no' => h($composite->no),
            'title' => h($composite->title),
            'actions' =>  [
                'params' => ['class' => 'actions'],
                'html' => $this->Html->link(
                    '<i class="material-icons chevron">list</i>',
                    ['controller' => 'ProjectsComposites', 'action' => 'view', $composite->id],
                    ['escape' => false, 'class' => 'btn btn-small btn-floating waves-effect waves-light waves-circle']
                ) . ' ' .
                (!$this->getCurrentUser()->hasRole('editor') ? '' : (
                    $this->Lil->editLink(['controller' => 'ProjectsComposites', 'action' => 'edit', $composite->id]) . ' ' .
                    $this->Lil->deleteLink(['controller' => 'ProjectsComposites', 'action' => 'delete', $composite->id])
                )),
            ]
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
        $("#add-projects-composite").each(function() {
            $(this).modalPopup({
                title: "<?= __d('lil_projects', 'Add Composite') ?>",
                onOpen: function(popup) { $("#projects-composites-no", popup).focus(); }
            });
        });
    });
</script>
