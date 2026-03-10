<?php
/**
 * @var \App\View\AppView $this
 * @var \Documents\Model\Entity\TravelOrdersMileage $mileage
 * @var string|null $redirect
 */
$mileageEdit = [
    'title_for_layout' => $mileage->id
        ? __d('documents', 'Edit Mileage')
        : __d('documents', 'Add Mileage'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $mileage],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'travel_order_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'travel_order_id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'redirect', 'options' => ['value' => $redirect ?? '']],
            ],
            'start_time' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'start_time',
                    'options' => [
                        'label' => __d('documents', 'Start Time') . ':',
                        'type' => 'datetime-local',
                        'step' => 60,
                        'value' => $mileage->start_time?->format('Y-m-d\TH:i'),
                    ],
                ],
            ],
            'end_time' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'end_time',
                    'options' => [
                        'label' => __d('documents', 'End Time') . ':',
                        'type' => 'datetime-local',
                        'step' => 60,
                        'value' => $mileage->end_time?->format('Y-m-d\TH:i'),
                    ],
                ],
            ],
            'road_description' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'road_description',
                    'options' => [
                        'label' => __d('documents', 'Road Description') . ':',
                        'type' => 'text',
                    ],
                ],
            ],
            'distance_km' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'distance_km',
                    'options' => [
                        'label' => __d('documents', 'Distance (km)') . ':',
                        'type' => 'number',
                        'step' => '0.1',
                    ],
                ],
            ],
            'price_per_km' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'price_per_km',
                    'options' => [
                        'label' => __d('documents', 'Price / km') . ':',
                        'type' => 'number',
                        'step' => '0.01',
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

echo $this->Lil->form($mileageEdit, 'Documents.TravelOrdersMileages.edit');
