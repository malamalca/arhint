<?php

use Cake\Routing\Router;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Projects\Lib\KnowledgeBase\KbCalendar;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Job\KnowledgeBaseRegenerateJob;
use Projects\Lib\KnowledgeBase\KbRenderer;
use Projects\Lib\KnowledgeBase\KbSettings;
use Projects\Lib\ProjectsFuncs;

$converter = new GithubFlavoredMarkdownConverter([
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);

// Deadlines of the knowledge base can be added to the personal calendar of users who use the calendar.
$kbLinked = (new KbCalendar())->linksFor($project, $this->getCurrentUser());
$kbCalendarData = '';
if ($kbLinked !== null) {
    $this->Html->script('Projects.projects-kb-calendar.js', ['block' => 'script']);
    $kbCalendarData = ' data-calendar-url="' . h(Router::url([
        'plugin' => 'Projects', 'controller' => 'ProjectsKnowledge', 'action' => 'calendar', $project->id,
    ])) . '"'
        . ' data-text-add="' . h(__d('projects', 'Add to my calendar')) . '"'
        . ' data-text-remove="' . h(__d('projects', 'Remove from my calendar')) . '"'
        . ' data-text-error="' . h(__d('projects', 'Request failed. Please try again.')) . '"';
}

// Administrators can have the knowledge base made again from all the events of the project.
$kbRegenerate = '';
if ($this->getCurrentUser()->can('edit', $project)) {
    $kbRegenerate = KnowledgeBaseRegenerateJob::isPending((string)$project->id)
        ? '<div class="small" id="ProjectKbRegenerating">'
            . h(__d('projects', 'The knowledge base is being made again from all the events.')) . '</div>'
        : '<div class="small" id="ProjectKbRegenerate">' . $this->Form->postLink(
            __d('projects', 'Make the knowledge base again from all events'),
            ['plugin' => 'Projects', 'controller' => 'ProjectsKnowledge', 'action' => 'regenerate', $project->id],
            ['confirm' => __d(
                'projects',
                'The summary, warnings, deadlines and other sections will be made again from all the events of the project. The description, the notes and the changelog stay, and the current state can be returned to from the history. It can take a long time. Continue?',
            )],
        ) . '</div>';
}

// Conversation with the AI about the knowledge base; only for users who have the AI assistant set up.
$kbChat = null;
$aiConfig = $this->getCurrentUser()->getProperty('ai_assistant');
if (
    $aiConfig && !empty($aiConfig->provider) && $aiConfig->provider !== 'none'
    && KbSettings::companyAllowed((string)$project->owner_id)
) {
    $kbUrl = fn(string $action): string => Router::url([
        'plugin' => 'Projects', 'controller' => 'ProjectsKnowledge', 'action' => $action, $project->id,
    ]);
    $this->Html->script('Projects.projects-kb-chat.js', ['block' => 'script']);
    $kbChat = '<details id="ProjectKbChat" class="kb-chat"'
        . ' data-chat-url="' . h($kbUrl('chat')) . '"'
        . ' data-chat-status-url="' . h($kbUrl('chatStatus')) . '"'
        . ' data-clear-url="' . h($kbUrl('clearChat')) . '"'
        . ' data-undo-url="' . h($kbUrl('undo')) . '"'
        . ' data-worker-status-url="' . h(Router::url(['plugin' => false, 'controller' => 'Ai', 'action' => 'workerStatus'])) . '"'
        . ' data-text-undo="' . h(__d('projects', 'Undo')) . '"'
        . ' data-text-changed="' . h(__d('projects', 'Knowledge base changed')) . '"'
        . ' data-text-error="' . h(__d('projects', 'Request failed. Please try again.')) . '"'
        . ' data-text-worker="' . h(__d('projects', 'Queue worker is not running. Start it with: bin/cake queue worker')) . '"'
        . ' data-text-version="' . h(__d('projects', 'version {0}', '{0}')) . '">'
        . '<summary>' . h(__d('projects', 'Conversation with AI about the project')) . '</summary>'
        . '<div id="ProjectKbChatMessages" class="kb-chat-messages"></div>'
        . '<form id="ProjectKbChatForm">'
        . '<textarea id="ProjectKbChatInput" rows="2" placeholder="'
        . h(__d('projects', 'Ask about the project or tell the AI what to change in the knowledge base...'))
        . '"></textarea>'
        . '<div class="kb-chat-actions"><button type="submit" id="ProjectKbChatSend" class="btn filled">'
        . h(__d('projects', 'Send')) . '</button> '
        . '<a href="#" id="ProjectKbChatClear" class="btn-small">' . h(__d('projects', 'Clear conversation')) . '</a>'
        . '</div></form></details>';
}

$projectView = [
    'title_for_layout' => $this->Html->image(
        'data:image/png;base64, ' . base64_encode(ProjectsFuncs::thumb($project, 80)),
        ['style' => 'float: left; margin-right: 20px;', 'class' => 'project-avatar', 'quote' => false],
    ) . '<div><div class="small">' . $project->no . ' </div>' . $project->title . '</div>',
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
        'add' => [
            'title' => __d('projects', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'submenu' => [
                'authority' => [
                    'title' => __d('projects', 'Add authority'),
                    'visible' => $this->getCurrentUser()->hasRole('editor'),
                    'url' => [
                        'controller' => 'ProjectsAuthorities',
                        'action' => 'editCase',
                        '?' => ['project' => $project->id],
                    ],
                    'params' => ['id' => 'add-projects-authority'],
                ],
                'log' => [
                    'title' => __d('projects', 'Add Log'),
                    'visible' => $this->getCurrentUser()->hasRole('editor'),
                    'url' => [
                        'controller' => 'ProjectsLogs',
                        'action' => 'edit',
                        '?' => ['project' => $project->id],
                    ],
                    'params' => ['id' => 'add-projects-log'],
                ],
                'workhour' => [
                    'title' => __d('projects', 'Add Workhour'),
                    'visible' => $this->getCurrentUser()->hasRole('editor'),
                    'url' => [
                        'controller' => 'ProjectsWorkhours',
                        'action' => 'edit',
                        '?' => ['project' => $project->id],
                    ],
                    'params' => ['id' => 'add-projects-workhour'],
                ],
                'users' => [
                    'title' => __d('projects', 'Add User'),
                    'visible' => $this->getCurrentUser()->hasRole('admin'),
                    'url' => [
                        'controller' => 'Projects',
                        'action' => 'user',
                        $project->id,
                    ],
                    'params' => ['id' => 'add-projects-user'],
                ],
                'milestones' => [
                    'title' => __d('projects', 'Add Milestone'),
                    'visible' => $this->getCurrentUser()->hasRole('admin'),
                    'url' => [
                        'controller' => 'ProjectsMilestones',
                        'action' => 'edit',
                        '?' => ['project' => $project->id],
                    ],
                    'params' => ['id' => 'add-projects-milestone'],
                ],
            ],
        ],
    ],
    'entity' => $project,
    'panels' => [
        'properties' => [
            'lines' => [
                'status' => [
                    'label' => __d('projects', 'Status') . ':',
                    'text' => empty($project->status_id) ? '' : h($projectsStatuses[$project->status_id]),
                ],
            ],
        ],
        'descript' => empty($project->descript) && !$kbChat && $kbRegenerate === '' ? null : [
            'params' => ['id' => 'ProjectDescriptPanel'],
            'lines' => [
                0 => [
                    'text' => '<div class="small" id="ProjectKbHeader"'
                        . ((int)$project->kb_format === 1 ? '' : ' style="display: none;"') . '>'
                        . h(__d('projects', 'Knowledge base')) . ' · '
                        . '<span id="ProjectKbVersion">' . h(__d('projects', 'version {0}', $project->kb_version))
                        . '</span>'
                        . ' · <span id="ProjectKbModified">'
                        . ($project->modified ? h($project->modified->i18nFormat('dd.MM.yyyy HH:mm')) : '') . '</span>'
                        . ' · ' . $this->Html->link(
                            __d('projects', 'History'),
                            ['action' => 'history', $project->id],
                        ) . '</div>',
                ],
                1 => [
                    'text' => '<div id="ProjectKbContent"' . $kbCalendarData . '>' . (
                        (int)$project->kb_format === 1
                            ? (new KbRenderer())->render(KbDocument::parse((string)$project->descript), $kbLinked)
                            : (empty($project->descript) ? '' : (string)$converter->convert($project->descript))
                    ) . '</div>',
                ],
                2 => $kbRegenerate,
            ],
        ],
        'kb_chat' => !$kbChat ? null : [
            'lines' => [$kbChat],
        ],
        'tabs' => ['lines' => [
            'pre' => '<div class="row view-panel"><div class="col s12"><ul class="tabs">',
            'authorities' => sprintf(
                '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                $this->Url->build([$project->id, '?' => ['tab' => 'authorities']]),
                __d('projects', 'Procedures'),
                !$this->getRequest()->getQuery('tab') || $this->getRequest()->getQuery('tab') === 'authorities' ? ' class="active"' : '',
            ),
            'milestones' => sprintf(
                '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                $this->Url->build([$project->id, '?' => ['tab' => 'milestones']]),
                __d('projects', 'Milestones'),
                $this->getRequest()->getQuery('tab') == 'milestones' ? ' class="active"' : '',
            ),
            'logs' => sprintf(
                '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                $this->Url->build([$project->id, '?' => ['tab' => 'logs']]),
                __d('projects', 'Logs'),
                $this->getRequest()->getQuery('tab') == 'logs' ? ' class="active"' : '',
            ),
            'workhours' => sprintf(
                '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                $this->Url->build([$project->id, '?' => ['tab' => 'workhours']]),
                __d('projects', 'Workhours'),
                $this->getRequest()->getQuery('tab') == 'workhours' ? ' class="active"' : '',
            ),
            'users' => !$this->getCurrentUser()->hasRole('admin') ? null : sprintf(
                '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                $this->Url->build([$project->id, '?' => ['tab' => 'users']]),
                __d('projects', 'Users'),
                $this->getRequest()->getQuery('tab') == 'users' ? ' class="active"' : '',
            ),
            'post' => '</ul></div>',
        ]],
        'tabs_end' => '</div>',
    ],
];

$activeTab = $this->getRequest()->getQuery('tab', 'authorities');
$this->set('tab', $activeTab);

switch ($activeTab) {
    case 'authorities':
        $this->Lil->insertIntoArray($projectView['panels'], [
            'authorities' => [
                'lines' => [$this->element('Projects.authorities_list', [
                    'cases' => $authorityCases,
                    'summary' => $authoritySummary,
                    'reviews' => $authorityReviews,
                ])],
            ],
        ], ['before' => 'tabs_end']);
        break;
    case 'milestones':
        // milestones tab panel
        $milestonesPanel = [
            'milestones_list' => [
                'params' => ['id' => 'projects-milestones'],
                'lines' => [$this->Element('Projects.milestones_list', ['milestones' => $milestones, 'project' => $project])],
            ],
        ];
        $this->Lil->insertIntoArray($projectView['panels'], $milestonesPanel);

        break;
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
                                    ['controller' => 'ProjectsLogs', 'action' => 'delete', $log->id, '?' => ['redirect' => Router::url(null, true)]],
                                    ['confirm' => __d('projects', 'Are you sure you want to delete this log?')],
                                ) . ' ' .
                                $this->Html->link(
                                    __d('projects', 'edit'),
                                    ['controller' => 'ProjectsLogs', 'action' => 'edit', $log->id, '?' => ['redirect' => Router::url(null, true)]],
                                    ['class' => 'btn-edit-log'],
                                )
                                :
                                '',
                        ) .
                        //$log->descript
                        strip_tags($log->descript ?? '', ['a', 'strong', 'em', 'span', 'sub', 'sup', 'table', 'tr', 'td', 'p', 'pre', 'blockquote', 'img']),
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

        $("#add-projects-authority").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Add authority') ?>",
                processSubmit: true,
                onJson: function() { window.location.reload(); },
                onOpen: function(popup) { $("#authority-contact-title", popup).focus(); }
            });
        });

        $("#add-projects-workhour").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Add Workhour') ?>",
                onOpen: function(popup) { $("#projects-work-descript", popup).focus(); }
            });
        });

        $("#add-projects-milestone").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Add Milestone') ?>",
                onOpen: function(popup) { $("#projects-milestone-title", popup).focus(); }
            });
        });

        $(".btn-add-task").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Add Task') ?>",
                onOpen: function(popup) { $("#title", popup).focus(); }
            });
        });

        $(".btn-edit-milestone").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Edit Milestone') ?>",
                onOpen: function(popup) { $("#projects-milestone-title", popup).focus(); }
            });
        });

        $(".btn-edit-log").each(function() {
            $(this).modalPopup({
                title: "<?= __d('projects', 'Edit Log') ?>",
                onOpen: function(popup) { $("#projects-logs-descript", popup).focus(); }
            });
        });
    });
</script>
