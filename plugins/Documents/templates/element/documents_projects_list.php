<?php
//if ($documents->count() > 0) {
    $pagingUrl = [
        'action' => 'view',
        $entityId,
        '?' => [
            'tab' => 'documents',
        ]
    ];
    $pagingDirection = $this->getRequest()->getQuery('documents.direction', 'asc');
    $pagingDirection = $pagingDirection == 'asc' ? 'desc' : null;

    $paymentsTable = [
        'parameters' => [
            'cellspacing' => 0, 'cellpadding' => 0, 'style' => 'max-width:700px;',
        ],
        'pre' => '<div id="tabc_documents" class="col s12">',
        'post' => '</div>',
        'head' => ['rows' => [['columns' => [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' => $this->Paginator->sort(
                    'no',
                    __d('documents', 'Document'),
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
    foreach ($documents as $document) {
        $paymentsTable['body']['rows'][]['columns'] = [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' =>
                '<div class="small">' . h($counters[$document->counter_id]->title) . '</div>' .
                $this->Html->link(
                    '#' . $document->no . ' - ' . $document->title,
                    [
                        'plugin' => 'Documents',
                        'controller' => 'Documents',
                        'action' => 'view',
                        $document->id,
                    ]
                ),
            ],
            'download' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $document->attachments_count == 0 ? '' : $this->Html->link(
                    '<i class="material-icons">cloud_download</i>',
                    [
                        'plugin' => 'Documents',
                        'controller' => 'DocumentsAttachments',
                        'action' => 'downloadAll',
                        $document->id,
                    ],
                    [
                        'title' => __d('documents', 'Download Attachment(s)'),
                        'escape' => false,
                        'class' => 'btn btn-small btn-floating waves-effect waves-light waves-circle',
                    ]
                ),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$document->dat_issue,
            ],
            /*'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $document->isInvoice() ? $this->Number->currency($document->total) : '',
            ],*/
        ];
        $total += $document->total;
    }

    /*$paymentsTable['foot']['rows'][0]['columns']['total']['html'] =
        $this->Number->currency($documentsSum);*/

    echo $this->Lil->table($paymentsTable, 'Documents.Element.documents_list');
//} else {
//    echo '<div class="hint">' . __d('documents', 'No documents for this project found.') . '</div>';
//}
