<?php
$vatEdit = [
    'title_for_layout' =>
        $vat->id ? __d('documents', 'Edit Vat') : __d('documents', 'Add Vat'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'class' => $this->Form,
                'method' => 'create',
                'parameters' => ['model' => $vat],
            ],
            'id' => [
                'class' => $this->Form,
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'referer' => [
                'class' => $this->Form,
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'descript' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'descript',
                    'options' => [
                        'label' => __d('documents', 'Description') . ':',
                        'error' => __d('documents', 'Description is required.'),
                    ],
                ],
            ],
            'percent' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'percent',
                    'options' => [
                        'type' => 'number',
                        'step' => '0.1',
                        'label' => __d('documents', 'Percent') . ':',
                    ],
                ],
            ],
            'submit' => [
                'class' => $this->Form,
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('documents', 'Save'),
                ],
            ],
            'form_end' => [
                'class' => $this->Form,
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($vatEdit, 'Documents.Vats.edit');
