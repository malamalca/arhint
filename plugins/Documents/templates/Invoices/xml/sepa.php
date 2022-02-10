<?php
    use Cake\Utility\Xml;

    $transformed = ['Document' => [
        'xmlns:' => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03',
        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        '@xsi:schemaLocation' => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03 ./pain.001.001.03.xsd',
        'CstmrCdtTrfInitn' => [
        ],
    ]];

    $user = $this->getRequest()->getAttribute('identity');

    $transformed['Document']['CstmrCdtTrfInitn']['GrpHdr'] = [
        'MsgId'   => date('Y-m-d') . 'T' . date('G:i:s/u'),
        'CreDtTm' => strftime('%Y-%m-%dT%H:%M:%S'),                 // creation date time :: 2011-12-10T09:28:24
        'NbOfTxs' => sizeof($invoices),                             // število transakcij v paketu
        'CtrlSum'   => '123.00',                                    // kontrolna vsota vseh transkacij
        'InitgPty' => [                                        // Podatki o iniciatorju plačila.
            'Nm' => $user->name,
            'Id' => [
                'OrgId' => [
                    'Othr' => [
                        'Id' => substr($user->id, 0, 35),
                        'SchmeNm' => ['Cd' => 'TXID'],
                    ],
                ],
            ],
        ],
    ];

    $sum = 0;
    foreach ($invoices as $invoice) {
        $transformed['Document']['CstmrCdtTrfInitn']['PmtInf'][] = [                       // Označuje začetek opisa plačilnih nalogov. Se lahko večkrat ponovi
            'PmtInfId' => str_replace('-', '', $invoice->id),                                   // Označuje začetek podatkov o identifikaciji plačilnega naloga.
            'PmtMtd'   => 'TRF',                                                                // Način plačila. Možne vrednosti so: • TRF (prenos sredstev) • CHQ (čeki)
            'PmtTpInf' => [                                                                // Označuje začetek podatkov o tipu plačilnega naloga.
                'InstrPrty' => 'NORM',                                                          // Prioriteta plačila. Možne vrednosti so: • »HIGH« (nujen nalog) • »NORMAL« (nenujen nalog); Vrednost »HIGH« je možna samo za stare »ne-SEPA« PPD naloge.
                'SvcLvl' => [                                                              // Označuje začetek podatkov o nivoju storitve (samo za SEPA naloge)
                    'Cd' => 'SEPA',                                                              // Koda za nivo storitve. Vedno "SEPA"
                ],
                'LclInstrm' => [                                                           // Označuje začetek podatkov za vrsto plačilnega naloga.
                    'Prtry' => 'SEPA',                                                           // • »SEPA« (SEPA nalog), • »BN02« • »PP02« • »KOMP« • »VP70« • »VP70EU« • »PRENOS« (PPT prenos sredstev)
                ],
            ],
            'ReqdExctnDt' => $invoice->dat_expire->toDateString(),                           // Datum izvršitve
            'Dbtr' => [                                                                    // Podatki o nalogodajalcu
                'Nm' => h($invoice->receiver->title),
                'PstlAdr' => [
                    'Ctry' => 'SI',
                    'AdrLine' => [
                        h($invoice->receiver->street),
                        h(implode(' ', array_filter([
                            $invoice->receiver->zip,
                            $invoice->receiver->city,
                        ]))),
                    ],
                ],
            ],
            'DbtrAcct' => [                                                                // Račun bremenitve. Račun je lahko podan samo v IBAN obliki.
                'Id' => [
                    'IBAN' => h($invoice->receiver->iban),
                ],
            ],
            'DbtrAgt' => [                                                                 // Banka nalogodajalca. Banka mora biti podana samo z BIC kodo.
                'FinInstnId' => [
                    'BIC' => h($invoice->receiver->bic),
                ],
            ],
            'CdtTrfTxInf' => [                                                             // Označuje začetek podatkov o plačilnih nalogih. Se lahko večkrat ponovi.
                'PmtId' => [                                                               // Označuje začetek podatkov o identifikaciji plačilnega naloga
                    //'EndToEndId' => 'SI00112233'
                    'EndToEndId' => 'NOTPROVIDED',                                               // Referenca nalogodajalca v Halcomovi strukturirani obliki ali v nestrukturirani obliki. Če referenca nalogodajalca ni podana se poda vrednost »NOTPROVIDED«
                ],
                'Amt' => [                                                                 // Označuje začetek podatkov za valuto in znesek naloga
                    'InstdAmt' => [                                                        // Valuta in znesek naloga. Podaja se za vse naloge z izjemo prenosa sredstev protivrednosti.
                        '@Ccy' => 'EUR',
                        '@' => (string)$invoice->total,
                    ],
                ],
                'ChrgBr' => 'SLEV',                                                             // Plačnik stroškov: • »DEBT«, če je plačnik stroškov nalogodajalec; • »CRED«, če je plačnik stroškov prejemnik; »SHAR«, če so stroški deljeni; »SLEV« za SEPA naloge
                'CdtrAgt' => [                                                             // Banka upravičenca. Za PPD in SEPA naloge ter prenos sredstev mora biti banka podana z BIC kodo.
                    'FinInstnId' => [
                        'BIC' => h($invoice->issuer->bic),
                    ],
                ],
                'Cdtr' => [                                                                // Podatki o upravičencu.
                    'Nm' => h($invoice->issuer->title),
                    'PstlAdr' => [
                        'Ctry' => 'SI',
                        'AdrLine' => [
                            h($invoice->issuer->street),
                            h(implode(' ', array_filter([
                                $invoice->issuer->zip,
                                $invoice->issuer->city,
                            ]))),
                        ],
                    ],
                ],
                'CdtrAcct' => [                                                            // Račun upravičenca. Za PPD in SEPA naloge mora biti račun podan v IBAN oblik
                    'Id' => [
                        'IBAN' => h($invoice->issuer->iban),
                    ],
                ],
                'Purp' => [                                                                // Označuje začetek podatkov za vrsto namena
                    'Cd' => h($invoice->pmt_sepa_type),                                          // Koda vrste namena: ena izmed 4-mestnih »Purpose« kod, ki jih navaja (definira) ISO 20022. Samo za sepa naloge
                ],
                'RmtInf' => [                                                              // Označuje začetek podatkov namena. Podana samo nestrukturirana ali samo strukturirana oblika
                    'Strd' => [                                                            // Structured ali <Ustrd>Max140Text</Ustrd>
                        'CdtrRefInf' => [                                                  // Označuje začetek podatkov za referenco prejemnika
                            //'CdtrRef' => '/SIB/00/125-07'                                     // Opcijsko Namesto TP in Ref

                            // ALI::
                            'Tp' => [                                                      // Označuje začetek podatkov o tipu (vrsti) stanju
                                'CdOrPrtry' => [
                                    // CMDT
                                    'Cd' => 'SCOR',                                              // Identifikacija vrsta prometa (transakcije). Hal E-Bank: Podaja se vrednost »NOTPROVIDED«
                                ],
                            ],

                            // npr. SI0020120104
                            // prva dva znaka sta lahko SI, NRC ali RF
                            'Ref' => h($invoice->pmt_type . $invoice->pmt_module . $invoice->pmt_ref),
                        ],
                        'AddtlRmtInf' => h($invoice->title),         // Namen plačila. Max 140 text.
                    ],
                ],
            ],
        ];

        $sum += $invoice->total;
    }

    $transformed['Document']['CstmrCdtTrfInitn']['GrpHdr']['CtrlSum'] =  (string)$sum;

    $XmlObject = Xml::fromArray($transformed, ['format' => 'tags']);
    echo $XmlObject->saveXML();
