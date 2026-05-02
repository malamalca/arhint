<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BookingRule $bookingRule
 * @var array<string, string> $modelList
 */

$brEdit = [
    'title_for_layout' => $bookingRule->id
        ? __d('expenses', 'Edit Booking Rule')
        : __d('expenses', 'Add Booking Rule'),
    'menu' => [
        'delete' => [
            'title'   => __d('expenses', 'Delete'),
            'visible' => (bool)$bookingRule->id,
            'url'     => [
                'plugin'     => 'Expenses',
                'controller' => 'BookingRules',
                'action'     => 'delete',
                $bookingRule->id,
            ],
            'params' => [
                'confirm' => __d('expenses', 'Are you sure you want to delete this booking rule?'),
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
                'parameters' => ['model' => $bookingRule],
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id'],
            ],

            'fs_start' => '<fieldset>',
            'lg'       => sprintf('<legend>%s</legend>', __d('expenses', 'Booking Rule')),

            'model' => [
                'method'     => 'control',
                'parameters' => ['field' => 'model', [
                    'type'    => 'select',
                    'label'   => __d('expenses', 'Model') . ':',
                    'options' => $modelList,
                    'empty'   => __d('expenses', '— Select —'),
                ]],
            ],
            'title' => [
                'method'     => 'control',
                'parameters' => ['field' => 'title', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'Title') . ':',
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

echo $this->Lil->form($brEdit, 'Expenses.BookingRules.edit');
