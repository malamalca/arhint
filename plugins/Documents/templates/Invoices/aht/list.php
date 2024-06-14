<?php
// UPDATE `documents_clients` SET contact_id = (SELECT id FROM contacts WHERE contacts.title = documents_clients.title);
if ($data->count() > 0) {
    $this->Paginator->options([
        'url' => $sourceRequest
    ]);

    $invoicesTable = [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'InvoicesList', 'width' => '700',
        ],
        'head' => ['rows' => [['columns' => [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' => __d('documents', 'Invoices'),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Paginator->sort(
                    'dat_issue',
                    __d('documents', 'Date'),
                ),
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Paginator->sort(
                    'total',
                    __d('documents', 'Total'),
                ),
            ],
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'actions' => [
                'parameters' => ['class' => 'left-align'],
                'html' => '<ul class="paginator">' .
                    $this->Paginator->numbers([
                        'first' => 1,
                        'last' => 1,
                        'modulus' => 3,
                    ]) .
                    '</ul>',
            ],
            'caption' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('documents', 'Total') . ':',
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => '',
            ],
         ]]]],
    ];

    $total = 0;

    foreach ($data as $invoice) {
        $invoicesTable['body']['rows'][]['columns'] = [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' =>
                //'<div class="small">' . h($counters[$invoice->counter_id]->title) . '</div>' .
                $this->Html->link(
                    '#' . $invoice->no . ' - ' . $invoice->title,
                    [
                        'plugin' => 'Documents',
                        'controller' => 'Invoices',
                        'action' => 'view',
                        $invoice->id,
                    ]
                ),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$invoice->dat_issue,
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($invoice->total ?? 0),
            ],
        ];
        $total += $invoice->total;
    }

    $invoicesTable['foot']['rows'][0]['columns']['total']['html'] =
        $this->Number->currency($invoicesTotals['sumTotal'] ?? 0);

    echo $this->Lil->table($invoicesTable, 'Documents.Invoices.Aht.index');
} else {
    echo '<div class="hint">' . __d('documents', 'No invoices for this Contact found.') . '</div>';
}
