<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\Partner $partner
 * @var array<string, string> $roleList
 */

$partnerEdit = [
    'title_for_layout' => $partner->id
        ? __d('expenses', 'Edit Partner')
        : __d('expenses', 'Add Partner'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre'  => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method'     => 'create',
                'parameters' => ['model' => $partner],
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'contact_id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'contact_id'],
            ],

            'fs_start' => '<fieldset>',
            'lg'       => sprintf('<legend>%s</legend>', __d('expenses', 'Partner')),

            'role' => [
                'method'     => 'control',
                'parameters' => ['field' => 'role', [
                    'type'    => 'select',
                    'label'   => __d('expenses', 'Role') . ':',
                    'options' => $roleList,
                    'empty'   => false,
                ]],
            ],
            'date_start' => [
                'method'     => 'control',
                'parameters' => ['field' => 'date_start', [
                    'type'  => 'date',
                    'label' => __d('expenses', 'Date from') . ':',
                ]],
            ],
            'date_end' => [
                'method'     => 'control',
                'parameters' => ['field' => 'date_end', [
                    'type'  => 'date',
                    'label' => __d('expenses', 'Date to') . ':',
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

echo $this->Lil->form($partnerEdit, 'Expenses.Partners.edit');
