<?php
use Cake\I18n\FrozenTime;

$folderTitle = null;
$folderId = $this->getRequest()->getQuery('folder');

if (!empty($folderId)) {
    $folderTitle = $tasks->first()->tasks_folder->title;
}

$this->set('pageTitle', __d('tasks', 'Tasks') . ($folderTitle ? ' :: ' . h($folderTitle) : ''));

$tasksIndex = [
    'title' => ' ',
    'menu' => [
        'add' => [
            'title' => __d('tasks', 'Add Task'),
            'visible' => true,
            'url' => [
                'plugin' => 'Tasks',
                'controller' => 'Tasks',
                'action' => 'edit',
                '?' => array_merge(
                    ['folder' => $this->getRequest()->getQuery('folder'), null],
                    ['due' => $this->getRequest()->getQuery('due', null)]
                ),
            ],
            'params' => [
                'onclick' => $this->getRequest()->is('mobile') ? null : sprintf(
                    'popup("%1$s", $(this).attr("href"), "auto"); return false;',
                    __d('tasks', 'Add Task')
                ),
            ],
        ],
        'add_folder' => [
            'title' => __d('tasks', 'Add List'),
            'visible' => true,
            'url' => [
                'plugin' => 'Tasks',
                'controller' => 'TasksFolders',
                'action' => 'edit',
            ],
            'params' => [
                'onclick' => $this->getRequest()->is('mobile') ? null : sprintf(
                    'popup("%1$s", $(this).attr("href"), "auto"); return false;',
                    __d('tasks', 'Add Folder')
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
                'actions' => !$this->getRequest()->is('mobile') ? null : '<div class="lil-task-swipe-actions">' .
                    $this->Html->link(__d('tasks', 'Edit'), [
                        'plugin' => 'Tasks',
                        'controller' => 'TasksFolders',
                        'action' => 'edit',
                        $task->folder_id,
                    ], ['class' => 'lil-task-swipe-edit']) .
                    $this->Html->link(__d('tasks', 'Delete'), [
                        'plugin' => 'Tasks',
                        'controller' => 'TasksFolders',
                        'action' => 'delete',
                        $task->folder_id,
                    ], [
                        'class' => 'lil-task-swipe-delete',
                        'confirm' => __d('tasks', 'Are you sure you want to delete this folder?'),
                    ]) .
                '</div>',
                'title' => $this->Html->link(
                    __d('tasks', 'Tasks') . ' :: ' . $task->tasks_folder->title,
                    [
                        'plugin' => 'Tasks',
                        'controller' => 'Tasks',
                        'action' => 'index',
                        '?' => ['folder' => $task->folder_id],
                    ]
                ),
            ],
        ];
        $taskFolderId = $task->folder_id;
    }

    $panelClass = 'lil-task-item';
    $tickImage = $this->Html->image('/tasks/img/tick_empty.png');

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
            if ($task->deadline->ne(new FrozenTime('today')) && $this->Time->isPast($task->deadline)) {
                $dueSpanClass = 'lil-task-overdue';
            } else {
                // time equals 00:00:00
                $dueDate = __d('tasks', 'today');
            }
        } elseif ($this->Time->isTomorrow($task->deadline)) {
            $dueDate = __d('tasks', 'tomorrow');
        } elseif ($this->Time->isPast($task->deadline)) {
            $dueSpanClass = 'lil-task-overdue';
        }
        $dueSpan = __d('tasks', ' :: due {0}', sprintf(
            '<span class="%1$s">%2$s</span> ',
            $dueSpanClass,
            $dueDate
        ));
    }

    if (!empty($task->completed)) {
        $panelClass .= ' lil-task-item-completed';
        $tickImage = $this->Html->image('/tasks/img/tick_completed.png');
    }

    $usersDescript = '';

    if (empty($task->tasker_id)) {
        if ($task->user_id != $this->getCurrentUser()->id) {
            $usersDescript = __d('tasks', '"{0}" to Anyone', h($users[$task->user_id]->name ?? ''));
        } else {
            $usersDescript = __d('tasks', 'Me to Anyone');
        }
    } else {
        if ($task->user_id != $this->getCurrentUser()->id) {
            $usersDescript = __d('tasks', '"{0}" to Me', h($users[$task->user_id]->name ?? ''));
        }

        if ($task->tasker_id != $this->getCurrentUser()->id) {
            $usersDescript = __d('tasks', '"{0}" from Me', h($users[$task->tasker_id]->name ?? ''));
        }

    }


    $taskPanel = [
        'params' => ['class' => $panelClass],
        'lines' => [
            'actions' => '<div class="lil-task-swipe-actions">' .
                $this->Html->link(__d('tasks', 'Edit'), [
                    'plugin' => 'Tasks',
                    'controller' => 'Tasks',
                    'action' => 'edit',
                    $task->id,
                ], ['class' => 'lil-task-swipe-edit']) .
                $this->Html->link(__d('tasks', 'Delete'), [
                    'plugin' => 'Tasks',
                    'controller' => 'Tasks',
                    'action' => 'delete',
                    $task->id,
                ], [
                    'class' => 'lil-task-swipe-delete',
                    'confirm' => __d('tasks', 'Are you sure you want to delete this task?'),
                ]) .
                '</div>',
            'tick' => $this->Html->link($tickImage, [
                    'plugin' => 'Tasks',
                    'controller' => 'Tasks',
                    'action' => 'toggle',
                    $task->id,
                ], [
                    'escape' => false,
                    'class' => 'lil-task-tick',
                ]),

            'title' => $prioritySpan .
                $this->getRequest()->is('mobile') ? h($task->title) : $this->Html->link(
                    $task->title,
                    [
                        'plugin' => 'Tasks',
                        'controller' => 'Tasks',
                        'action' => 'edit',
                        $task->id,
                    ],
                    [
                        'class' => 'lil-task-edit',
                    ]
                ),
            'descript' => sprintf('<div class="userdue">%s</div>', $usersDescript . $dueSpan),
            'descript2' => empty($task->descript) ? null : sprintf('<div class="descript">%s</div>', h($task->descript)),
        ],
    ];

    $tasksIndex['panels'][] = $taskPanel;
}

echo $this->Html->script('/Tasks/js/jquery.touchSwipe.min');

///////////////////////////////////////////////////////////////////////////////////////////////
// call plugin handlers and output data
echo $this->Lil->panels($tasksIndex, 'Tasks.Tasks.index');
?>
<script type="text/javascript">
    $(document).ready(function() {
        <?php
        if ($this->getRequest()->is('mobile')) {
            ?>
        $("a.lil-task-swipe-edit, a.lil-task-edit").on("click", function() {
            //popup("<?php echo __d('tasks', 'Edit'); ?>", $(this).attr("href"), "auto");
            //return false;
        });


        $("div.lil-task-item, div.lil-tasks-folder").swipe({
            swipeLeft: function(event, direction, distance, duration, fingerCount) {
                if (!$("div.lil-task-swipe-actions", this).is(":visible")) {
                    $("div.lil-task-swipe-actions")
                        .css("margin-right", "-100px")
                        .hide();

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
