<?php
// UPDATE `invoices_clients` SET contact_id = (SELECT id FROM contacts WHERE contacts.title = invoices_clients.title);
if (!$invoices->isEmpty()) {
    $paymentsTable = [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'PaymentsList', 'width' => '700'
        ],
        'head' => ['rows' => [['columns' => [
            'no' => [
                'parameters' => ['class' => 'left'],
                'html' => __d('lil_invoices', 'No'),
            ],
            'date' => [
                'parameters' => ['class' => 'center'],
                'html' => __d('lil_invoices', 'Date'),
            ],
            'account' => [
                'parameters' => ['class' => 'left'],
                'html' => __d('lil_invoices', 'Title'),
            ],
            'net_total' => [
                'parameters' => ['class' => 'right'],
                'html' => __d('lil_invoices', 'Net Total'),
            ],
            'total' => [
                'parameters' => ['class' => 'right'],
                'html' => __d('lil_invoices', 'Total'),
            ],
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'actions' => [
                'parameters' => ['colspan' => '2', 'class' => 'left'],
                'html' => '<ul class="paginator-numbers">' .
                    $this->Paginator->numbers(['first' => 1, 'last' => 1, 'modulus' => 3, 'model' => 'Invoices']) .
                    '</ul>',
            ],
            'caption' => [
                'parameters' => ['class' => 'right'],
                'html' => __d('lil_invoices', 'Total') . ':',
            ],
            'net_total' => [
                'parameters' => ['class' => 'right'],
                'html' => '',
            ],
            'total' => [
                'parameters' => ['class' => 'right'],
                'html' => '',
            ],
         ]]]],
    ];

    $total = 0;
    $net_total = 0;
    foreach ($invoices as $invoice) {
        $paymentsTable['body']['rows'][]['columns'] = [
            'no' => [
                'parameters' => ['class' => 'left'],
                'html' => $this->Html->link(
                    $invoice->no,
                    [
                        'plugin' => 'LilInvoices',
                        'controller' => 'invoices',
                        'action' => 'view',
                        $invoice->id,
                    ]
                ) . '<div class="light">' . h($invoice->invoices_counter->title) . '</div>',
            ],
            'date' => [
                'parameters' => ['class' => 'center'],
                'html' => $this->Time->format($invoice->dat_issue, $this->Lil->dateFormat()),
            ],
            'title' => [
                'parameters' => ['class' => 'left'],
                'html' => h($invoice->title),
            ],
            'net_total' => [
                'parameters' => ['class' => 'right'],
                'html' => $this->Number->precision($invoice->net_total, 2),
            ],
            'total' => [
                'parameters' => ['class' => 'right'],
                'html' => $this->Number->precision($invoice->total, 2),
            ],
        ];
        $total += $invoice->total;
        $net_total += $invoice->net_total;
    }

    $paymentsTable['foot']['rows'][0]['columns']['net_total']['html'] =
        $this->Number->precision($net_total, 2);
    $paymentsTable['foot']['rows'][0]['columns']['total']['html'] =
        $this->Number->precision($total, 2);

    echo $this->Lil->table($paymentsTable, 'LilExpenses.Element.payments_list');

    $this->Lil->jsReady(sprintf('$("td.edit-payment > a").click(function() { popup("%s", $(this).prop("href"), "auto", 500); return false; });', __d('lil_invoices', 'Edit Payment')));
    $this->Lil->jsReady(sprintf('$("th.add-payment > a").click(function() { popup("%s", $(this).prop("href"), "auto", 500); return false; });', __d('lil_invoices', 'Add Payment')));
} else {
    echo '<div class="hint">' . __d('lil_invoices', 'No invoices for this Contact found.') . '</div>';
}
