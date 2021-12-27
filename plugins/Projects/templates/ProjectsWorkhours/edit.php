<?php
use Cake\I18n\FrozenTime;

$editForm = [
    'title_for_layout' =>
        $projectsWorkhour->id ? __d('projects', 'Edit Project Workhour') : __d('projects', 'Add Project Workhour'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $projectsWorkhour],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'user_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'user_id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'project' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'project_id',
                    'options' => [
                        'type' => 'select',
                        'options' => $projects,
                        'label' => [
                            'text' => __d('projects', 'Project') . ':',
                            'class' => 'active',
                        ],
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'started' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'started',
                    'options' => [
                        'type' => 'datetime',
                        'label' => [
                            'text' => __d('projects', 'Started') . ':',
                            'class' => 'active',
                        ],
                        'default' => new FrozenTime(),
                    ],
                ],
            ],
            'duration' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'duration',
                    'options' => [
                        'type' => 'duration',
                        'label' => [
                            'text' => __d('projects', 'Duration') . ':',
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
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($editForm, 'Projects.ProjectsWorkhours.edit');
