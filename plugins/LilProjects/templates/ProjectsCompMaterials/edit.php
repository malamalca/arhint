<?php
/**
 * This is admin_edit template file.
 */

$editForm = [
    'title_for_layout' =>
        h($composite->getName()) . ' :: ' .
        ($material->id ? __d('lil_projects', 'Edit Material') : __d('lil_projects', 'Add Material')),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $material, ['idPrefix' => 'composites-material',]],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['redirect', ['default' => base64_encode($this->getRequest()->referer())]],
            ],
            'composite_id' => [
                'method' => 'hidden',
                'parameters' => ['composite_id'],
            ],
            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'descript',
                    [
                        'type' => 'text',
                        'label' => __d('lil_projects', 'Description') . ':',
                    ],
                ],
            ],
            'thickness' => [
                'method' => 'control',
                'parameters' => [
                    'thickness',
                    [
                        'type' => 'number',
                        'step' => 0.1,
                        'label' => __d('lil_projects', 'Thickness [cm]') . ':',
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