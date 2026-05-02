<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\Account $account
 * @var array<int|string, string> $parentList
 */

$accountEdit = [
    'title_for_layout' =>
        $account->id
            ? __d('expenses', 'Edit Account')
            : __d('expenses', 'Add Account'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre'  => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method'     => 'create',
                'parameters' => ['model' => $account],
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id'],
            ],

            'fs_basics_start' => '<fieldset>',
            'lg_basics'       => sprintf('<legend>%s</legend>', __d('expenses', 'Account')),

            'parent_id' => [
                'method'     => 'control',
                'parameters' => [
                    'field' => 'parent_id', [
                        'type'    => 'select',
                        'label'   => __d('expenses', 'Parent Account') . ':',
                        'options' => $parentList,
                        'empty'   => false,
                    ],
                ],
            ],

            'code' => [
                'method'     => 'control',
                'parameters' => [
                    'field' => 'code', [
                        'type'  => 'text',
                        'label' => __d('expenses', 'Code') . ':',
                    ],
                ],
            ],

            'name' => [
                'method'     => 'control',
                'parameters' => [
                    'field' => 'name', [
                        'type'  => 'text',
                        'label' => __d('expenses', 'Name') . ':',
                    ],
                ],
            ],

            'fs_basics_end' => '</fieldset>',

            'submit' => [
                'method'     => 'submit',
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

echo $this->Lil->form($accountEdit, 'Expenses.Accounts.edit');
