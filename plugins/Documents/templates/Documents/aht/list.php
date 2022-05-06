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
                'html' => __d('documents', 'Documents'),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Paginator->sort(
                    'dat_issue',
                    __d('documents', 'Date'),
                ),
            ],
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'actions' => [
                'parameters' => ['class' => 'left-align', 'colspan' => 2],
                'html' => '<ul class="paginator">' .
                    $this->Paginator->numbers([
                        'first' => 1,
                        'last' => 1,
                        'modulus' => 3,
                    ]) .
                    '</ul>',
            ],
         ]]]],
    ];

    $total = 0;

    foreach ($data as $document) {
        $invoicesTable['body']['rows'][]['columns'] = [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' =>
                //'<div class="small">' . h($counters[$invoice->counter_id]->title) . '</div>' .
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
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$document->dat_issue,
            ],
        ];
    }

    echo $this->Lil->table($invoicesTable, 'Documents.Documents.Aht.index');
} else {
    echo '<div class="hint">' . __d('documents', 'No documents for this Entity found.') . '</div>';
}
