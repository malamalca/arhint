<?php
    use Projects\Lib\ProjectsFuncs;

    $projectView = [
        'title_for_layout' =>
        $this->Html->image(
            'data:image/png;base64, ' . base64_encode(ProjectsFuncs::thumb($project, 80)),
            ['style' => 'float: left; margin-right: 20px;', 'class' => 'project-avatar', 'quote' => false]
        ).
            '<div><div class="small">' . $project->no . ' </div>' . $project->title . '</div>',
        'menu' => [
            'edit' => [
                'title' => __d('projects', 'Edit'),
                'visible' => $this->getCurrentUser()->hasRole('admin'),
                'url' => [
                    'action' => 'edit',
                    $project->id,
                ],
            ],
            'delete' => [
                'title' => __d('projects', 'Delete'),
                'visible' => $this->getCurrentUser()->hasRole('admin'),
                'url' => [
                    'action' => 'delete',
                    $project->id,
                ],
                'params' => [
                    'confirm' => __d('projects', 'Are you sure you want to delete this project?'),
                ],
            ],
            'log' => [
                'title' => __d('projects', 'Add Log'),
                'visible' => true,
                'url' => [
                    'controller' => 'ProjectsLogs',
                    'action' => 'edit',
                    '?' => ['project' => $project->id],
                ],
                'params' => ['id' => 'add-projects-log'],
            ],
            'composite' => [
                'title' => __d('projects', 'Add Composite'),
                'visible' => true,
                'url' => [
                    'controller' => 'ProjectsComposites',
                    'action' => 'edit',
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
                        'label' => __d('projects', 'Status') . ':',
                        'text' => empty($project->status_id) ? '' : ('<div class="chip z-depth-1">' . h($projectsStatuses[$project->status_id]) . '</div>'),
                    ],
                    'work_duration' => [
                        'label' => __d('projects', 'Work Duration') . ':',
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
                'logs' => sprintf(
                    '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                    $this->Url->build([$project->id, '?' => ['tab' => 'logs']]),
                    __d('projects', 'Logs'),
                    $this->getRequest()->getQuery('tab') == 'logs' ? ' class="active"' : ''
                ),
                'composites' => sprintf(
                    '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                    $this->Url->build([$project->id, '?' => ['tab' => 'composites']]),
                    __d('projects', 'Composites'),
                    $this->getRequest()->getQuery('tab') == 'composites' ? ' class="active"' : ''
                ),
                'post' => '</ul></div>',
            ]],
            'tabs_end' => '</div>',
        ],
    ];

    /*$projectView['panels']['logs']['table']['body']['rows'][] = ['columns' => [
        h($this->getCurrentUser()->get('name')),
        $this->Form->create(null, ['url' => ['controller' => 'ProjectsLogs', 'action' => 'edit', '?' => ['project' => $project->id]], 'id' => 'add-log']) .
        $this->Form->textarea('descript', ['rows' => 2]) .
        $this->Form->button(__d('projects', 'Save'), ['id' => 'submit-logs-btn']) .
        $this->Form->end()
    ]];*/

    $activeTab = $this->getRequest()->getQuery('tab', 'logs');
    switch ($activeTab) {
        case 'logs':
            $table = [
                'params' => ['id' => 'projects-logs'],
                'table' => [
                    'pre' => '<div id="tabc_logs" class="col s12">',
                    'post' => '</div>',
                    'params' => ['class' => 'striped', 'id' => 'projects-logs'],
                    'body' => ['rows' => []],
                ],
            ];
            foreach ($logs as $log) {
                $table['table']['body']['rows'][] = ['columns' => [
                    'user' => h($users[$log->user_id]->name),
                    'descript' =>
                        sprintf(
                            '<div class="logs-header">%2$s %1$s</div>',
                            $this->Time->i18nFormat($log->created),
                            $this->getCurrentUser()->hasRole('admin') || ($this->getCurrentUser()->id == $log->user_id) ?
                                $this->Html->link(
                                    __d('projects', 'delete'),
                                    ['controller' => 'ProjectsLogs', 'action' => 'delete', $log->id]
                                )
                                :
                                ''
                        ) .
                        $this->Lil->autop($log->descript),
                ]];
            }
            $this->Lil->insertIntoArray($projectView['panels'], ['logs' => $table], ['before' => 'tabs_end']);
            break;
        case 'composites':
            $table = [
                'params' => ['id' => 'projects-composites'],
                'table' => [
                    'pre' => '<div id="tabc_composites" class="col s12">',
                    'post' => '</div>',
                    'params' => ['class' => 'striped', 'id' => 'projects-composites', 'style' => 'max-width: 700px'],
                    'body' => ['rows' => []],
                ],
            ];
            foreach ($composites as $composite) {
                $table['table']['body']['rows'][] = ['columns' => [
                    'no' => h($composite->no),
                    'title' => $this->Html->link($composite->title, ['controller' => 'ProjectsComposites', 'action' => 'view', $composite->id]),
                    'actions' =>  [
                        'params' => ['class' => 'actions'],
                        'html' =>
                            (!$this->getCurrentUser()->hasRole('editor') ? '' : (
                                $this->Lil->editLink(
                                    ['controller' => 'ProjectsComposites', 'action' => 'edit', $composite->id],
                                    ['class' => 'edit-projects-composite'],
                                ) . ' ' .
                                $this->Lil->deleteLink(['controller' => 'ProjectsComposites', 'action' => 'delete', $composite->id])
                            )),
                    ]
                ]];
            }
            $this->Lil->insertIntoArray(
                $projectView['panels'],
                [
                    'composites' => $table,
                    'export' => $this->Html->link(
                        __d('projects', 'Export to Word'),
                        [
                            'controller' => 'ProjectsComposites',
                            'action' => 'export',
                            $project->id,
                        ],
                        ['class' => 'btn']
                    )
                ],
                ['before' => 'tabs_end']
            );
            break;
    }

    echo $this->Lil->panels($projectView, 'Projects.Projects.view');
    ?>
<script type="text/javascript">
    $(document).ready(function() {
        $("#add-projects-log").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Add Log') ?>",
                onOpen: function(popup) { $("#projects-logs-descript", popup).focus(); }
            });
        });
        $("#add-projects-composite").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Add Composite') ?>",
                onOpen: function(popup) { $("#projects-composites-no", popup).focus(); }
            });
        });
        $(".edit-projects-composite").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Edit Composite') ?>",
                onOpen: function(popup) { $("#projects-composites-no", popup).focus(); }
            });
        });
    });
</script>
