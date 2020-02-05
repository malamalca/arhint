<?php

$vatIndex = [
    'title_for_layout' => __d('lil_invoices', 'Vats'),
    'menu' => [
        'add' => [
            'title' => __d('lil_invoices', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'LilInvoices',
                'controller' => 'vats',
                'action' => 'add',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminVatsIndex'
        ],
        'head' => ['rows' => [['columns' => [
            'descript' => __d('lil_invoices', 'Description'),
            'percent' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_invoices', 'Level') . ' [%]',
             ],
            'actions' => [],
        ]]]],
    ],
];

foreach ($vats as $vat) {
    $vatIndex['table']['body']['rows'][]['columns'] = [
        'descript' => $this->Html->link($vat->descript, ['action' => 'edit', $vat->id]),
        'percent' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Number->precision($vat->percent, 1),
        ],
        'actions' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($vat->id) . ' ' . $this->Lil->deleteLink($vat->id),
        ],
    ];
}

echo $this->Lil->index($vatIndex, 'LilInvoices.Vats.index');
