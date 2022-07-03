<?php
$itemEdit = [
    'title_for_layout' =>
        $vehicle->id ? __d('documents', 'Edit Vehicle') : __d('documents', 'Add Vehicle'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $vehicle],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'owner_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'owner_id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'label' => __d('documents', 'Title') . ':',
                        'error' => __d('documents', 'Title is required.'),
                    ],
                ],
            ],
            'registration' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'registration',
                    'options' => [
                        'label' => __d('documents', 'Registration') . ':',
                        'error' => __d('documents', 'Registration is required.'),
                    ],
                ],
            ],
            'owner' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'owner',
                    'options' => [
                        'label' => __d('documents', 'Owner') . ':',
                        'error' => __d('documents', 'Owner is required.'),
                    ],
                ],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('documents', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($itemEdit, 'Documents.Vehicles.edit');
