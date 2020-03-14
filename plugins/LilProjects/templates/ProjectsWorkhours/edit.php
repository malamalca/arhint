<?php
use Cake\I18n\Time;

$editForm = [
    'title_for_layout' =>
        $projectsWorkhour->id ? __d('lil_projects', 'Edit Project Workhour') : __d('lil_projects', 'Add Project Workhour'),
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
                            'text' => __d('lil_projects', 'Project') . ':',
                            'class' => 'active'
                        ],
                        'class' => 'browser-default'
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
                            'text' => __d('lil_projects', 'Started') . ':',
                            'class' => 'active'
                        ],
                        'default' => new Time()
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
                            'text' => __d('lil_projects', 'Duration') . ':',
                            'class' => 'active'
                        ],
                    ],
                ],
            ],
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_projects', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($editForm, 'LilProjects.ProjectsWorkhours.edit');
