<?php
/**
 * @var \App\View\AppView $this
 * @var \Documents\Model\Entity\TravelOrdersExpense $expense
 * @var string|null $redirect
 */
$expenseEdit = [
    'title_for_layout' => $expense->id
        ? __d('documents', 'Edit Expense')
        : __d('documents', 'Add Expense'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $expense],
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
                        'value' => $expense->start_time?->format('Y-m-d\TH:i'),
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
                        'value' => $expense->end_time?->format('Y-m-d\TH:i'),
                    ],
                ],
            ],
            'type' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'type',
                    'options' => [
                        'label' => __d('documents', 'Type') . ':',
                        'type' => 'text',
                    ],
                ],
            ],
            'description' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'description',
                    'options' => [
                        'label' => __d('documents', 'Description') . ':',
                        'type' => 'text',
                    ],
                ],
            ],
            'quantity' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'quantity',
                    'options' => [
                        'label' => __d('documents', 'Qty') . ':',
                        'type' => 'number',
                        'step' => '0.1',
                    ],
                ],
            ],
            'price' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'price',
                    'options' => [
                        'label' => __d('documents', 'Price') . ':',
                        'type' => 'number',
                        'step' => '0.01',
                    ],
                ],
            ],
            'currency' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'currency',
                    'options' => [
                        'label' => __d('documents', 'Currency') . ':',
                        'type' => 'text',
                    ],
                ],
            ],
            'approved_total' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'approved_total',
                    'options' => [
                        'label' => __d('documents', 'Approved Total') . ':',
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

echo $this->Lil->form($expenseEdit, 'Documents.TravelOrdersExpenses.edit');
