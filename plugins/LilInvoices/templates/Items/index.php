<?php

$itemsIndex = [
    'title_for_layout' => __d('lil_invoices', 'Items'),
    'menu' => [
        'add' => [
            'title' => __d('lil_invoices', 'Add'),
            'visible' => true,
            'url'   => [
                'plugin'     => 'LilInvoices',
                'controller' => 'Items',
                'action'     => 'add',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminItemsIndex',
        ],
        'head' => ['rows' => [['columns' => [
            'descript' => __d('lil_invoices', 'Description'),
            'qty' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_invoices', 'Qty'),
            ],
            'unit' => [
                'html' => __d('lil_invoices', 'Unit'),
            ],
            'discount' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_invoices', 'Discount') . ' [%]',
            ],
            'price' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_invoices', 'Price'),
            ],
            'actions' => [],
        ]]]],
    ],
];

foreach ($items as $item) {
    $itemsIndex['table']['body']['rows'][]['columns'] = [
        'descript' => $this->Html->link($item->descript, ['action' => 'edit', $item->id]),
        'qty' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Number->precision($item->qty, 2),
        ],
        'unit' => h($item->unit),
        'discount' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Number->precision($item->discount, 1),
        ],
        'price' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Number->currency($item->price),
        ],
        'actions' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($item->id) . ' ' . $this->Lil->deleteLink($item->id),
        ],
    ];
}

echo $this->Lil->index($itemsIndex, 'LilInvoices.Items.index');
