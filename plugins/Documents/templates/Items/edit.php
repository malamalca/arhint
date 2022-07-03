<?php
$itemEdit = [
    'title_for_layout' =>
        $item->id ? __d('documents', 'Edit Item') : __d('documents', 'Add Item'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $item],
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
            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'descript',
                    'options' => [
                        'label' => __d('documents', 'Description') . ':',
                        'error' => __d('documents', 'Description is required.'),
                    ],
                ],
            ],
            'qty' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'qty',
                    'options' => [
                        'type' => 'number',
                        'step' => '0.01',
                        'label' => __d('documents', 'Qty') . ':',
                        'default' => 1,
                    ],
                ],
            ],
            'unit' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'unit',
                    'options' => [
                        'label' => __d('documents', 'Unit') . ':',
                        'default' => __d('documents', 'pcs'),
                    ],
                ],
            ],
            'discount' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'discount',
                    'options' => [
                        'type' => 'number',
                        'step' => '0.1',
                        'label' => __d('documents', 'Discount') . ' [%]:',
                        'default' => 0,
                    ],
                ],
            ],
            'price' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'price',
                    'options' => [
                        'type' => 'number',
                        'step' => '0.01',
                        'label' => __d('documents', 'Price') . ':',
                    ],
                ],
            ],
            'vat_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'vat_id',
                    'options' => [
                        'type' => 'select',
                        'options' => $vats,
                        'label' => [
                            'class' => 'active',
                            'text' => __d('documents', 'Vat') . ':',
                        ],
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('documents', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($itemEdit, 'Documents.Items.edit');
