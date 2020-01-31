<?php
    use Cake\Core\Configure;
    use Cake\Routing\Router;
    use Cake\Utility\Xml;

    $transformed = ['IzdaniRacunEnostavni' => [
        'xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
        'xmlns:xds' => 'http://uri.etsi.org/01903/v1.1.1#',
        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        '@xsi:noNamespaceSchemaLocation' => "http://www.gzs.si/e-poslovanje/sheme/eSLOG_1-6_EnostavniRacun.xsd",
    ]];

    // find bank name for current users' company
    $banks = Configure::read('LilInvoices.banks');
    $docTypes = Configure::read('LilInvoices.documentTypes');

    $i = 0;

    foreach ((array)$invoices as $invoice) {
        $transformed['IzdaniRacunEnostavni']['Racun'][$i] = [
        '@Id' => 'data',
        'GlavaRacuna' => [
            // VrstaRacuna :: E:El-1001 :: 380 - račun
            'VrstaRacuna' => '380',
            'StevilkaRacuna' => $invoice->no,
            // FunkcijaRacuna :: E-1225 :: 9 - original, 31 - kopija
            'FunkcijaRacuna' => '9',
            'NacinPlacila' => empty($invoice->pmt_kind) ? 0 : $invoice->pmt_kind,
            //'StroskovnoMesto' => '',
            'KodaNamena' => empty($invoice->pmt_sepa_type) ? 'OTHR' : $invoice->pmt_sepa_type,
        ],

        'Valuta' => [
            'VrstaValuteRacuna' => '2',
            'KodaValute' => 'EUR',
        ],
        'Lokacije' => [
            // VrstaLokacije = 91 :: 57-kraj plačila, 91-kraj izdaje dokumenta, 162-kraj prodaje (E: El-LOC01)
            'VrstaLokacije' => '91',
            'NazivLokacije' => substr($invoice->location, 0, 70),
        ],
        'DatumiRacuna' => [
            0 => [
                'VrstaDatuma' => 137,
                // VrstaDatuma = 137 :: datum izdaje
                'DatumRacuna' => $invoice->dat_issue ? $invoice->dat_issue->format('c') : '',
            ],
            1 => [
                'VrstaDatuma' => 35,
                // VrstaDatuma = 35 :: datum opravljene storitve oz. odpreme blaga
                'DatumRacuna' => $invoice->dat_service ? $invoice->dat_service->format('c') : '',
            ],
        ],
        'PlacilniPogoji' => [
            'PodatkiORokih' => [
                'VrstaPogoja' => '3',
            ],
            'PlacilniRoki' => [
                'VrstaDatumaPlacilnegaRoka' => '13',
                'Datum' => $invoice->dat_expire ? $invoice->dat_expire->format('c') : '',
            ],
        ],
        'PoljubnoBesedilo' => [
            0 => [
                // naslov računa
                'VrstaBesedila' => 'AAI',
                'Besedilo' => [
                    'Tekst1' => 'NASLOV_RACUNA',
                    'Tekst2' => $invoice->title,
                ],
            ],
            1 => [
                'VrstaBesedila' => 'ZZZ',
                'Besedilo' => [
                    'Tekst1' => 'TIP_DOKUMENTA',
                    'Tekst2' => $docTypes[$invoice->doc_type],
                ],
            ],
            2 => [
                'VrstaBesedila' => 'ZZZ',
                'Besedilo' => [
                    'Tekst1' => 'VRSTA_DOKUMENTA',
                    'Tekst2' => $invoice->invoices_counter->kind,
                ],
            ],
            3 => [
                'VrstaBesedila' => 'AAI',
                'Besedilo' => [
                    'Tekst1' => 'PRIROCNIK_ESLOG_1_6',
                    'Tekst2' => 'Dodatna informacija o verziji priročnika ESLOG_1_6',
                ],
            ],
            4 => [
                'VrstaBesedila' => 'AAI',
                'Besedilo' => [
                    'Tekst1' => 'FAKTURIST',
                    'Tekst2' => $invoice->issuer->person,
                ],
            ],
            5 => [
                'VrstaBesedila' => 'AAI',
                'Besedilo' => [
                    'Tekst1' => 'GLAVA_TEKST',
                    'Tekst2' => '',
                ],
            ],
        ],

        'PodatkiPodjetja' => [
        ],
        ];

        $dodatniTextIndex = 5;

        if ($invoice->inversed_tax) {
            $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PoljubnoBesedilo'][6] = [
            'VrstaBesedila' => 'AAI',
            'Besedilo' => [
                'Tekst1' => 'OBRNJENA_DAVCNA_OBVEZNOST',
                'Tekst2' => 'true',
            ],
            ];
            $dodatniTextIndex = 6;
        }

        foreach ([$invoice->buyer, $invoice->issuer, $invoice->receiver] as $client) {
            if ($client) {
                $clientData = [
                'NazivNaslovPodjetja' => [
                    // VrstaPartnerja :: II - izdajatelj, BY - kupec, IV - prejemnik
                    'VrstaPartnerja' => $client->kind,
                    'NazivPartnerja' => $this->Lil->mbWordWrap($client->title, ['maxlines' => 4, 'width' => 35, 'result' => 'array', 'startwith' => 1, 'prefix' => 'NazivPartnerja']),
                    'Ulica' => $this->Lil->mbWordWrap($client->street, ['maxlines' => 4, 'width' => 35, 'result' => 'array', 'startwith' => 1, 'prefix' => 'Ulica']),
                    'Kraj' => $client->city,
                    'NazivDrzave' => empty($client->country) ? 'Slovenija' : $client->country,
                    'PostnaStevilka' => $client->zip,
                    'KodaDrzave' => empty($client->country_code) ? 'SI' : $client->country_code,
                ],

                'FinancniPodatkiPodjetja' => [
                    'BancniRacun' => [
                        'StevilkaBancnegaRacuna' => $client->iban,
                        'NazivBanke1' => '',
                        //'BIC' => str_pad($client->bic, 11, 'X'),
                    ],
                ],

                'ReferencniPodatkiPodjetja' => [
                    0 => [
                        'VrstaPodatkaPodjetja' => 'VA',
                        'PodatekPodjetja' => $client->tax_no,
                    ],
                    1 => [
                        'VrstaPodatkaPodjetja' => 'GN',
                        'PodatekPodjetja' => $client->mat_no,
                    ],
                ],
                ];

                $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PodatkiPodjetja'][] = $clientData;
            }
        }

        // IV - prejemnik
        if ($invoice->invoices_counter->kind == 'issued') {
            $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PodatkiPodjetja'][2] =
            $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PodatkiPodjetja'][0];
        } else {
            $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PodatkiPodjetja'][2] =
            $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PodatkiPodjetja'][1];
        }
        $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PodatkiPodjetja'][2]['NazivNaslovPodjetja']['VrstaPartnerja'] = 'IV';

        ////////////////////////////////////////////////////////////////////////////////////////////
        // ITEMS
        $total_wo_discount = 0;
        $total_discount = 0;
        $total_base = 0;
        $total_tax = 0;
        $total_grand = 0;
        if ($invoice->invoices_counter->kind == 'issued') {
            if (!empty($invoice->invoices_items)) {
                $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PostavkeRacuna'] = [];

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
                            $em = 'PCE';
                    }

                    $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PostavkeRacuna'][] = [
                    'Postavka' => [
                        'StevilkaVrstice' => $j,
                    ],
                    'OpisiArtiklov' => [
                        [
                            'KodaOpisaArtikla' => 'F',
                            'OpisArtikla' => [
                                'OpisArtikla1' => $item->descript,
                            ],
                        ],
                        [
                            'OpisArtikla' => [
                                'OpisArtikla1' => 'OZNAKA_POSTAVKE',
                                'OpisArtikla2' => 'navadna',
                            ],
                        ],
                    ],
                    'KolicinaArtikla' => [
                        'VrstaKolicine' => '47',
                        'Kolicina' => $this->Number->format($item->qty, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        'EnotaMere' => $em,
                    ],
                    'ZneskiPostavke' => [
                        0 => [
                            'VrstaZneskaPostavke' => '203',
                            // ZnesekPostavke :: price * qty
                            'ZnesekPostavke' => $this->Number->format($item->items_total, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                        1 => [
                            'VrstaZneskaPostavke' => '38',
                            // ZnesekPostavke :: (cena*količina*znesekpopusta)* (1+davčnastopnja)
                            'ZnesekPostavke' => $this->Number->format($item->total, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                    ],
                    'CenaPostavke' => [
                        'Cena' => $this->Number->format($item->price, ['pattern' => '##0.00', 'locale' => 'en-US']),
                    ],
                    'DavkiPostavke' => [
                        'DavkiNaPostavki' => [
                            'VrstaDavkaPostavke' => 'VAT',
                            'OdstotekDavkaPostavke' => $this->Number->format($item->vat_percent, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                        'ZneskiDavkovPostavke' => [
                        [
                            'VrstaZneskaDavkaPostavke' => '125',
                            'Znesek' => $this->Number->format($item->net_total, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                        [
                            'VrstaZneskaDavkaPostavke' => '124',
                            'Znesek' => $this->Number->format($item->tax_total, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                        ],
                    ],
                    'OdstotkiPostavk' => [
                        'Identifikator' => 'A',
                        'VrstaOdstotkaPostavke' => '1',
                        'OdstotekPostavke' => $this->Number->format($item->discount, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        'VrstaZneskaOdstotka' => '204',
                        'ZnesekOdstotka' => $this->Number->format($item->discount_total, ['pattern' => '##0.00', 'locale' => 'en-US']),
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

                foreach ($tax_spec as $vat_id => $vat_data) {
                    $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PovzetekDavkovRacuna'][] = [
                    'DavkiRacuna' => [
                        'VrstaDavka' => 'VAT',
                        'OdstotekDavka' => $this->Number->format($vat_data['percent'], ['pattern' => '##0.0', 'locale' => 'en-US']),
                    ],
                    'ZneskiDavkov' => [
                        0 => [
                            'VrstaZneskaDavka' => '125',
                            'ZnesekDavka' => $this->Number->format($vat_data['base'], ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                        1 => [
                            'VrstaZneskaDavka' => '124',
                            'ZnesekDavka' => $this->Number->format($vat_data['amount'], ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                    ],
                    ];
                }
            }
        } else {
            ////////////////////////////////////////////////////////////////////////////////////////////
            // velja samo za prejete račune
            if (!empty($invoice->invoices_taxes)) {
                foreach ($invoice->invoices_taxes as $itm) {
                    $tax = round($itm->base * $itm->vat_percent / 100, 2);
                    $line_total = round($itm->base + $tax, 2);

                    $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PovzetekDavkovRacuna'][] = [
                    'DavkiRacuna' => [
                        'VrstaDavka' => 'VAT',
                        'OdstotekDavka' => $itm->vat_percent,
                    ],
                    'ZneskiDavkov' => [
                        0 => [
                            'VrstaZneskaDavka' => '125',
                            'ZnesekDavka' => $this->Number->format($itm->base, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                        1 => [
                            'VrstaZneskaDavka' => '124',
                            'ZnesekDavka' => $this->Number->format($tax, ['pattern' => '##0.00', 'locale' => 'en-US']),
                        ],
                    ],
                    ];

                    $total_wo_discount += $itm->base;
                    $total_discount += 0;
                    $total_base += $itm->base;
                    $total_tax += $tax;
                    $total_grand += $line_total;
                }
            }
        }

    // povzetek zneskov računa
        $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PovzetekZneskovRacuna'] = [
        0 => [
        'ZneskiRacuna' => [
            'VrstaZneska' => '79',
            // VrstaZneska = 79 :: vsota vrednosti (cena * količina) postavk brez popustov
            'ZnesekRacuna' => $this->Number->format($total_wo_discount, ['pattern' => '##0.00', 'locale' => 'en-US']),
        ],
        'SklicZaPlacilo' => [
            'SklicPlacila' => 'PQ',
        ],
        ],
        1 => [
        'ZneskiRacuna' => [
            'VrstaZneska' => '53',
            // VrstaZneska = 53 :: vsota zneskov popustov (oziroma rabatov)
            'ZnesekRacuna' => $this->Number->format($total_discount, ['pattern' => '##0.00', 'locale' => 'en-US']),
        ],
        'SklicZaPlacilo' => [
            'SklicPlacila' => 'PQ',
        ],
        ],
        2 => [
        'ZneskiRacuna' => [
            'VrstaZneska' => '125',
            // VrstaZneska = 125 :: vsota osnov za DDV
            'ZnesekRacuna' => $this->Number->format($total_base, ['pattern' => '##0.00', 'locale' => 'en-US']),
        ],
        'SklicZaPlacilo' => [
            'SklicPlacila' => 'PQ',
        ],
        ],
        3 => [
        'ZneskiRacuna' => [
            'VrstaZneska' => '176',
            // VrstaZneska = 176 :: vsota zneskov DDV
            'ZnesekRacuna' => $this->Number->format($total_tax, ['pattern' => '##0.00', 'locale' => 'en-US']),
        ],
        'SklicZaPlacilo' => [
            'SklicPlacila' => 'PQ',
        ],
        ],
        4 => [
        'ZneskiRacuna' => [
            'VrstaZneska' => '86',
            // VrstaZneska = 86 :: vsota vrednosti postavk s popusti in DDV
            'ZnesekRacuna' => $this->Number->format($total_base + $total_tax, ['pattern' => '##0.00', 'locale' => 'en-US']),
        ],
        'SklicZaPlacilo' => [
            'SklicPlacila' => 'PQ',
        ],
        ],
        5 => [
        'ZneskiRacuna' => [
            'VrstaZneska' => '9',
            // VrstaZneska = 9 :: znesek za plačilo
            'ZnesekRacuna' => $this->Number->format($total_base + $total_tax, ['pattern' => '##0.00', 'locale' => 'en-US']),
        ],
        'SklicZaPlacilo' => [
            'SklicPlacila' => 'PQ',
            'StevilkaSklica' => $invoice->pmt_type . $invoice->pmt_module . $invoice->pmt_ref,
        ],
        ],
        ];

        $descript = explode("\n", $this->Lil->mbWordWrap($invoice->descript, 70));

        foreach ($descript as $k => $d_line) {
            if (($k % 4) == 0) {
                $ln_cnt = 2;
                $dodatniTextIndex++;

                $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PoljubnoBesedilo'][$dodatniTextIndex] = [
                'VrstaBesedila' => 'AAI',
                'Besedilo' => [
                'Tekst1' => 'DODATNI_TEKST',
                ],
                ];
            }

            $transformed['IzdaniRacunEnostavni']['Racun'][$i]['PoljubnoBesedilo'][$dodatniTextIndex]['Besedilo']['Tekst' . $ln_cnt] = $d_line . ' ';
            $ln_cnt++;
        }
    }

    $XmlObject = Xml::fromArray($transformed, ['format' => 'tags', 'return' => 'domdocument', 'pretty' => true]);

    echo $XmlObject->saveXML();
