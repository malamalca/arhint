<?php
$vatEdit = [
    'title_for_layout' =>
        $vat->id ? __d('lil_invoices', 'Edit Vat') : __d('lil_invoices', 'Add Vat'),
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
                        'label' => __d('lil_invoices', 'Description') . ':',
                        'error' => __d('lil_invoices', 'Description is required.'),
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
                        'label' => __d('lil_invoices', 'Percent') . ':',
                    ],
                ],
            ],
            'submit' => [
                'class' => $this->Form,
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_invoices', 'Save'),
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

echo $this->Lil->form($vatEdit, 'LilInvoices.Vats.edit');
