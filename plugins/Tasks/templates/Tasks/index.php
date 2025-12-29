<?php
use Tasks\Lib\TasksUtils;

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
                'id' => 'AddTaskPopup',
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
                'id' => 'AddTaskFolderPopup',
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

    $taskPanel = TasksUtils::taskPanel($task, $this);

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
        } else {
            ?>

        $("#AddTaskPopup").modalPopup({title: "<?php echo __d('tasks', 'Add Task'); ?>"});
        $("#AddTaskFolderPopup").modalPopup({title: "<?php echo __d('tasks', 'Add Task Folder'); ?>"});
            <?php
        }
        ?>
    });
</script>
