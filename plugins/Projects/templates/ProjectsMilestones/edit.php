<?php
/**
 * This is admin_edit template file.
 */

$editForm = [
    'title_for_layout' =>
        '<div class="small">' . h($project->getName()) . '</div>' .
        ($projectsMilestone->id ? __d('projects', 'Edit Milestone') : __d('projects', 'Add Milestone')),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $projectsMilestone, ['idPrefix' => 'projects-milestone', 'type' => 'file']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['redirect', ['default' => base64_encode($this->getRequest()->referer() ?? '')]],
            ],
            'project_id' => [
                'method' => 'hidden',
                'parameters' => ['project_id'],
            ],
            'user_id' => [
                'method' => 'hidden',
                'parameters' => ['user_id'],
            ],

            'title' => [
                'method' => 'control',
                'parameters' => [
                    'title',
                    [
                        'type' => 'text',
                        'label' => __d('projects', 'Title'),
                    ],
                ],
            ],
            'due' => [
                'method' => 'control',
                'parameters' => [
                    'date_due',
                    [
                        'type' => 'date',
                        'label' => __d('projects', 'Due'),
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
            ],
        ],
    ],
];

echo $this->Lil->form($editForm, 'Projects.ProjectsMilestones.edit');
