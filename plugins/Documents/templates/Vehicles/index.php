<?php

$itemsIndex = [
    'title_for_layout' => __d('documents', 'Vehicles'),
    'menu' => [
        'add' => [
            'title' => __d('documents', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'controller' => 'Vehicles',
                'action' => 'edit',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminVehiclesIndex',
        ],
        'head' => ['rows' => [['columns' => [
            'title' => __d('documents', 'Title'),
            'registration' => [
                'html' => __d('documents', 'Registration'),
            ],
            'owner' => [
                'html' => __d('documents', 'Owner'),
            ],
            'actions' => [],
        ]]]],
    ],
];

foreach ($vehicles as $vehicle) {
    $itemsIndex['table']['body']['rows'][]['columns'] = [
        'title' => h($vehicle->title),
        'registration' => h($vehicle->registration),
        'owner' => h($vehicle->owner),

        'actions' => !$this->getCurrentUser()->hasRole('editor') ? '' : [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($vehicle->id) . ' ' . $this->Lil->deleteLink($vehicle->id),
        ],
    ];
}

echo $this->Lil->index($itemsIndex, 'Documents.Vehicles.index');
