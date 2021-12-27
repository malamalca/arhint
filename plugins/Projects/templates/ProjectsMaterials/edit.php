<?php
$editForm = [
    'title_for_layout' =>
        $projectsMaterial->id ? __d('projects', 'Edit Project Material') : __d('projects', 'Add Project Material'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $projectsMaterial],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'descript',
                    'options' => [
                        'label' => __d('projects', 'Descript') . ':',
                        'error' => __d('projects', 'Descript is required.'),
                    ],
                ],
            ],
            'group' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'group_id',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('projects', 'Group') . ':',
                            'class' => 'active',
                        ],
                        'options' => $groups,
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'thickness' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'thickness',
                    'options' => [
                        'type' => 'number',
                        'label' => __d('projects', 'Thickness') . ':',
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
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($editForm, 'Projects.ProjectsMaterials.edit');
