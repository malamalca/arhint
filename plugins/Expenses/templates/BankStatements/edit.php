<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BankStatement $bankStatement
 */

$bsEdit = [
    'title_for_layout' => __d('expenses', 'Edit Bank Statement'),
    'menu' => [],
    'form' => [
        'defaultHelper' => $this->Form,
        'pre'  => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method'     => 'create',
                'parameters' => ['model' => $bankStatement],
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id'],
            ],

            'fs_start' => '<fieldset>',
            'lg'       => sprintf('<legend>%s</legend>', __d('expenses', 'Bank Statement')),

            'no' => [
                'method'     => 'control',
                'parameters' => ['field' => 'no', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'Statement No') . ':',
                ]],
            ],
            'seq_no' => [
                'method'     => 'control',
                'parameters' => ['field' => 'seq_no', [
                    'type'  => 'number',
                    'label' => __d('expenses', 'Seq. No') . ':',
                ]],
            ],
            'iban' => [
                'method'     => 'control',
                'parameters' => ['field' => 'iban', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'IBAN') . ':',
                ]],
            ],
            'dat_issue' => [
                'method'     => 'control',
                'parameters' => ['field' => 'dat_issue', [
                    'type'  => 'date',
                    'label' => __d('expenses', 'Date') . ':',
                ]],
            ],
            'currency' => [
                'method'     => 'control',
                'parameters' => ['field' => 'currency', [
                    'type'  => 'text',
                    'label' => __d('expenses', 'Currency') . ':',
                ]],
            ],
            'balance' => [
                'method'     => 'control',
                'parameters' => ['field' => 'balance', [
                    'type'  => 'number',
                    'step'  => '0.01',
                    'label' => __d('expenses', 'Opening Balance') . ':',
                ]],
            ],
            'total_credit' => [
                'method'     => 'control',
                'parameters' => ['field' => 'total_credit', [
                    'type'  => 'number',
                    'step'  => '0.01',
                    'label' => __d('expenses', 'Total Credit') . ':',
                ]],
            ],
            'total_debit' => [
                'method'     => 'control',
                'parameters' => ['field' => 'total_debit', [
                    'type'  => 'number',
                    'step'  => '0.01',
                    'label' => __d('expenses', 'Total Debit') . ':',
                ]],
            ],
            'saldo' => [
                'method'     => 'control',
                'parameters' => ['field' => 'saldo', [
                    'type'  => 'number',
                    'step'  => '0.01',
                    'label' => __d('expenses', 'Saldo') . ':',
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

echo $this->Lil->form($bsEdit, 'Expenses.BankStatements.edit');
