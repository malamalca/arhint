<?php
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
                'submit' => [
                    'method' => 'submit',
                    'parameters' => [
                        'label' => __d('crm', 'Save'),
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
