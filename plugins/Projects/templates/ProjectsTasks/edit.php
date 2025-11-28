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
                ],
            ],
            'date_complete' => [
                'method' => 'control',
                'parameters' => [
                    'date_complete',
                    [
                        'label' => __d('projects', 'Completition Date') . ':',
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
