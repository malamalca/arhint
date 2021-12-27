<?php
// UPDATE `documents_clients` SET contact_id = (SELECT id FROM contacts WHERE contacts.title = documents_clients.title);
if ($documents->count() > 0) {
    $pagingUrl = [
        'action' => 'view',
        $entityId,
    ];
    $pagingDirection = $this->getRequest()->getQuery('documents.direction', 'asc');
    $pagingDirection = $pagingDirection == 'asc' ? 'desc' : null;

    $paymentsTable = [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'DocumentsList', 'width' => '700',
        ],
        'head' => ['rows' => [['columns' => [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' => __d('documents', 'Document'),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Paginator->sort(
                    'dat_issue',
                    __d('documents', 'Date'),
                    ['url' => $pagingUrl, 'direction' => $pagingDirection]
                ),
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Paginator->sort(
                    'total',
                    __d('documents', 'Total'),
                    ['url' => $pagingUrl, 'direction' => $pagingDirection]
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
                        'url' => $pagingUrl,
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
                        'controller' => 'documents',
                        'action' => 'view',
                        $document->id,
                    ]
                ),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$document->dat_issue,
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($document->total),
            ],
        ];
        $total += $document->total;
    }

    $paymentsTable['foot']['rows'][0]['columns']['total']['html'] =
        $this->Number->currency($documentsSum);

    echo $this->Lil->table($paymentsTable, 'Documents.Element.documents_list');
} else {
    echo '<div class="hint">' . __d('documents', 'No documents for this Contact found.') . '</div>';
}
