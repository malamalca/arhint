<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BankStatementEntry $bankStatementEntry
 */

$bseEdit = [
    'title_for_layout' => $bankStatementEntry->id
        ? __d('expenses', 'Edit Entry')
        : __d('expenses', 'Add Entry'),
    'menu' => [
        'delete' => [
            'title'   => __d('expenses', 'Delete'),
            'visible' => (bool)$bankStatementEntry->id && $this->getCurrentUser()->hasRole('root'),
            'url'     => [
                'plugin'     => 'Expenses',
                'controller' => 'BankStatementEntries',
                'action'     => 'delete',
                $bankStatementEntry->id,
            ],
            'params' => [
                'confirm' => __d('expenses', 'Are you sure you want to delete this entry?'),
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
                'parameters' => ['model' => $bankStatementEntry],
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'statement_id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'statement_id'],
            ],

            'fs_start' => '<fieldset>',
            'lg'       => sprintf('<legend>%s</legend>', __d('expenses', 'Entry')),

            'dat_issue' => [
                'method'     => 'control',
                'parameters' => ['field' => 'dat_issue', [
                    'type'  => 'date',
                    'label' => __d('expenses', 'Date') . ':',
                ]],
            ],
            'no' => [
                'method'     => 'control',
                'parameters' => ['field' => 'no', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'Ref#') . ':',
                ]],
            ],
            'client' => [
                'method'     => 'control',
                'parameters' => ['field' => 'client', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'Client') . ':',
                ]],
            ],
            'descript' => [
                'method'     => 'control',
                'parameters' => ['field' => 'descript', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'Description') . ':',
                ]],
            ],
            'ref' => [
                'method'     => 'control',
                'parameters' => ['field' => 'ref', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'Reference') . ':',
                ]],
            ],
            'iban' => [
                'method'     => 'control',
                'parameters' => ['field' => 'iban', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'IBAN') . ':',
                ]],
            ],
            'debit' => [
                'method'     => 'control',
                'parameters' => ['field' => 'debit', [
                    'type'  => 'number',
                    'step'  => '0.01',
                    'label' => __d('expenses', 'Debit') . ':',
                ]],
            ],
            'credit' => [
                'method'     => 'control',
                'parameters' => ['field' => 'credit', [
                    'type'  => 'number',
                    'step'  => '0.01',
                    'label' => __d('expenses', 'Credit') . ':',
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

echo $this->Lil->form($bseEdit, 'Expenses.BankStatementEntries.edit');
