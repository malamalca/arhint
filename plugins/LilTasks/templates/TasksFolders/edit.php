<?php
$taskEditFolder = [
    'title_for_layout' =>
        $folder->id ? __d('lil_tasks', 'Edit Task List') : __d('lil_tasks', 'Add Task List'),
    'menu' => [
        'delete' => $folder->isNew() ? null : [
            'title' => __d('lil_tasks', 'Delete'),
            'visible' => true,
            'url' => [
                'plugin'     => 'LilTasks',
                'controller' => 'TasksFolders',
                'action'     => 'delete',
                $folder->id
            ],
            'params' => [
                'confirm' => __d('lil_tasks', 'Are you sure you want to delete this folder?')
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
                        'label' => __d('lil_tasks', 'Title') . ':',
                        'autofocus'
                    ]
                ]
            ],

            'submit' => [
                'method'     => 'submit',
                'parameters' => [
                    'label' => __d('lil_tasks', 'Save')
                ]
            ],
            'form_end' => [
                'method'     => 'end',
            ],
        ]
    ]
];
$this->Lil->jsReady('$("#task-folder-title").focus();');
echo $this->Lil->form($taskEditFolder, 'LilTasks.TasksFolders.edit');
