<?php
//if ($documents->count() > 0) {
    $pagingUrl = [
        'action' => 'view',
        $entityId,
        '?' => [
            'tab' => 'invoices',
        ],
    ];
    $pagingDirection = $this->getRequest()->getQuery('invoices.direction', 'asc');
    $pagingDirection = $pagingDirection == 'asc' ? 'desc' : null;

    $paymentsTable = [
        'parameters' => [
            'cellspacing' => 0, 'cellpadding' => 0, 'style' => 'max-width:700px;',
        ],
        'pre' => '<div id="tabc_invoices" class="col s12">',
        'post' => '</div>',
        'head' => ['rows' => [['columns' => [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' => $this->Paginator->sort(
                    'no',
                    __d('documents', 'Invoice'),
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
                    __d('documents', 'Date'),
                    ['url' => $pagingUrl, 'direction' => $pagingDirection]
                ),
            ],
            /*'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Paginator->sort(
                    'total',
                    __d('documents', 'Total'),
                    ['url' => $pagingUrl, 'direction' => $pagingDirection]
                ),
            ],*/
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'actions' => [
                'parameters' => ['class' => 'left-align'],
                'html' => '<ul class="paginator">' .
                    $this->Paginator->numbers([
                        'first' => '<<',
                        'last' => '>>',
                        'modulus' => 3,
                        'url' => $pagingUrl,
                    ]) .
                    '</ul>',
            ],
            'download' => [
                'parameters' => ['class' => 'center-align'],
                'html' => '',
            ],
            /*'caption' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('documents', 'Total') . ':',
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => '',
            ],*/
         ]]]],
    ];

    $total = 0;
    foreach ($invoices as $invoice) {
        $paymentsTable['body']['rows'][]['columns'] = [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' =>
                '<div class="small">' . h($counters[$invoice->counter_id]->title) . '</div>' .
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
            'download' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $invoice->attachments_count == 0 ? '' : $this->Html->link(
                    '<i class="material-icons">cloud_download</i>',
                    [
                        'plugin' => false,
                        'controller' => 'Attachments',
                        'action' => 'downloadAll',
                        $invoice->id,
                    ],
                    [
                        'title' => __d('documents', 'Download Attachment(s)'),
                        'escape' => false,
                        'class' => 'btn-small filled',
                    ],
                ),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$invoice->dat_issue,
            ],
        ];
        $total += $invoice->total;
    }

    /*$paymentsTable['foot']['rows'][0]['columns']['total']['html'] =
        $this->Number->currency($invoicesSum);*/

    echo $this->Lil->table($paymentsTable, 'Documents.Element.invoices_list');
//} else {
//    echo '<div class="hint">' . __d('documents', 'No invoices for this project found.') . '</div>';
//}
