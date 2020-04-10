<?php
if ($invoices->count() > 0) {
    $pagingUrl = [
        'action' => 'view',
        $entityId
    ];
    $pagingDirection = $this->getRequest()->getQuery('invoices.direction', 'asc');
    $pagingDirection = $pagingDirection == 'asc' ? 'desc' : null;

    $paymentsTable = [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'InvoicesList', 'width' => '700'
        ],
        'head' => ['rows' => [['columns' => [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' => $this->Paginator->sort(
                    'no',
                    __d('lil_invoices', 'Invoice'),
                    ['url' => $pagingUrl, 'direction' => $pagingDirection]
                ),
            ],
            'download' => [
                'parameters' => ['class' => 'center-align'],
                'html' => '',
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Paginator->sort(
                    'dat_issue',
                    __d('lil_invoices', 'Date'),
                    ['url' => $pagingUrl, 'direction' => $pagingDirection]
                ),
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Paginator->sort(
                    'total',
                    __d('lil_invoices', 'Total'),
                    ['url' => $pagingUrl, 'direction' => $pagingDirection]
                ),
            ],
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'actions' => [
                'parameters' => ['class' => 'left-align'],
                'html' => '<ul class="paginator">' .
                    $this->Paginator->numbers([
                        'first' => '<<',
                        'last' => '>>',
                        'modulus' => 3,
                        'url' => $pagingUrl
                    ]) .
                    '</ul>',
            ],
            'download' => [
                'parameters' => ['class' => 'center-align'],
                'html' => '',
            ],
            'caption' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_invoices', 'Total') . ':',
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => '',
            ],
         ]]]],
    ];

    $total = 0;
    $net_total = 0;
    foreach ($invoices as $invoice) {
        $paymentsTable['body']['rows'][]['columns'] = [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' =>
                '<div class="small">' . h($invoice->invoices_counter->title) . '</div>' .
                $this->Html->link(
                    '#' . $invoice->no . ' - ' . $invoice->title,
                    [
                        'plugin' => 'LilInvoices',
                        'controller' => 'invoices',
                        'action' => 'view',
                        $invoice->id,
                    ]
                ),
            ],
            'download' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $invoice->invoices_attachment_count == 0 ? '' : $this->Html->link(
                    '<i class="material-icons">cloud_download</i>',
                    [
                        'plugin' => 'LilInvoices',
                        'controller' => 'InvoicesAttachments',
                        'action' => 'downloadAll',
                        $invoice->id,
                    ],
                    [
                        'title' => __d('lil_invoices', 'Download Attachment(s)'),
                        'escape' => false,
                        'class' => 'btn btn-small btn-floating waves-effect waves-light waves-circle'
                    ]
                ),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$invoice->dat_issue,
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($invoice->total),
            ],
        ];
        $total += $invoice->total;
        $net_total += $invoice->net_total;
    }

    $paymentsTable['foot']['rows'][0]['columns']['total']['html'] =
        $this->Number->currency($invoicesSum);

    echo $this->Lil->table($paymentsTable, 'LilInvoices.Element.invoices_list');
} else {
    echo '<div class="hint">' . __d('lil_invoices', 'No invoices for this Contact found.') . '</div>';
}
