<?php
/**
 * This is admin_edit template file.
 */
$editForm = [
    'title_for_layout' =>
        $project->id ? __d('projects', 'Edit Project') : __d('projects', 'Add Project'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $project, ['idPrefix' => 'project', 'type' => 'file']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'owner_id' => [
                'method' => 'hidden',
                'parameters' => ['owner_id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['redirect', ['default' => base64_encode($this->getRequest()->referer())]],
            ],

            'no' => [
                'method' => 'control',
                'parameters' => [
                    'no',
                    [
                        'type' => 'text',
                        'label' => __d('projects', 'No.') . ':',
                        'autofocus',
                    ],
                ],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'title',
                    [
                        'type' => 'text',
                        'label' => __d('projects', 'Title') . ':',
                    ],
                ],
            ],
            'status' => empty($projectStatuses) ? null : [
                'method' => 'control',
                'parameters' => [
                    'status_id',
                    [
                        'type' => 'select',
                        'label' => [
                            'class' => 'active',
                            'text' => __d('projects', 'Status') . ':',
                        ],
                        'class' => 'browser-default',
                        'empty' => '-- ' . __d('projects', 'status') . ' --',
                        'options' => $projectStatuses,
                    ],
                ],
            ],
            'active' => [
                'method' => 'control',
                'parameters' => [
                    'active',
                    [
                        'type' => 'checkbox',
                        'label' => __d('projects', 'Active'),
                    ],
                ],
            ],

            'lat' => [
                'method' => 'control',
                'parameters' => [
                    'lat',
                    [
                        'type' => 'number',
                        'step' => '0.0001',
                        'label' => __d('projects', 'Latitude') . ':',
                    ],
                ],
            ],
            'lon' => [
                'method' => 'control',
                'parameters' => [
                    'lon',
                    [
                        'type' => 'number',
                        'step' => '0.0001',
                        'label' => __d('projects', 'Longitude') . ':',
                    ],
                ],
            ],

            'ico' => [
                'method' => 'control',
                'parameters' => [
                    'ico',
                    [
                        'type' => 'file',
                        //'label' => __d('projects', 'Icon') . ':',
                        'label' => false,
                    ],
                ],
            ],
            'colorize' => [
                'method' => 'control',
                'parameters' => [
                    'colorize',
                    [
                        'type' => 'color',
                        'label' => [
                            'text' => __d('projects', 'Colorize') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('projects', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];
$this->Lil->jsReady('$("#project-no").focus();');
echo $this->Lil->form($editForm, 'Projects.Projects.edit');
