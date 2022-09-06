<?php
use Cake\i18n\FrozenDate;

if (!empty($filter['start']) && !empty($filter['end'])) {
    $date_caption = __d(
        'expenses',
        'from {0} to {1}',
        (string)FrozenDate::parse($filter['start']),
        (string)FrozenDate::parse($filter['end'])
    );
} elseif (!empty($filter['end'])) {
    $date_caption = __d('expenses', 'to {0}', $this->LilDate->format($filter['end']));
} elseif (!empty($filter['start'])) {
    $date_caption = __d('expenses', 'from {0}', $this->LilDate->format($filter['start']));
}

if (empty($filter['counter'])) {
    $counter_caption = __d('expenses', 'For all counters.');
} else {
    $counter_caption = __d('expenses', 'For specified counters: {0}', '<br />+ ' .
        implode(', <br />+ ', array_intersect_key($counters, array_flip($filter['counter']))));
}

////////////////////////////////////////////////////////////////////////////////////////////////
$analytics = [
    'title_for_layout' => __d('expenses', 'Unpaid Documents'),
    'head_for_layout' => false,
    'panels' => [
        sprintf('<h1>%s</h1>', __d('expenses', 'Unpaid Documents')),
        empty($date_caption) ? null : $date_caption . '<br />',
        $counter_caption . '<br />',
    ],
];

/**
 * Init table head
 *
 * @param array $target_table Target table
 * @return void
 */
function initUnpaidDocumentsTableHead(&$target_table)
{
    $target_table['parameters'] = [
        'width' => '100%',
        'cellpadding' => 2,
    ];
    $target_table['head'] = [
        'rows' => [
            0 => [
                'parameters' => [],
                'columns' => [
                    'cnt' => [
                        'parameters' => ['class' => 'center-align', 'width' => '6%'],
                        'html' => __d('expenses', 'Cnt'),
                    ],
                    'no' => [
                        'parameters' => ['class' => 'left-align', 'width' => '13%'],
                        'html' => __d('expenses', 'No'),
                    ],
                    'issued' => [
                        'parameters' => ['class' => 'center-align', 'width' => '13%'],
                        'html' => __d('expenses', 'Issued'),
                    ],
                    'title' => [
                        'parameters' => ['class' => 'left-align', 'width' => '20%'],
                        'html' => __d('expenses', 'Title'),
                    ],
                    'client' => [
                        'parameters' => ['class' => 'left-align', 'width' => '20%'],
                        'html' => __d('expenses', 'Client'),
                    ],
                    'total' => [
                        'parameters' => ['class' => 'right-align', 'width' => '14%'],
                        'html' => __d('expenses', 'Total'),
                    ],
                    'delta' => [
                        'parameters' => ['class' => 'right-align', 'width' => '14%'],
                        'html' => __d('expenses', 'Delta'),
                    ],
                ],
            ],
        ],
    ];
}

/**
 * Create table foot
 *
 * @param array $target_table Target Table
 * @param int $column_total Sum for total column
 * @param object $Number Number helper
 * @return void
 */
function initUnpaidDocumentsTableFoot(&$target_table, $column_total, $Number = null)
{
    $target_table['foot']['rows'] = [
        0 => [
            'columns' => [
                [
                    'parameters' => ['class' => 'right-align', 'colspan' => '6', 'width' => '86%'],
                    'html' => __d('expenses', 'Total') . ':',
                ],
                [
                    'parameters' => ['class' => 'right-align', 'width' => '14%'],
                    'html' => $Number->precision((float)$column_total, 2),
                ],
            ],
        ],
    ];
}

// table body values
$i = 0;
$grand_total = 0;
$current_counter = '';
$target = null;
$counter_total = null;

if (empty($data)) {
    $analytics['panels'][] = sprintf('<div><i>%s</i></div>', __d('expenses', 'No documents found'));
} else {
    foreach ($data as $expense) {
        $tableFields = [
            'counter' => '',
            'no' => '',
            'issued' => (string)$expense->dat_happened,
            'title' => $expense->title,
            'client' => '',
            'total' => $this->Number->currency($expense->total),
        ];

        if (!empty($expense->invoice)) {
            if ($current_counter !== $expense->invoice->counter_id) {
                // close table
                if ($i > 0) {
                    initUnpaidDocumentsTableFoot($target, $counter_total, $this->Number);
                }

                $analytics['panels']['cntr_hr_' . $expense->invoice->counter_id] = sprintf(
                    '<h2>%s</h2>',
                    h($counters[$expense->invoice->counter_id])
                );
                $analytics['panels']['cntr_' . $expense->invoice->counter_id]['table']['element'] = [];
                $target =& $analytics['panels']['cntr_' . $expense->invoice->counter_id]['table'];

                initUnpaidDocumentsTableHead($target);

                $current_counter = $expense->invoice->counter_id;
                $counter_total = 0;
            }

            $tableFields['counter'] = $expense->invoice->counter;
            $tableFields['no'] = $expense->invoice->no;
            $tableFields['issued'] = (string)$expense->invoice->dat_issue .
                '<br />' .
                (string)$expense->invoice->dat_expire;
            $tableFields['title'] = $expense->invoice->title;

            $client = $counters[$expense->invoice->counter_id]->kind == 'issued' ?
                $expense->invoice->receiver :
                $expense->invoice->issuer;
            $tableFields['client'] = $client->title;

            $tableFields['total'] = $this->Number->currency($expense->invoice->total);
        } elseif (empty($current_counter)) {
            $analytics['panels']['cntr_default']['table']['element'] = [];
            $target =& $analytics['panels']['cntr_default']['table'];

            initUnpaidDocumentsTableHead($target);

            $current_counter = 'default';
            $counter_total = 0;
        }

        // calculate delta and add to grand total
        $delta = $expense->total - $expense->payments_total;
        $grand_total += $delta;
        $counter_total += $delta;

        $target['body']['rows'][$i] = [
            'data' => $expense,
            'columns' => [],
        ];

        $target['body']['rows'][$i]['columns'] =
            [
                'cnt' => [
                    'parameters' => ['class' => 'center-align', 'width' => '6%'],
                    'html' => h($tableFields['counter']),
                ],
                'no' => [
                    'parameters' => ['class' => 'center-align', 'width' => '13%'],
                    'html' => h($tableFields['no']),
                ],
                'issued' => [
                    'parameters' => ['class' => 'center-align', 'width' => '13%'],
                    'html' => $tableFields['issued'],
                ],
                'title' => [
                    'parameters' => ['class' => 'left-align', 'width' => '20%'],
                    'html' => h($tableFields['title']),
                ],
                'client' => [
                    'parameters' => ['width' => '20%'],
                    'html' => h($tableFields['client']),
                ],
                'total' => [
                    'parameters' => ['class' => 'right-align', 'width' => '14%'],
                    'html' => $tableFields['total'],
                ],
                'delta' => [
                    'parameters' => ['class' => 'right-align', 'width' => '14%'],
                    'html' => $this->Number->currency($delta),
                ],
            ];

        $i++;
    }
    initUnpaidDocumentsTableFoot($target, $counter_total, $this->Number);
}

echo $this->Lil->panels($analytics);
