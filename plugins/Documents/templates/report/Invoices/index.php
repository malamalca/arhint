<?php
use Cake\I18n\FrozenDate;

if ($filter['kind'] == 'span') {
    $title = __d('documents', 'Documents List from {0} to {1}', $filter['start'], $filter['end']);
} else {
    $dateTitle = FrozenDate::parseDate($filter['month'] . '-01', 'yyyy-MM-dd');
    $title = __d('documents', 'Documents List for {0}', $dateTitle->i18nFormat('MMMM yyyy'));
}

$documentsTable = [
    'data' => $data,
    'panels' => [
        'list' => [
            'lines' => [
                'title' => ['html' => sprintf('<h1>%s</h1>', $title)],
                'default' => [
                    'table' => [
                        'params' => ['width' => '100%'],
                        'head' => [
                            'rows' => [
                                0 => ['columns' => [
                                    'no' => ['parameters' => ['align' => 'left'], 'html' => __d('documents', 'No')],
                                    'date' => ['parameters' => ['align' => 'center'], 'html' => __d('documents', 'Issued')],
                                    'title' => ['parameters' => ['align' => 'left'], 'html' => __d('documents', 'Title')],
                                    'client' => ['parameters' => ['align' => 'left'], 'html' => __d('documents', 'Client')],
                                    'net_total' => ['parameters' => ['align' => 'right'], 'html' => __d('documents', 'Net Total')],
                                    'total' => ['parameters' => ['align' => 'right'], 'html' => __d('documents', 'Total')],
                                ]],
                            ],
                        ],
                        'foot' => [
                            'rows' => [
                                0 => ['columns' => [
                                    'title' => ['parameters' => ['align' => 'right', 'colspan' => 4], 'html' => __d('documents', 'TOTAL') . ':'],
                                    'net_total' => ['parameters' => ['align' => 'right'], 'html' => ''],
                                    'total' => ['parameters' => ['align' => 'right'], 'html' => ''],
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];

$total = 0;
$netTotal = 0;
$counterId = null;
$target = null;

$def = $documentsTable['panels']['list']['lines']['default'];
unset($documentsTable['panels']['list']['lines']['default']);

foreach ($data as $document) {
    $client = $document->documents_counter->direction == 'issued' ? $document->receiver : $document->issuer;

    if ($counterId != $document->counter_id) {
        if (!empty($counterId)) {
            $target['table']['foot']['rows'][0]['columns']['total']['html'] = $this->Number->precision((float)$total, 2);
            $target['table']['foot']['rows'][0]['columns']['net_total']['html'] = $this->Number->precision((float)$netTotal, 2);

            $total = 0;
            $netTotal = 0;
        }

        $documentsTable['panels']['list']['lines']['cTitle' . $document->counter_id]['html'] =
            sprintf('<h3>%s</h3>', h($document->documents_counter->title));
        $documentsTable['panels']['list']['lines']['c' . $document->counter_id] = $def;
        $target = &$documentsTable['panels']['list']['lines']['c' . $document->counter_id];
    }

    $target['table']['body']['rows'][] = ['columns' => [
        'no' => [
            'parameters' => ['class' => 'nowrap'],
            'html' => $document->no,
        ],
        'date' => [
            'parameters' => ['align' => 'center', 'class' => 'nowrap'],
            'html' => (string)($document->dat_issue),
        ],
        'title' => [
            'html' => h($document->title),
        ],
        'client' => [
            'html' => h($client->title),
        ],
        'net_total' => [
            'parameters' => ['align' => 'right'],
            'html' => $this->Number->precision((float)$document->net_total, 2),
        ],
        'total' => [
            'parameters' => ['align' => 'right'],
            'html' => $this->Number->precision((float)$document->total, 2),
        ],
    ]];

    $total += $document->total;
    $netTotal += $document->net_total;

    $counterId = $document->counter_id;
}

if (sizeof($data) > 0) {
    $target['table']['foot']['rows'][0]['columns']['total']['html'] = $this->Number->precision((float)$total, 2);
    $target['table']['foot']['rows'][0]['columns']['net_total']['html'] = $this->Number->precision((float)$netTotal, 2);
}

echo $this->Lil->panels($documentsTable, 'Documents.Invoices.report');
