<?php
$editForm = [
    'title_for_layout' =>
        $taskComment->id ? __d('projects', 'Edit Comment') : __d('projects', 'Add Comment'),
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $taskComment],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'user_id' => $this->getCurrentUser()->hasRole('admin') ? null : [
                'method' => 'hidden',
                'parameters' => ['user_id'],
            ],
            'task_id' => [
                'method' => 'hidden',
                'parameters' => ['task_id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'redirect', ['value' => $this->request->getQuery('redirect')]],
            ],
            'user' => !$this->getCurrentUser()->hasRole('admin') ? null : [
                'method' => 'control',
                'parameters' => [
                    'user_id',
                    [
                        'label' => __d('projects', 'User') . ':',
                        'options' => $users,
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

echo $this->Lil->form($editForm, 'Projects.ProjectsTasksComments.edit');
