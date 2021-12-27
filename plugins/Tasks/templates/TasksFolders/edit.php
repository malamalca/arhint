<?php
$taskEditFolder = [
    'title_for_layout' =>
        $folder->id ? __d('tasks', 'Edit Task List') : __d('tasks', 'Add Task List'),
    'menu' => [
        'delete' => $folder->isNew() ? null : [
            'title' => __d('tasks', 'Delete'),
            'visible' => true,
            'url' => [
                'plugin'     => 'Tasks',
                'controller' => 'TasksFolders',
                'action'     => 'delete',
                $folder->id
            ],
            'params' => [
                'confirm' => __d('tasks', 'Are you sure you want to delete this folder?')
            ]
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method'     => 'create',
                'parameters' => ['model' => $folder, ['idPrefix' => 'task-folder']]
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id']
            ],
            'owner_id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'owner_id']
            ],

            'title' => [
                'method'     => 'control',
                'parameters' => [
                    'field'   => 'title', [
                        'type'  => 'text',
                        'label' => __d('tasks', 'Title') . ':',
                        'autofocus'
                    ]
                ]
            ],

            'submit' => [
                'method'     => 'submit',
                'parameters' => [
                    'label' => __d('tasks', 'Save')
                ]
            ],
            'form_end' => [
                'method'     => 'end',
            ],
        ]
    ]
];
$this->Lil->jsReady('$("#task-folder-title").focus();');
echo $this->Lil->form($taskEditFolder, 'Tasks.TasksFolders.edit');
