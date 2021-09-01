<?php
/**
 * This is admin_edit template file.
 */

$editForm = [
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<li id="material-editor"><div class="row">',
        'post' => '</div></<li>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $material],
            ],
            
            '<div class="col s1">',
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id', ['id' => 'id']],
            ],
            'sort_order' => [
                'method' => 'hidden',
                'parameters' => ['sort_order', ['id' => 'sort_order']],
            ],
            'composite_id' => [
                'method' => 'hidden',
                'parameters' => ['composite_id', ['id' => 'composite_id']],
            ],
            '</div>',
            '<div class="col s8">',
            'descript' => [
                'method' => 'text',
                'parameters' => ['descript', ['id' => 'descript']],
            ],
            '</div>',
            '<div class="col s3">',
            'thickness' => [
                'method' => 'text',
                'parameters' => [
                    'thickness',
                    [
                        'type' => 'number',
                        'step' => 0.1,
                        'id' => 'thickness',
                    ],
                ],
            ],
            '</div>',
            '<div class="row">',
            '<div class="col s2"></div>',
            '<div class="col s7">',
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_projects', 'Save'),
                ],
            ],
            '</div>',
            '</div>',
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];

echo $this->Lil->form($editForm, 'LilProjects.ProjectsComposites.edit.aht');
