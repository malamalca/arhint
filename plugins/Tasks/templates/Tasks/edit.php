<?php

use Cake\I18n\DateTime;

$deadline = null;
switch ($this->getRequest()->getQuery('due')) {
    case 'today':
        $deadline = DateTime::today();
        break;
    case 'tomorrow':
        $deadline = DateTime::tomorrow();
        break;
}

$taskEdit = [
    'title_for_layout' =>
        $task->id ? __d('tasks', 'Edit Task') : __d('tasks', 'Add Task'),
    'menu' => [
        'delete' => $task->isNew() ? null : [
            'title' => __d('tasks', 'Delete'),
            'visible' => true,
            'url' => [
                'plugin' => 'Tasks',
                'controller' => 'Tasks',
                'action' => 'delete',
                $task->id,
            ],
            'params' => [
                'confirm' => __d('tasks', 'Are you sure you want to delete this task?'),
            ],
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $task, ['idPrefix' => 'task']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'owner_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'owner_id'],
            ],
            'user_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'user_id'],
            ],

            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title', [
                        'type' => 'text',
                        'label' => __d('tasks', 'Title') . ':',
                        'autofocus',
                    ],
                ],
            ],

            'deadline' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'deadline', [
                        'type' => 'date',
                        'label' => __d('tasks', 'Due Date') . ':',
                        'default' => $deadline,
                    ],
                ],
            ],

            'tasks_label' => [
                'method' => 'label',
                'parameters' => ['tasker_id', __d('tasks', 'Assigned To') . ':'],
            ],
            'tasker' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'tasker_id', [
                        'type' => 'select',
                        'label' => false,
                        'empty' => '-- ' . __d('tasks', 'anyone') . ' --',
                        'options' => $users,
                        'default' => $this->getCurrentUser()->id,
                    ],
                ],
            ],

            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'descript', [
                        'type' => 'textarea',
                        'label' => __d('tasks', 'Notes') . ':',
                    ],
                ],
            ],

            'folder_id_label' => [
                'method' => 'label',
                'parameters' => ['folder_id', __d('tasks', 'List') . ':'],
            ],
            'folder' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'folder_id', [
                        'type' => 'select',
                        'label' => false,
                        'options' => $folders,
                        'default' => $this->getRequest()->getQuery('folder'),
                    ],
                ],
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('tasks', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];
$this->Lil->jsReady('$("#task-title").focus();');
echo $this->Lil->form($taskEdit, 'Tasks.Tasks.edit');
