<?php
use Cake\Core\Configure;
use Cake\Utility\Xml;

$transformed = ['Invoice' => [
    '@xmlns' => 'urn:eslog:2.00',
    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
]];

// find bank name for current users' company
$banks = Configure::read('Documents.banks');
$docTypes = Configure::read('Documents.documentTypes');

$i = 0;
foreach ((array)$invoices as $invoice) {
    $transformed['Invoice']['M_INVOIC'][$i] = [
        '@Id' => 'data',
        // message header
        'S_UNH' => [
            // message id
            'D_0062' => '534535345',
            'C_S009' => [
                'D_0065' => 'INVOIC', // message type
                'D_0052' => 'D', // version number; D=Draft
                'D_0054' => '01B', // message release number
                'D_0051' => 'UN', // controlling agency
            ],
        ],
        'S_BGM' => [
            'C_C002' => [
                'D_1001' => '380', // 380 - račun; 381 - dobropis; 325 - predračun; 386 - avansni račun
            ],
            'C_C106' => [
                'D_1004' => h($invoice->no),
            ],
        ],
        'S_DTM' => [
            0 => [
                'C_C507' => [
                    'D_2005' => '137', // invoice date-time
                    'D_2380' => (string)$invoice->dat_issue->toDateString(),
                ],
            ],
            1 => [
                'C_C507' => [
                    'D_2005' => '35', // delivery date-time
                    'D_2380' => (string)$invoice->dat_service->toDateString(),
                ],
            ],
        ],
        'S_FTX' => [
            'D_4451' => 'DOC', // documentation instructions
            'C_C107' => [
                'D_4441' => 'P1', // business process type
            ],
            'C_C108' => [
                'D_4440' => 'urn:cen.eu:en16931:2017',
            ],
        ],
        //payment reference
        'G_SG1' => [
            'S_RFF' => [
                'C_C506' => [
                    'D_1153' => 'PQ',
                    'D_1154' => $invoice->pmt_type . $invoice->pmt_module . $invoice->pmt_ref,
                ],
            ],
        ],

        'G_SG2' => [
            0 => [
                // buyer
                'S_NAD' => [
                    'D_3035' => 'BY',
                    'C_C080' => [
                        'D_3036' => $invoice->buyer->title,
                    ],
                    'C_C059' => [
                        'D_3042' => $invoice->buyer->street,
                    ],
                    'D_3164' => $invoice->buyer->city,
                    'C_C819' => null,
                    'D_3251' => $invoice->buyer->zip,
                    'D_3207' => $invoice->buyer->country_code ?? 'SI',
                ],
                'G_SG3' => [
                    /*0 => ['S_RFF' => [
                        'C_C506' => [
                            'D_1153' => '0199',
                            'D_1154' => $document->buyer->tax_no,
                        ],
                    ]],
                    1 => [
                        'S_RFF' => [
                            'C_C506' => [
                                'D_1153' => 'GN',
                                'D_1154' => $document->buyer->mat_no,
                            ],
                        ],
                    ],*/
                    2 => [
                        'S_RFF' => [
                            'C_C506' => [
                                'D_1153' => 'VA',
                                'D_1154' => $invoice->buyer->tax_no,
                            ],
                        ],
                    ],
                ],
                'G_SG5' => [
                    'S_CTA' => [
                        'D_3139' => 'IC',
                    ],
                    'S_COM' => [
                        'C_C076' => [
                            'D_3148' => $invoice->buyer->email,
                            'D_3155' => 'EM',
                        ],
                    ],
                ],
            ],
            1 => [
                'S_NAD' => [
                    'D_3035' => 'SE',
                    'C_C080' => [
                        'D_3036' => $invoice->issuer->title,
                    ],
                    'C_C059' => [
                        'D_3042' => $invoice->issuer->street,
                    ],
                    'D_3164' => $invoice->issuer->city,
                    'C_C819' => null,
                    'D_3251' => $invoice->issuer->zip,
                    'D_3207' => $invoice->issuer->country_code ?? 'SI',
                ],
                'S_FII' => [
                    'D_3035' => 'RB',
                    'C_C078' => [
                        'D_3194' => $invoice->issuer->iban,
                    ],
                    'C_C088' => [
                        'D_3433' => str_pad($invoice->issuer->bic, 11, 'X'),
                    ],
                ],
                'G_SG3' => [
                    /*0 => ['S_RFF' => [
                        'C_C506' => [
                            'D_1153' => '0199',
                            'D_1154' => $invoice->issuer->tax_no,
                        ],
                    ]],
                    1 => ['S_RFF' => [
                        'C_C506' => [
                            'D_1153' => 'GN',
                            'D_1154' => $invoice->issuer->mat_no,
                        ],
                    ]],*/
                    2 => ['S_RFF' => [
                        'C_C506' => [
                            'D_1153' => 'VA',
                            'D_1154' => $invoice->issuer->tax_no,
                        ],
                    ]],
                    3 => ['S_RFF' => [
                        'C_C506' => [
                            'D_1153' => 'AHP',
                            'D_1154' => $invoice->issuer->tax_no,
                        ],
                    ]],
                ],
                'G_SG5' => [
                    'S_CTA' => [
                        'D_3139' => 'IC',
                    ],
                    'S_COM' => [
                        'C_C076' => [
                            'D_3148' => $invoice->issuer->email,
                            'D_3155' => 'EM',
                        ],
                    ],
                ],
            ],
        ],

        // reference currency
        'G_SG7' => [
            'S_CUX' => [
                'C_C504' => [
                    'D_6347' => '2',
                    'D_6345' => 'EUR',
                ],
            ],
        ],

        // payment therms
        'G_SG8' => [
            'S_PAT' => [
                'D_4279' => '1',
            ],
            'S_DTM' => [
                'C_C507' => [
                    'D_2005' => '13',
                    'D_2380' => $invoice->dat_expire->toDateString(),
                ],
            ],
            'S_PAI' => [
                'C_C534' => [
                    'D_4461' => '30', // payment means
                ],
            ],
        ],
    ];

    ////////////////////////////////////////////////////////////////////////////////////////////
    // ITEMS
    $total_wo_discount = 0;
    $total_discount = 0;
    $total_base = 0;
    $total_tax = 0;
    $total_grand = 0;
    if ($invoice->documents_counter->direction == 'issued') {
        if (!empty($invoice->invoices_items)) {
            $j = 1;
            $tax_spec = [];
            $total_tax = $total_base = $total_grand = 0;
            foreach ($invoice->invoices_items as $item) {
                $em = '';
                switch ($item->unit) {
                    case 'cm':
                        $em = 'CMT';
                        break;
                    case 'dan':
                        $em = 'DAY';
                        break;
                    case 'g':
                        $em = 'GRM';
                        break;
                    case 'ura':
                    case 'ur':
                        $em = 'HUR';
                        break;
                    case 'kg':
                        $em = 'KGM';
                        break;
                    case 'km':
                        $em = 'KTM';
                        break;
                    case 'l':
                        $em = 'LTR';
                        break;
                    case 'mg':
                        $em = 'MGM';
                        break;
                    case 'min':
                        $em = 'MIN';
                        break;
                    case 'mm':
                        $em = 'MMT';
                        break;
                    case 'mes':
                        $em = 'MON';
                        break;
                    case 'm':
                        $em = 'MTR';
                        break;
                    default:
                        $em = 'C62'; // unit
                }

                $transformed['Invoice']['M_INVOIC'][$i]['G_SG26'] = [
                    'S_LIN' => [
                        'D_1082' => $j, // številka vrstice
                    ],
                    'S_IMD' => [
                        'D_7077' => 'F',
                        'C_C272' => '',
                        'C_C273' => [
                            'D_7008' => $item->descript,
                        ],
                    ],
                    'S_QTY' => [
                        'C_C186' => [
                            'D_6063' => '47', // documentd qty
                            'D_6060' => $this->Number->format($item->qty, ['pattern' => '##0.00', 'locale' => 'en-US']),
                            'D_6411' => $em,
                        ],
                    ],
                    'G_SG27' => [
                        0 => ['S_MOA' => [
                            'C_C516' => [
                                'D_5025' => '203', // line item amount
                                'D_5004' => $this->Number->format($item->items_total, ['pattern' => '##0.00', 'locale' => 'en-US']),
                            ],
                        ]],
                        1 => ['S_MOA' => [
                            'C_C516' => [
                                'D_5025' => '38', // document item amount
                                // ZnesekPostavke :: (cena*količina*znesekpopusta)* (1+davčnastopnja)
                                'D_5004' => $this->Number->format($item->items_total * (1+$item->vat_percent/100), ['pattern' => '##0.00', 'locale' => 'en-US']),
                            ],
                        ]],
                    ],
                    'G_SG29' => [
                        0 => [
                            'S_PRI' => [
                                'C_C509' => [
                                    'D_5125' => 'AAA',
                                    'D_5118' => $this->Number->format($item->price, ['pattern' => '##0.00', 'locale' => 'en-US']),
                                ],
                            ],
                        ],
                        1 => [
                            'S_PRI' => [
                                'C_C509' => [
                                    'D_5125' => 'AAB',
                                    // ZnesekPostavke :: price * qty
                                    'D_5118' => $this->Number->format($item->items_total, ['pattern' => '##0.00', 'locale' => 'en-US']),
                                ],
                            ],
                        ],
                    ],
                    // taxes
                    'G_SG34' => [
                        'S_TAX' => [
                            'D_5283' => '7', // tax
                            'C_C241' => [
                                'D_5153' => 'VAT',
                            ],
                            'C_C243' => [
                                'D_5278' => $this->Number->format($item->vat_percent, ['pattern' => '##0.00', 'locale' => 'en-US']),
                            ],
                            'D_5305' => 'S', // S = standard. Z = zero, E = exempt, AE = reverse charge
                        ],
                    ],
                ];

                if (!isset($tax_spec[$item->vat_id])) {
                    $tax_spec[$item->vat_id] = ['base' => 0, 'amount' => 0, 'percent' => 0];
                }
                $tax_spec[$item->vat_id]['base'] += $item->net_total;
                $tax_spec[$item->vat_id]['amount'] += $item->tax_total;
                $tax_spec[$item->vat_id]['percent'] = $item->vat_percent;

                $total_wo_discount += $item->items_total;
                $total_discount += $item->discount_total;
                $total_base += $item->net_total;
                $total_tax += $item->tax_total;
                $total_grand += $item->total;

                $j++;
            }
        }
    }

    $transformed['Invoice']['M_INVOIC'][$i]['G_SG50'] = [
        0 => [
            'S_MOA' => [
                'C_C516' => [
                    // Total line items amount
                    'D_5025' => '79', // VrstaZneska = 79 :: vsota vrednosti (cena * količina) postavk brez popustov
                    'D_5004' => $this->Number->format($total_wo_discount, ['pattern' => '##0.00', 'locale' => 'en-US']),
                ],
            ],
        ],
        1 => [
            'S_MOA' => [
                'C_C516' => [
                    // Amount excluding Value Added Tax (VAT)
                    'D_5025' => '389',
                    'D_5004' => $this->Number->format($total_base, ['pattern' => '##0.00', 'locale' => 'en-US']),
                ],
            ],
        ],
        2 => [
            'S_MOA' => [
                'C_C516' => [
                    // Total amount including Value Added Tax (VAT)
                    'D_5025' => '388',
                    'D_5004' => $this->Number->format($total_base + $total_tax, ['pattern' => '##0.00', 'locale' => 'en-US']),
                ],
            ],
        ],
        3 => [
            'S_MOA' => [
                'C_C516' => [
                    // Amount due/amount payable
                    'D_5025' => '9', // VrstaZneska = 9 :: znesek za plačilo
                    'D_5004' => $this->Number->format($total_base + $total_tax, ['pattern' => '##0.00', 'locale' => 'en-US']),
                ],
            ],
        ],
        4 => [
            'S_MOA' => [
                'C_C516' => [
                    // Total allowances
                    'D_5025' => '260',
                    'D_5004' => $this->Number->format(0, ['pattern' => '##0.00', 'locale' => 'en-US']),
                ],
            ],
        ],
        5 => [
            'S_MOA' => [
                'C_C516' => [
                    // VAT, 2nd value
                    'D_5025' => '2',
                    'D_5004' => $this->Number->format($total_tax, ['pattern' => '##0.00', 'locale' => 'en-US']),
                ],
            ],
        ],
        6 => [
            'S_MOA' => [
                'C_C516' => [
                    // Message total duty/tax/fee amount
                    'D_5025' => '176', // VrstaZneska = 176 :: vsota zneskov DDV
                    'D_5004' => $this->Number->format($total_tax, ['pattern' => '##0.00', 'locale' => 'en-US']),
                ],
            ],
        ],
    ];

    $transformed['Invoice']['M_INVOIC'][$i]['G_SG52'] = [];

    if (isset($tax_spec)) {
        foreach ($tax_spec as $vat_id => $vat_data) {
            $transformed['Invoice']['M_INVOIC'][$i]['G_SG52'][] = [
                'S_TAX' => [
                    'D_5283' => '7',
                    'C_C241' => [
                        'D_5153' => 'VAT',
                    ],
                    'C_C243' => [
                        'D_5278' => $this->Number->format($vat_data['percent'], ['pattern' => '##0.0', 'locale' => 'en-US']),
                    ],
                    'D_5305' => 'S',
                ],
                'S_MOA' => [
                    0 => [
                        'C_C516' => [
                            'D_5025' => '125',
                            'D_5004' => $this->Number->format($vat_data['base'], ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                    ],
                    1 => [
                        'C_C516' => [
                            'D_5025' => '124',
                            'D_5004' => $this->Number->format($vat_data['amount'], ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                    ],
                ],
            ];
        }
    }
}// foreach document

$XmlObject = Xml::fromArray($transformed, ['format' => 'tags', 'return' => 'domdocument', 'pretty' => true]);

echo $XmlObject->saveXML();
