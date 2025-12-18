<?php
use Cake\Utility\Hash;

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

if (count($data) === 0) {
    $invoicesTable['body'] = [
        'rows' => [[
            'columns' => [
                'no-data' => [
                    'parameters' => ['class' => 'center-align', 'colspan' => 2],
                    'html' => __d('documents', 'No invoices found.'),
                ],
            ],
        ]],
    ];
} else {
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
                    ],
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
    }
}

$invoicesTable['foot']['rows'][0]['columns']['total']['html'] =
    $this->Number->currency($invoicesTotals['sumTotal'] ?? 0);

echo $popupCounters;
echo $this->Lil->table($invoicesTable, 'Documents.Invoices.Aht.index');
echo '<script type="text/javascript">M.Dropdown.init(document.querySelectorAll("#filter-counters"), {coverTrigger: false, constrainWidth: false});</script>';
