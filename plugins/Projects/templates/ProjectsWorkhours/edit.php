<?php
use Cake\I18n\DateTime;
use Cake\Routing\Router;

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
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['id' => 'referer', 'default' => Router::url($this->getRequest()->referer(), true)]],
            ],
            'user_id' => $this->getCurrentUser()->hasRole('admin') ? null :[
                'method' => 'hidden',
                'parameters' => ['field' => 'user_id'],
            ],
            'user_label' => !$this->getCurrentUser()->hasRole('admin') ? null : [
                'method' => 'label',
                'parameters' => ['status_id', __d('projects', 'User') . ':'],
            ],
            'user' => !$this->getCurrentUser()->hasRole('admin') ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'user_id',
                    'options' => [
                        'type' => 'select',
                        'options' => $users,
                        'label' => false,
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'project_id' => !$this->getRequest()->getQuery('project') ? null : [
                'method' => 'hidden',
                'parameters' => ['field' => 'project_id'],
            ],
            'project' => $this->getRequest()->getQuery('project') ? null : [
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
                        'default' => new DateTime(),
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
            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'descript',
                    'options' => [
                        'type' => 'textarea',
                        'label' => [
                            'text' => __d('projects', 'Description') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
            ],
            'confirmed' => !$this->getCurrentUser()->hasRole('admin') ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'dat_confirmed',
                    'options' => [
                        'type' => 'date',
                        'label' => [
                            'text' => __d('projects', 'Confirmed') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('projects', 'Save'),
                    ['type' => 'submit'],
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
