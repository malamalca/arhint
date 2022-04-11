<?php
/**
 * This is admin_edit template file.
 */

$editForm = [
    'title_for_layout' =>
        h($project->getName()) . ' :: ' .
        ($projectsLog->id ? __d('projects', 'Edit Log') : __d('projects', 'Add Log')),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $projectsLog, ['idPrefix' => 'projects-logs', 'type' => 'file']],
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

            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'descript',
                    [
                        'type' => 'text',
                        'label' => false,
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
$this->Lil->jsReady('$("#descript").focus();');
echo $this->Lil->form($editForm, 'Projects.ProjectsLogs.edit');
