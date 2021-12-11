<?php
if ($expense) {
    $paymentsTable = [
        'pre' => '<div style="padding-left: 0; padding-right: 0; width: 100%; max-width: 700px;">',
        'post' => '</div>',
        'parameters' => [
            'width' => '700', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'PaymentsList',
        ],
        'head' => ['rows' => [['columns' => [
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => __d('lil_expenses', 'Date'),
            ],
            'account' => [
                'parameters' => ['class' => 'left-align'],
                'html' => __d('lil_expenses', 'Account'),
            ],
            'amount' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_expenses', 'Amount'),
            ],
            'actions' => '&nbsp;',
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'add' => [
                'parameters' => ['class' => 'left-align'],
                'html' => $this->Html->link(
                    __d('lil_expenses', 'Add Payment'),
                    ['plugin' => 'LilExpenses', 'controller' => 'Payments', 'action' => 'edit',
                     '?' => ['expense' => $expense->id, 'amount' => $expense->total],
                    ],
                    ['id' => 'add-payment']
                ),
            ],
            'caption' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_expenses', 'Total') . ':',
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => '',
            ],
            'actions' => [
                'parameters' => ['colspan' => '2', 'class' => 'right-align'],
                'html' => '&nbsp;',
            ],
         ]]]],
    ];

    $total = 0;
    foreach ($expense->payments as $p) {
        $paymentsTable['body']['rows'][]['columns'] = [
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$p->dat_happened,
            ],
            'account' => [
                'parameters' => ['class' => 'left-align'],
                'html' => $accounts[$p->account_id] ?? __d('lil_expenses', 'N/A'),
            ],
            'amount' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->precision((float)$p->amount, 2),
            ],
            'edit' => [
                'parameters' => ['class' => 'center-align'],
                'html' =>
                $this->Lil->editLink(
                    ['plugin' => 'LilExpenses', 'controller' => 'Payments', 'action' => 'edit', $p->id],
                    ['class' => 'edit-payment']
                ) .
                ' ' .
                $this->Lil->deleteLink(
                    ['plugin' => 'LilExpenses', 'controller' => 'Payments', 'action' => 'delete', $p->id],
                    ['confirm' => __d('lil_expenses', 'Are you sure you want to delete this payment?')]
                ),
            ],
        ];
        $total += $p->amount;
    }

    $paymentsTable['foot']['rows'][0]['columns']['total']['html'] =
        $this->Number->precision((float)$total, 2) . ' / ' .
        sprintf(
            '<span%2$s>%1$s</span>',
            $this->Number->precision((float)$expense->total - $total, 2),
            $expense->total - $total < 0 ? ' class="negative"' : ''
        );

    echo $this->Lil->table($paymentsTable, 'LilExpenses.Element.payments_list');
} else {
    echo '<div class="hint">' . __d('lil_expenses', 'No expense for this item found.') . '</span>';
}
?>
<script type="text/javascript">
    $(document).ready(function() {
        $("a.edit-payment").modalPopup({
            title: "<?= __d('lil_expenses', 'Edit Payment') ?>"
        });
    });

    $(document).ready(function() {
        $("a#add-payment").modalPopup({
            title: "<?= __d('lil_expenses', 'Add Payment') ?>"
        });
    });
</script>
