<?php

$vatIndex = [
    'title_for_layout' => __d('documents', 'Vats'),
    'menu' => [
        'add' => [
            'title' => __d('documents', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'plugin' => 'Documents',
                'controller' => 'vats',
                'action' => 'edit',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminVatsIndex',
        ],
        'head' => ['rows' => [['columns' => [
            'descript' => __d('documents', 'Description'),
            'percent' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('documents', 'Level') . ' [%]',
             ],
            'actions' => [],
        ]]]],
    ],
];

foreach ($vats as $vat) {
    $vatIndex['table']['body']['rows'][]['columns'] = [
        'descript' => h($vat->descript),
        'percent' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Number->precision((float)$vat->percent, 1),
        ],
        'actions' => !$this->getCurrentUser()->hasRole('editor') ? '' : [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($vat->id) . ' ' . $this->Lil->deleteLink($vat->id),
        ],
    ];
}

echo $this->Lil->index($vatIndex, 'Documents.Vats.index');
