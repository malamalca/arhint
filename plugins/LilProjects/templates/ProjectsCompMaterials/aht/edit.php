<?php
/**
 * This is admin_edit template file.
 */

$editForm = [
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<tr id="material-editor"><td></td><td colspan="2">',
        'post' => '</td></<tr>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $material],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'sort_order' => [
                'method' => 'hidden',
                'parameters' => ['sort_order'],
            ],
            'composite_id' => [
                'method' => 'hidden',
                'parameters' => ['composite_id'],
            ],
            'descript' => [
                'method' => 'text',
                'parameters' => [
                    'descript',
                ],
            ],
            'thickness' => [
                'method' => 'text',
                'parameters' => [
                    'thickness',
                    [
                        'type' => 'number',
                        'step' => 0.1,
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

echo $this->Lil->form($editForm, 'LilProjects.ProjectsComposites.edit.aht');
