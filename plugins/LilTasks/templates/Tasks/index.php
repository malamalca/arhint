<?php
    use Cake\I18n\Time;

    $this->set('head_for_layout', false);
    $tasksIndex = [
        'menu' => [
            'add' => [
                'title' => __d('lil_tasks', 'Add Task'),
                'visible' => true,
                'url' => array_merge(
                    [
                        'plugin' => 'LilTasks',
                        'controller' => 'Tasks',
                        'action' => 'add',
                    ],
                    $this->getRequest()->getQuery('folder') ? ['folder' => $this->getRequest()->getQuery('folder')] : [],
                    $this->getRequest()->getQuery('due') ? ['due' => $this->getRequest()->getQuery('due')] : []
                ),
                'params' => [
                    'onclick' => $this->getRequest()->is('mobile') ? null : sprintf(
                        'popup("%1$s", $(this).attr("href"), "auto"); return false;',
                        __d('lil_tasks', 'Add Task')
                    ),
                ],
            ],
            'add_folder' => [
                'title' => __d('lil_tasks', 'Add List'),
                'visible' => true,
                'url' => [
                    'plugin' => 'LilTasks',
                    'controller' => 'TasksFolders',
                    'action' => 'add',
                ],
                'params' => [
                    'onclick' => $this->getRequest()->is('mobile') ? null : sprintf(
                        'popup("%1$s", $(this).attr("href"), "auto"); return false;',
                        __d('lil_tasks', 'Add Folder')
                    ),
                ],
            ],
        ],
        'panels' => [],
    ];

    $taskFolderId = null;
    foreach ($tasks as $task) {
        if ($taskFolderId != $task->folder_id) {
            $tasksIndex['panels'][] = [
                'params' => ['class' => 'lil-tasks-folder' . (empty($taskFolderId) ? ' lil-tasks-folder-first' : '')],
                'lines' => [
                    'actions' => '<div class="lil-task-swipe-actions">' .
                        $this->Html->link(__d('lil_tasks', 'Edit'), [
                            'plugin' => 'LilTasks',
                            'controller' => 'TasksFolders',
                            'action' => 'edit',
                            $task->folder_id,
                        ], ['class' => 'lil-task-swipe-edit']) .
                        $this->Html->link(__d('lil_tasks', 'Delete'), [
                            'plugin' => 'LilTasks',
                            'controller' => 'TasksFolders',
                            'action' => 'delete',
                            $task->folder_id,
                        ], [
                            'class' => 'lil-task-swipe-delete',
                            'confirm' => __d('lil_tasks', 'Are you sure you want to delete this folder?'),
                        ]) .
                    '</div>',
                    'title' => $this->Html->link(
                        $task->tasks_folder->title,
                        [
                            'plugin' => 'LilTasks',
                            'controller' => 'Tasks',
                            'action' => 'index',
                            '?' => ['folder' => $task->folder_id],
                        ],
                    ),
                ],
            ];
            $taskFolderId = $task->folder_id;
        }

        $panelClass = 'lil-task-item';
        $tickImage = $this->Html->image('/lil_tasks/img/tick_empty.png');

        $prioritySpan = '';
        switch ($task->priority) {
            case 2:
                $prioritySpan = '<span class="lil-task-priority-high">!</span> ';
                break;
            default:
                $prioritySpan = '';
        }

        $dueSpan = '';
        if (!empty($task->deadline)) {
            $dueSpanClass = 'lil-task-due';
            $dueDate = $task->deadline->timeAgoInWords(['accuracy' => 'hour', 'end' => 30]);

            if ($this->Time->isToday($task->deadline)) {
                $dueSpanClass = 'lil-task-duetoday';
                if ($task->deadline->ne(new Time('today')) && $this->Time->isPast($task->deadline)) {
                    $dueSpanClass = 'lil-task-overdue';
                } else {
                    // time equals 00:00:00
                    $dueDate = __d('lil_tasks', 'today');
                }
            } elseif ($this->Time->isTomorrow($task->deadline)) {
                $dueDate = __d('lil_tasks', 'tomorrow');
            } elseif ($this->Time->isPast($task->deadline)) {
                $dueSpanClass = 'lil-task-overdue';
            }
            $dueSpan = sprintf(
                '<span class="%1$s">%2$s</span> ',
                $dueSpanClass,
                $dueDate
            );
        }

        if (!empty($task->completed)) {
            $panelClass .= ' lil-task-item-completed';
            $tickImage = $this->Html->image('/lil_tasks/img/tick_completed.png');
        }

        $taskPanel = [
            'params' => ['class' => $panelClass],
            'lines' => [
                'actions' => '<div class="lil-task-swipe-actions">' .
                    $this->Html->link(__d('lil_tasks', 'Edit'), [
                        'plugin' => 'LilTasks',
                        'controller' => 'Tasks',
                        'action' => 'edit',
                        $task->id,
                    ], ['class' => 'lil-task-swipe-edit']) .
                    $this->Html->link(__d('lil_tasks', 'Delete'), [
                        'plugin' => 'LilTasks',
                        'controller' => 'Tasks',
                        'action' => 'delete',
                        $task->id,
                    ], [
                        'class' => 'lil-task-swipe-delete',
                        'confirm' => __d('lil_tasks', 'Are you sure you want to delete this task?'),
                    ]) .
                    '</div>',
                'tick' => $this->Html->link($tickImage, [
                        'plugin' => 'LilTasks',
                        'controller' => 'Tasks',
                        'action' => 'toggle',
                        $task->id,
                    ], [
                        'escape' => false,
                        'class' => 'lil-task-tick',
                    ]),
                'title' => $prioritySpan . $this->Html->link(
                    $task->title,
                    [
                        'plugin' => 'LilTasks',
                        'controller' => 'Tasks',
                        'action' => 'edit',
                        $task->id,
                    ],
                    [
                       'class' => 'lil-task-edit',
                    ]
                ),
                'descript' => sprintf('<div class="descript">%s</div>', $dueSpan . h($task->descript)),
            ],
        ];

        $tasksIndex['panels'][] = $taskPanel;
    }

    echo $this->Html->script('/LilTasks/js/jquery.touchSwipe.min');

    ///////////////////////////////////////////////////////////////////////////////////////////////
    // call plugin handlers and output data
    echo $this->Lil->panels($tasksIndex, 'LilTasks.Tasks.index');
    ?>
<script type="text/javascript">
    $(document).ready(function() {
        <?php
        if (!$this->getRequest()->is('mobile')) {
            ?>
        $("a.lil-task-swipe-edit, a.lil-task-edit").on("click", function() {
            popup("<?php echo __d('lil_tasks', 'Edit'); ?>", $(this).attr("href"), "auto");
            return false;
        });


        $("div.lil-task-item, div.lil-tasks-folder").swipe({
            swipeLeft: function(event, direction, distance, duration, fingerCount) {
                if (!$("div.lil-task-swipe-actions", this).is(":visible")) {
                    $("div.lil-task-swipe-actions", this)
                        .show()
                        .animate({"margin-right": '+=100'});
                }
            },
            tap: function(event, target) {
                if ($("div.lil-task-swipe-actions", this).is(":visible")) {
                    $("div.lil-task-swipe-actions", this)
                        .css("margin-right", "-100px")
                        .hide();
                }
            },
            threshold: 30
        });
            <?php
        }
        ?>
    });
</script>
