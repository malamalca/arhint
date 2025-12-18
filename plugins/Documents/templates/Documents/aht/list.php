<?php
use Cake\Utility\Hash;

// UPDATE `documents_clients` SET contact_id = (SELECT id FROM contacts WHERE contacts.title = documents_clients.title);
$this->Paginator->options([
    'url' => $sourceRequest,
]);

/** COUNTERS POPUP */
$activeCounterId = $sourceRequest['?']['counter'] ?? null;
$popupCounters = ['items' => [[
    'title' => __d('documents', 'All Counters'),
    'url' => Hash::merge($sourceRequest, ['?' => ['counter' => null]]),
    'params' => ['class' => 'nowrap'],
]]];
foreach ($counters as $counter) {
    $popupCounters['items'][] = [
        'title' => (string)$counter,
        'url' => Hash::merge($sourceRequest, ['?' => ['counter' => $counter->id]]),
        'active' => ($activeCounterId == $counter->id),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupCounters = $this->Lil->popup('counters', $popupCounters, true);

/** COUNTERS FILTER LINK */
$countersFilter = sprintf('<button class="btn-small elevated" id="filter-counters" data-target="dropdown-counters">%1$s &#x25BC;</button>',
    $activeCounterId ? (string)$counters[$activeCounterId] : __d('documents', 'All Counters'),
);

$invoicesTable = [
    'parameters' => [
        'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'InvoicesList', 'width' => '700',
    ],
    'head' => ['rows' => [['columns' => [
        'no' => [
            'parameters' => ['class' => 'left-align'],
            'html' => $countersFilter,
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

if (count($data) === 0) {
    $invoicesTable['body'] = [
        'rows' => [[
            'columns' => [
                'no-data' => [
                    'parameters' => ['class' => 'center-align', 'colspan' => 2],
                    'html' => __d('documents', 'No documents found.'),
                ],
            ],
        ]],
    ];
} else {
    foreach ($data as $document) {
        $invoicesTable['body']['rows'][]['columns'] = [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' =>
                $this->Html->link(
                    '#' . $document->no . ' - ' . $document->title,
                    [
                        'plugin' => 'Documents',
                        'controller' => 'Documents',
                        'action' => 'view',
                        $document->id,
                    ],
                ),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$document->dat_issue,
            ],
        ];
    }
}

echo $popupCounters;
echo $this->Lil->table($invoicesTable, 'Documents.Documents.Aht.index');
echo '<script type="text/javascript">M.Dropdown.init(document.querySelectorAll("#filter-counters"), {coverTrigger: false, constrainWidth: false});</script>';
