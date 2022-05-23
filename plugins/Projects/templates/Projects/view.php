<?php

use Cake\Routing\Router;
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
        'workhour' => [
            'title' => __d('projects', 'Add Workhour'),
            'visible' => true,
            'url' => [
                'controller' => 'ProjectsWorkhours',
                'action' => 'edit',
                '?' => ['project' => $project->id],
            ],
            'params' => ['id' => 'add-projects-workhour'],
        ],
        'users' => [
            'title' => __d('projects', 'Add User'),
            'visible' => true,
            'url' => [
                'controller' => 'Projects',
                'action' => 'user',
                $project->id,
            ],
            'params' => ['id' => 'add-projects-user'],
        ],
    ],
    'entity' => $project,
    'panels' => [
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
            'workhours' => sprintf(
                '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                $this->Url->build([$project->id, '?' => ['tab' => 'workhours']]),
                __d('projects', 'Workhours'),
                $this->getRequest()->getQuery('tab') == 'workhours' ? ' class="active"' : ''
            ),
            'users' => !$this->getCurrentUser()->hasRole('admin') ? null : sprintf(
                '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                $this->Url->build([$project->id, '?' => ['tab' => 'users']]),
                __d('projects', 'Users'),
                $this->getRequest()->getQuery('tab') == 'users' ? ' class="active"' : ''
            ),
            'post' => '</ul></div>',
        ]],
        'tabs_end' => '</div>',
    ],
];

$activeTab = $this->getRequest()->getQuery('tab', 'logs');
$this->set('tab', $activeTab);

switch ($activeTab) {
    case 'logs':
        if ($logs->isEmpty()) {
            $table = [
                'params' => ['id' => 'projects-logs'],
                'lines' => [
                    __d('projects', 'No logs found.'),
                ],
            ];
        } else {
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
                        $log->descript .
                        strip_tags($log->descript, ['a', 'strong', 'em', 'span', 'sub', 'sup', 'table', 'tr', 'td', 'p', 'pre', 'blockquote', 'img']),
                ]];
            }
        }
        $this->Lil->insertIntoArray($projectView['panels'], ['logs' => $table], ['before' => 'tabs_end']);
        break;
    case 'workhours':
        // invoices tab panel
        $workhoursPanel = [
            'workhours_table' => '<div id="tab-content-workhours"></div>',
        ];

        $this->Lil->insertIntoArray($projectView['panels'], $workhoursPanel);

        $sourceRequest = Router::reverseToArray($this->getRequest());
        unset($sourceRequest['?']['page']);
        unset($sourceRequest['?']['sort']);
        unset($sourceRequest['?']['direction']);

        $url = Router::normalize($sourceRequest);
        $params = [
            'source' => $url,
            'page' => $this->getRequest()->getQuery('page'),
            'sort' => $this->getRequest()->getQuery('sort'),
            'direction' => $this->getRequest()->getQuery('direction'),
        ];

        $url = Router::url(['plugin' => 'Projects', 'controller' => 'ProjectsWorkhours', 'action' => 'list', '_ext' => 'aht', '?' => $params]);
        $this->Lil->jsReady('$.get("' . $url . '", function(data) { $("#tab-content-workhours").html(data); });');

        break;
    case 'users':
        if (!$this->getCurrentUser()->hasRole('admin')) {
            break;
        }
        if (count($project->users) == 0) {
            $table = [
                'params' => ['id' => 'projects-users'],
                'lines' => [
                    __d('projects', 'No users found.'),
                ],
            ];
        } else {
            $table = [
                'params' => ['id' => 'projects-users'],
                'table' => [
                    'pre' => '<div id="tabc_users" class="col s12">',
                    'post' => '</div>',
                    'params' => ['class' => 'striped', 'id' => 'list-projects-users'],
                    'body' => ['rows' => []],
                ],
            ];
            foreach ($project->users as $user) {
                $table['table']['body']['rows'][] = ['columns' => [
                    'user' => h($user->name),
                    'actions' => $this->Lil->deleteLink(['action' => 'deleteUser', $project->id, $user->id]),
                ]];
            }
        }
        $this->Lil->insertIntoArray($projectView['panels'], ['users' => $table], ['before' => 'tabs_end']);
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

        $("#add-projects-workhour").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Add Workhour') ?>",
                onOpen: function(popup) { $("#projects-work-descript", popup).focus(); }
            });
        });
    });
</script>
