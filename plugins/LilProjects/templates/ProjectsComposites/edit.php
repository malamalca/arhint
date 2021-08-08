<?php
/**
 * This is admin_edit template file.
 */

$editForm = [
    'title_for_layout' =>
        h($project->getName()) . ' :: ' .
        ($projectsComposite->id ? __d('lil_projects', 'Edit Composite') : __d('lil_projects', 'Add Composite')),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $projectsComposite, ['idPrefix' => 'projects-composites', 'type' => 'file']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['redirect', ['default' => base64_encode($this->getRequest()->referer())]],
            ],
            'project_id' => [
                'method' => 'hidden',
                'parameters' => ['project_id'],
            ],
            'no' => [
                'method' => 'control',
                'parameters' => [
                    'no',
                    [
                        'type' => 'text',
                        'label' => __d('lil_projects', 'No.'),
                    ],
                ],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'title',
                    [
                        'type' => 'text',
                        'label' => __d('lil_projects', 'Composite Name'),
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
            ],
        ],
    ],
];
$this->Lil->jsReady('$("#title").focus();');
echo $this->Lil->form($editForm, 'LilProjects.ProjectsComposites.edit');
