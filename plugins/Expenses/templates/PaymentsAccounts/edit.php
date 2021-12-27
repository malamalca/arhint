<?php
$accountEdit = [
    'title_for_layout' =>
        $account->id ? __d('expenses', 'Edit Account') : __d('expenses', 'Add Account'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $account],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'owner_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'owner_id'],
            ],
            'fs_basics_start' => '<fieldset>',
            'lg_basics' => sprintf('<legend>%s</legend>', __d('expenses', 'Basics')),

            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title', [
                        'type' => 'text',
                        'label' => __d('expenses', 'Title') . ':',
                    ],
                ],
            ],
            'primary' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'primary', [
                        'type' => 'checkbox',
                        'label' => __d('expenses', 'Primary Account'),
                    ],
                ],
            ],
            'active' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'active', [
                        'type' => 'checkbox',
                        'label' => __d('expenses', 'Active Account'),
                    ],
                ],
            ],

            'fs_basics_end' => '</fieldset>',

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('expenses', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];

echo $this->Lil->form($accountEdit, 'Expenses.PaymentsAccounts.edit');
