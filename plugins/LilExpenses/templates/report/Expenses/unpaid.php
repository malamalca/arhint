<?php
if (!empty($filter['start']) && !empty($filter['end'])) {
    $date_caption = __d(
        'lil_expenses',
        'from %1$s to %2$s',
        $this->LilDate->format($filter['start']),
        $this->LilDate->format($filter['end'])
    );
} elseif (!empty($filter['end'])) {
    $date_caption = __d('lil_expenses', 'to %s', $this->LilDate->format($filter['end']));
} elseif (!empty($filter['start'])) {
    $date_caption = __d('lil_expenses', 'from %s', $this->LilDate->format($filter['start']));
}

if (empty($filter['counter'])) {
    $counter_caption = __d('lil_expenses', 'For all counters.');
} else {
    $counter_caption = __d('lil_expenses', 'For specified counters: %s', '<br />+ ' .
        implode(', <br />+ ', array_intersect_key($counters, array_flip($filter['counter']))));
}

    ////////////////////////////////////////////////////////////////////////////////////////////////
    $analytics = [
        'title_for_layout' => __d('lil_expenses', 'Unpaid Invoices'),
        'head_for_layout' => false,
        'panels' => [
            sprintf('<h1>%s</h1>', __d('lil_expenses', 'Unpaid Invoices')),
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
    function initUnpaidInvoicesTableHead(&$target_table)
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
                            'html' => __d('lil_expenses', 'Cnt'),
                        ],
                        'no' => [
                            'parameters' => ['class' => 'left-align', 'width' => '13%'],
                            'html' => __d('lil_expenses', 'No'),
                        ],
                        'issued' => [
                            'parameters' => ['class' => 'center-align', 'width' => '13%'],
                            'html' => __d('lil_expenses', 'Issued'),
                        ],
                        'title' => [
                            'parameters' => ['class' => 'left-align', 'width' => '20%'],
                            'html' => __d('lil_expenses', 'Title'),
                        ],
                        'client' => [
                            'parameters' => ['class' => 'left-align', 'width' => '20%'],
                            'html' => __d('lil_expenses', 'Client'),
                        ],
                        'total' => [
                            'parameters' => ['class' => 'right-align', 'width' => '14%'],
                            'html' => __d('lil_expenses', 'Total'),
                        ],
                        'delta' => [
                            'parameters' => ['class' => 'right-align', 'width' => '14%'],
                            'html' => __d('lil_expenses', 'Delta'),
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
    function initUnpaidInvoicesTableFoot(&$target_table, $column_total, $Number = null)
    {
        $target_table['foot']['rows'] = [
            0 => [
                'columns' => [
                    [
                        'parameters' => ['class' => 'right-align', 'colspan' => '6', 'width' => '86%'],
                        'html' => __d('lil_expenses', 'Total') . ':',
                    ],
                    [
                        'parameters' => ['class' => 'right-align', 'width' => '14%'],
                        'html' => $Number->precision($column_total, 2),
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
        $analytics['panels'][] = sprintf('<div><i>%s</i></div>', __d('lil_expenses', 'No invoices found'));
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
                        initUnpaidInvoicesTableFoot($target, $counter_total, $this->Number);
                    }

                    $analytics['panels']['cntr_hr_' . $expense->invoice->counter_id] = sprintf(
                        '<h2>%s</h2>',
                        h($expense->invoice->invoices_counter->title)
                    );
                    $analytics['panels']['cntr_' . $expense->invoice->counter_id]['table']['element'] = [];
                    $target =& $analytics['panels']['cntr_' . $expense->invoice->counter_id]['table'];

                    initUnpaidInvoicesTableHead($target);

                    $current_counter = $expense->invoice->counter_id;
                    $counter_total = 0;
                }

                $tableFields['counter'] = $expense->invoice->counter;
                $tableFields['no'] = $expense->invoice->no;
                $tableFields['issued'] = (string)$expense->invoice->dat_issue .
                    '<br />' .
                    (string)$expense->invoice->dat_expire;
                $tableFields['title'] = $expense->invoice->title;
                // TODO: Add client title
                $tableFields['total'] = $this->Number->currency($expense->invoice->total);
            } elseif (empty($current_counter)) {
                $analytics['panels']['cntr_default']['table']['element'] = [];
                $target =& $analytics['panels']['cntr_default']['table'];

                initUnpaidInvoicesTableHead($target);

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
                        'html' => $tableFields['issued']
                    ],
                    'title' => [
                        'parameters' => ['class' => 'left-align', 'width' => '20%'],
                        'html' => h($tableFields['title']),
                    ],
                    'client' => [
                        'parameters' => ['width' => '20%'],
                        'html' => '', h($tableFields['client']),
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
        initUnpaidInvoicesTableFoot($target, $counter_total, $this->Number);
    }

    echo $this->Lil->panels($analytics);