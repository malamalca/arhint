<?php

use Cake\I18n\FrozenTime;

$deadline = null;
switch ($this->getRequest()->getQuery('due')) {
    case 'today':
        $deadline = FrozenTime::today();
        break;
    case 'tomorrow':
        $deadline = FrozenTime::tomorrow();
        break;
}

$taskEdit = [
    'title_for_layout' =>
        $task->id ? __d('lil_tasks', 'Edit Task') : __d('lil_tasks', 'Add Task'),
    'menu' => [
        'delete' => $task->isNew() ? null : [
            'title' => __d('lil_tasks', 'Delete'),
            'visible' => true,
            'url' => [
                'plugin' => 'LilTasks',
                'controller' => 'Tasks',
                'action' => 'delete',
                $task->id,
            ],
            'params' => [
                'confirm' => __d('lil_tasks', 'Are you sure you want to delete this task?'),
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
                        'label' => __d('lil_tasks', 'Title') . ':',
                        'autofocus',
                    ],
                ],
            ],

            'deadline' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'deadline', [
                        'type' => 'date',
                        'label' => __d('lil_tasks', 'Due Date') . ':',
                        'default' => $deadline,
                    ],
                ],
            ],

            'tasker' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'tasker_id', [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('lil_tasks', 'Assigned To') . ':',
                            'class' => 'active',
                        ],
                        'empty' => '-- ' . __d('lil_tasks', 'anyone') . ' --',
                        'options' => $users,
                        'default' => $this->getCurrentUser()->id,
                        'class' => 'browser-default',
                    ],
                ],
            ],

            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'descript', [
                        'type' => 'textarea',
                        'label' => __d('lil_tasks', 'Notes') . ':',
                    ],
                ],
            ],

            'folder' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'folder_id', [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('lil_tasks', 'List') . ':',
                            'class' => 'active',
                        ],
                        'options' => $folders,
                        'default' => $this->getRequest()->getQuery('folder'),
                        'class' => 'browser-default',
                    ],
                ],
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_tasks', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];
$this->Lil->jsReady('$("#task-title").focus();');
echo $this->Lil->form($taskEdit, 'LilTasks.Tasks.edit');
