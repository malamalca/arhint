<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BookingOrder $bookingOrder
 * @var array<string, string> $statusList
 */

$boEdit = [
    'title_for_layout' => $bookingOrder->id
        ? __d('expenses', 'Edit Booking Order')
        : __d('expenses', 'Add Booking Order'),
    'menu' => [
        'delete' => [
            'title'   => __d('expenses', 'Delete'),
            'visible' => (bool)$bookingOrder->id && $bookingOrder->status === 'draft',
            'url'     => [
                'plugin'     => 'Expenses',
                'controller' => 'BookingOrders',
                'action'     => 'delete',
                $bookingOrder->id,
            ],
            'params' => [
                'confirm' => __d('expenses', 'Are you sure you want to delete this booking order?'),
            ],
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,
        'pre'  => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method'     => 'create',
                'parameters' => ['model' => $bookingOrder],
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id'],
            ],

            'fs_start' => '<fieldset>',
            'lg'       => sprintf('<legend>%s</legend>', __d('expenses', 'Booking Order')),

            'no' => [
                'method'     => 'control',
                'parameters' => ['field' => 'no', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'No') . ':',
                ]],
            ],
            'title' => [
                'method'     => 'control',
                'parameters' => ['field' => 'title', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'Title') . ':',
                ]],
            ],
            'date_created' => [
                'method'     => 'control',
                'parameters' => ['field' => 'date_created', [
                    'type'  => 'date',
                    'label' => __d('expenses', 'Date') . ':',
                ]],
            ],
            'status' => [
                'method'     => 'control',
                'parameters' => ['field' => 'status', [
                    'type'    => 'select',
                    'label'   => __d('expenses', 'Status') . ':',
                    'options' => $statusList,
                ]],
            ],

            'fs_end' => '</fieldset>',

            'submit' => [
                'method'     => 'submit',
                'parameters' => ['label' => __d('expenses', 'Save')],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];

echo $this->Lil->form($boEdit, 'Expenses.BookingOrders.edit');
