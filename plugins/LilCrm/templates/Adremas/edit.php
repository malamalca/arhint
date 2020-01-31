<?php
    $adremaEdit = [
        'title_for_layout' =>
            $adrema->id ? __d('lil_crm', 'Edit Adrema') : __d('lil_crm', 'Add Adrema'),
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
                            'label' => __d('lil_crm', 'Title') . ':',
                            'error' => [
                                'empty' => __d('lil_crm', 'Adrema title is required.'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'method' => 'submit',
                    'parameters' => [
                        'label' => __d('lil_crm', 'Save'),
                    ],
                ],
                'form_end' => [
                    'method' => 'end',
                    'parameters' => [],
                ],
            ],
        ],
    ];

    echo $this->Lil->form($adremaEdit, 'LilCrm.Adremas.edit');
