<?php

use Cake\I18n\Time;

$payment_edit = [
    'title_for_layout' => $expense->id ? __d('lil_expenses', 'Edit Expense') : __d('lil_expenses', 'Add Expense'),
    'menu' => [
        'delete' => [
            'title' => __d('lil_expenses', 'Delete'),
            'visible' => $expense->id,
            'url' => [
                'plugin' => 'LilExpenses',
                'controller' => 'Expenses',
                'action' => 'delete',
                $expense->id,
            ],
            'params' => [
                'confirm' => __d('lil_expenses', 'Are you sure you want to delete this expense?'),
            ],
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$expense],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referrer'],
            ],
            //'fs_basics_start' => '<fieldset>',
            //'lg_basics' => sprintf('<legend>%s</legend>', __d('lil_expenses', 'Basics')),

            'title' => [
                'method' => 'control',
                'parameters' => ['title', [
                    'type' => 'text',
                    'label' => __d('lil_expenses', 'Description') . ':',
                    'default' => $this->getRequest()->getQuery('title'),
                ]],
            ],
            'dat_happened' => [
                'method' => 'control',
                'parameters' => ['dat_happened', [
                    'type' => 'lil-date',
                    'label' => __d('lil_expenses', 'Date') . ':',
                    'error' => __d('lil_expenses', 'Date is required.'),
                    'default' => $this->getRequest()->getQuery('date') ?: new Time(),
                ]],
            ],
            'net_total' => [
                'method' => 'control',
                'parameters' => ['net_total', [
                    'type' => 'number',
                    'step' => 0.01,
                    'label' => __d('lil_expenses', 'Net Total') . ':',
                    'default' => $this->getRequest()->getQuery('total'),
                ]],
            ],
            'total' => [
                'method' => 'control',
                'parameters' => ['total', [
                    'type' => 'number',
                    'step' => 0.01,
                    'label' => __d('lil_expenses', 'Total') . ':',
                    'default' => $this->getRequest()->getQuery('total'),
                ]],
            ],

            'payment' => !empty($expense->id) ? null : [
                'method' => 'control',
                'parameters' => ['auto_payment', [
                    'type' => 'select',
                    'label' => [
                        'text' => __d('lil_expenses', 'Payment') . ':',
                        'class' => 'active',
                    ],
                    'empty' => '-- ' . __d('lil_expenses', 'do not create payment') . ' --',
                    'options' => $accounts,
                    'class' => 'browser-default',
                ]],
            ],
            'payment_sepa_id' => !empty($expense->id) ? null : [
                'method' => 'control',
                'parameters' => ['sepa_id', [
                    'type' => 'hidden',
                    'value' => $this->getRequest()->getQuery('sepa_id'),
                ]],
            ],

            //'fs_basics_end' => '</fieldset>',

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_expenses', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($payment_edit, 'LilExpenses.Expenses.edit');
?>
<script type="text/javascript">
    $(document).ready(function() {
        $('input#net-total').blur(function() {
            if ($('input#total').val() == '') {
                $('input#total').val($(this).val());
            }
        });
    });
</script>
