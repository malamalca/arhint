<?php
$itemEdit = [
    'title_for_layout' =>
        $item->id ? __d('lil_invoices', 'Edit Item') : __d('lil_invoices', 'Add Item'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'class' => $this->Form,
                'method' => 'create',
                'parameters' => ['model' => $item],
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
            'qty' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'qty',
                    'options' => [
                        'type' => 'number',
                        'step' => '0.01',
                        'label' => __d('lil_invoices', 'Qty') . ':',
                        'default' => 1,
                    ],
                ],
            ],
            'unit' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'unit',
                    'options' => [
                        'label' => __d('lil_invoices', 'Unit') . ':',
                        'default' => __d('lil_invoices', 'pcs'),
                    ],
                ],
            ],
            'discount' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'discount',
                    'options' => [
                        'type' => 'number',
                        'step' => '0.1',
                        'label' => __d('lil_invoices', 'Discount') . ' [%]:',
                        'default' => 0,
                    ],
                ],
            ],
            'price' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'price',
                    'options' => [
                        'type' => 'number',
                        'step' => '0.01',
                        'label' => __d('lil_invoices', 'Price') . ':',
                    ],
                ],
            ],
            'vat_id' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'vat_id',
                    'options' => [
                        'type' => 'select',
                        'options' => $vats,
                        'label' => [
                            'class' => 'active',
                            'text' => __d('lil_invoices', 'Vat') . ':',
                        ],
                        'class' => 'browser-default',
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

echo $this->Lil->form($itemEdit, 'LilInvoices.Items.edit');
