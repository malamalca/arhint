<?php

$indexTable = [
    'title_for_layout' => __d('crm', 'Adremas'),
    'menu' => [
        'add' => [
            'title' => __d('crm', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'action' => 'edit',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdremasIndex',
        ],
        'head' => ['rows' => [['columns' => [
            'title' => __d('crm', 'Title'),
            'created' => __d('crm', 'Created'),
            'actions' => [],
        ]]]],
    ],
];

foreach ($adremas as $adrema) {
    $indexTable['table']['body']['rows'][]['columns'] = [
        'title' => $this->Html->link($adrema->title, ['action' => 'view', $adrema->id]),
        'created' => (string)$adrema->created,
        'actions' => !$this->getCurrentUser()->hasRole('editor') ? '' : [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($adrema->id) . ' ' . $this->Lil->deleteLink($adrema->id),
        ],
    ];
}

echo $this->Lil->index($indexTable, 'Crm.Adremas.index');
