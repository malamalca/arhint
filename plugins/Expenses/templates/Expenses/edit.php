<?php

use Cake\I18n\DateTime;

$payment_edit = [
    'title_for_layout' => $expense->id ? __d('expenses', 'Edit Expense') : __d('expenses', 'Add Expense'),
    'menu' => [
        'delete' => [
            'title' => __d('expenses', 'Delete'),
            'visible' => $expense->id,
            'url' => [
                'plugin' => 'Expenses',
                'controller' => 'Expenses',
                'action' => 'delete',
                $expense->id,
            ],
            'params' => [
                'confirm' => __d('expenses', 'Are you sure you want to delete this expense?'),
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
            //'lg_basics' => sprintf('<legend>%s</legend>', __d('expenses', 'Basics')),

            'title' => [
                'method' => 'control',
                'parameters' => ['title', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Description') . ':',
                    'default' => $this->getRequest()->getQuery('title'),
                ]],
            ],
            'dat_happened' => [
                'method' => 'control',
                'parameters' => ['dat_happened', [
                    'type' => 'lil-date',
                    'label' => __d('expenses', 'Date') . ':',
                    'error' => __d('expenses', 'Date is required.'),
                    'default' => $this->getRequest()->getQuery('date') ?: new DateTime(),
                ]],
            ],
            'month' => [
                'method' => 'control',
                'parameters' => ['month', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Month') . ':',
                ]],
            ],
            'net_total' => [
                'method' => 'control',
                'parameters' => ['net_total', [
                    'type' => 'number',
                    'step' => 0.01,
                    'label' => __d('expenses', 'Net Total') . ':',
                    'default' => $this->getRequest()->getQuery('total'),
                ]],
            ],
            'total' => [
                'method' => 'control',
                'parameters' => ['total', [
                    'type' => 'number',
                    'step' => 0.01,
                    'label' => __d('expenses', 'Total') . ':',
                    'default' => $this->getRequest()->getQuery('total'),
                ]],
            ],

            'account_id_label' => !empty($expense->id) ? null : [
                'method' => 'label',
                'parameters' => ['kind', __d('expenses', 'Payment') . ':'],
            ],
            'payment' => !empty($expense->id) ? null : [
                'method' => 'control',
                'parameters' => ['auto_payment', [
                    'type' => 'select',
                    'label' => false,
                    'empty' => '-- ' . __d('expenses', 'do not create payment') . ' --',
                    'options' => $accounts,
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
                    'label' => __d('expenses', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($payment_edit, 'Expenses.Expenses.edit');
?>
<script type="text/javascript">
    $(document).ready(function() {
        $("input#net-total").blur(function() {
            if ($("input#total").val() == "") {
                $("input#total").val($(this).val());
            }
        });

        $("input#dat-happened").blur(function() {
            if ($("input#month").val() == "") {
                $("input#month").val($(this).val().substr(0, 7));
            }
        });
    });
</script>
