<?php
use Cake\Core\Plugin;

$adremaEdit = [
    'title_for_layout' =>
        $adrema->id ? __d('crm', 'Edit Adrema') : __d('crm', 'Add Adrema'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$adrema],
            ],
            'id' => [
                'method' => 'control',
                'parameters' => [
                    'id',
                    'options' => ['type' => 'hidden'],
                ],
            ],
            'owner_id' => [
                'method' => 'control',
                'parameters' => [
                    'owner_id',
                    'options' => ['type' => 'hidden'],
                ],
            ],
            'referer' => [
                'method' => 'control',
                'parameters' => [
                    'referer',
                    'options' => ['type' => 'hidden'],
                ],
            ],

            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'label' => __d('crm', 'Title') . ':',
                        'error' => [
                            'empty' => __d('crm', 'Adrema title is required.'),
                        ],
                    ],
                ],
            ],
            'project' => !Plugin::isLoaded('Projects') ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'project_id', [
                        'type' => 'select',
                        'label' => __d('documents', 'Project') . ':',
                        'options' => $projects,
                        'empty' => '-- ' . __d('documents', 'no project') . ' --',
                        'default' => $this->getRequest()->getQuery('project'),
                    ],
                ],
            ],
            'additional_fields' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'additional_fields',
                    'options' => [
                        'type' => 'textarea',
                        'label' => __d('crm', 'Additional Fields') . ':',
                    ],
                ],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('crm', 'Save'),
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

echo $this->Lil->form($adremaEdit, 'Crm.Adremas.edit');
