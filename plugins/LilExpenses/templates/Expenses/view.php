<?php

$this->loadHelper('LilExpenses.LilExpense');

$expenseView = [
    'title_for_layout' => h($this->LilExpense->title($expense)),
    'menu' => [
        'edit' => [
            'title' => __d('lil_expenses', 'Edit'),
            'visible' => true,
            'url' => ['action' => 'edit', $expense->id],
        ],
        'delete' => [
            'title' => __d('lil_expenses', 'Delete'),
            'visible' => true,
            'url' => ['action' => 'delete', $expense->id],
            'params' => [
                'confirm' => __d('lil_expenses', 'Are you sure you want to delete this expense?'),
            ],
        ],
    ],
    'panels' => [
        'dates' => [
            'lines' => [
                [
                    'label' => __d('lil_expenses', 'Date') . ':',
                    'text' => (string)$expense->dat_happened,
                ],
            ],
        ],
        'totals' => [
            'lines' => [
                [
                    'label' => __d('lil_expenses', 'Net Total') . ':',
                    'text' => sprintf(
                        '<span class="currency %2$s">%1$s</span>',
                        $this->Number->currency($expense->net_total),
                        $expense->net_total < 0 ? 'negative' : 'positive'
                    ),
                ],
                [
                    'label' => __d('lil_expenses', 'Total') . ':',
                    'text' => sprintf(
                        '<span class="currency %2$s">%1$s</span>',
                        $this->Number->currency($expense->total),
                        $expense->total < 0 ? 'negative' : 'positive'
                    ),
                ],
            ],
        ],
        'payments' => [
            'params' => ['class' => 'no-margin'],
            'lines' => [
                'payments_title' => '<h3>' . __d('lil_expenses', 'Payments') . '</h3>',
                'payments_table' => $this->element('LilExpenses.payments_list'),
            ],
        ],
    ],
];

echo $this->Lil->panels($expenseView, 'LilExpenses.Expenses.view');
