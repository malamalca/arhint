<?php
$editForm = [
    'title_for_layout' =>
        $projectsUser->id ? __d('projects', 'Edit Project User') : __d('projects', 'Add Project User'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $projectsUser],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'project_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'project_id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'user' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'user_id',
                    'options' => [
                        'type' => 'select',
                        'options' => $users,
                        'label' => __d('projects', 'User') . ':',
                    ],
                ],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('projects', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($editForm, 'Projects.ProjectsUsers.edit');
