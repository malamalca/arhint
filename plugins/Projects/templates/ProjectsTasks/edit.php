<?php
$editForm = [
    'title_for_layout' =>
        $projectsTask->id ? __d('projects', 'Edit Task') : __d('projects', 'Add Task'),
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $projectsTask],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'user_id' => [
                'method' => 'hidden',
                'parameters' => ['user_id'],
            ],
            'project_id' => [
                'method' => 'hidden',
                'parameters' => ['project_id'],
            ],
            'milestone_id' => [
                'method' => 'hidden',
                'parameters' => ['milestone_id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'redirect', ['value' => $this->request->getQuery('redirect')]],
            ],

            'status' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'status', ['default' => 1]],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'title',
                    [
                        'type' => 'text',
                        'label' => __d('projects', 'Title') . ':',
                        'error' => __d('projects', 'Title is required.'),
                    ],
                ],
            ],
            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'descript',
                    [
                        'label' => __d('projects', 'Description') . ':',
                    ],
                ],
            ],
            'assigned_user' => [
                'method' => 'control',
                'parameters' => [
                    'assigned_user_id',
                    [
                        'type' => 'select',
                        'options' => $users,
                        'label' => __d('projects', 'Assigned User') . ':',
                    ],
                ],
            ],
            'milestone' => [
                'method' => 'control',
                'parameters' => [
                    'milestone_id',
                    [
                        'type' => 'select',
                        'options' => $milestones,
                        'label' => __d('projects', 'Milestone') . ':',
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

echo $this->Lil->form($editForm, 'Projects.ProjectsTasks.edit');
