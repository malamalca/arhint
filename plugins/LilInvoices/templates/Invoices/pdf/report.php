<?php

$invoicesTable = [
    'data' => $data,
    'panels' => [
        'list' => [
            'lines' => [
                'title' => ['html' => sprintf('<h1>%s</h1>', __d('lil_invoices', 'Invoices List'))],
                'default' => [
                    'table' => [
                        'head' => [
                            'rows' => [
                                0 => ['columns' => [
                                    'no' => ['html' => __d('lil_invoices', 'No')],
                                    'date' => ['parameters' => ['align' => 'center'], 'html' => __d('lil_invoices', 'Issued')],
                                    'title' => ['parameters' => ['align' => 'left'], 'html' => __d('lil_invoices', 'Title')],
                                    'client' => ['html' => __d('lil_invoices', 'Client')],
                                    'net_total' => ['parameters' => ['align' => 'right'], 'html' => __d('lil_invoices', 'Net Total')],
                                    'total' => ['parameters' => ['align' => 'right'], 'html' => __d('lil_invoices', 'Total')],
                                ]],
                            ],
                        ],
                        'foot' => [
                            'rows' => [
                                0 => ['columns' => [
                                    'title' => ['parameters' => ['align' => 'right', 'colspan' => 4], 'html' => __d('lil_invoices', 'TOTAL') . ':'],
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

$dateFormat = $this->Lil->dateFormat();

$total = 0;
$netTotal = 0;
$counterId = null;
$target = null;

$def = $invoicesTable['panels']['list']['lines']['default'];
unset($invoicesTable['panels']['list']['lines']['default']);

foreach ($data as $invoice) {
    $client = $invoice->invoices_counter->kind == 'issued' ? $invoice->receiver : $invoice->issuer;

    if ($counterId != $invoice->counter_id) {
        if (!empty($counterId)) {
            $target['table']['foot']['rows'][0]['columns']['total']['html'] = $this->Number->precision((float)$total, 2);
            $target['table']['foot']['rows'][0]['columns']['net_total']['html'] = $this->Number->precision((float)$netTotal, 2);

            $total = 0;
            $netTotal = 0;
        }

        $invoicesTable['panels']['list']['lines']['cTitle' . $invoice->counter_id]['html'] =
            sprintf('<h3>%s</h3>', h($invoice->invoices_counter->title));
        $invoicesTable['panels']['list']['lines']['c' . $invoice->counter_id] = $def;
        $target = &$invoicesTable['panels']['list']['lines']['c' . $invoice->counter_id];
    }

    $target['table']['body']['rows'][] = ['columns' => [
        'no' => [
            'html' => $invoice->no,
        ],
        'date' => [
            'parameters' => ['align' => 'center'],
            'html' => $this->Time->format($invoice->dat_issue, $dateFormat),
        ],
        'title' => [
            'html' => h($invoice->title),
        ],
        'client' => [
            'html' => h($client->title),
        ],
        'net_total' => [
            'parameters' => ['align' => 'right'],
            'html' => $this->Number->precision((float)$invoice->net_total, 2),
        ],
        'total' => [
            'parameters' => ['align' => 'right'],
            'html' => $this->Number->precision((float)$invoice->total, 2),
        ],
    ]];

    $total += $invoice->total;
    $netTotal += $invoice->net_total;

    $counterId = $invoice->counter_id;
}

if (sizeof($data) > 0) {
    $target['table']['foot']['rows'][0]['columns']['total']['html'] = $this->Number->precision((float)$total, 2);
    $target['table']['foot']['rows'][0]['columns']['net_total']['html'] = $this->Number->precision((float)$netTotal, 2);
}

echo $this->Lil->panels($invoicesTable, 'LilInvoices.Invoices.report');
