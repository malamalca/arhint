<?php
    use Cake\Utility\Xml;

    $transformed = ['IzdaniDokumenti' => [
        'xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
        'xmlns:xds' => 'http://uri.etsi.org/01903/v1.1.1#',
        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
    ]];

    $i = 0;
foreach ($invoices as $invoice) {
    $transformed['IzdaniDokumenti']['Dokument'][$i] = [
        '@Id' => 'data',

        'NazivLokacije' => substr(h($invoice->location), 0, 70),
        'Datum' => $invoice->dat_issue ? $invoice->dat_issue->format('c') : '',
        'Naslov' => h($invoice->title),
        'Tip' => h($invoice->tpl_title),
        'Izdajatelj' => $invoice->issuer->person,
        'Stevilka' => $invoice->no,

        'PodatkiPodjetja' => [
        ],
    ];

    foreach ([$invoice->issuer, $invoice->receiver] as $client) {
        if ($client) {
            $clientData = [
                'NazivNaslovPodjetja' => [
                    'VrstaPartnerja' => $client->kind, // II - izdajatelj, BY - kupec, IV - prejemnik
                    'NazivPartnerja' => $this->Lil->mbWordWrap($client->title, ['maxlines' => 4, 'width' => 35, 'result' => 'array', 'startwith' => 1, 'prefix' => 'NazivPartnerja']),
                    'Ulica' => $this->Lil->mbWordWrap($client->street, ['maxlines' => 4, 'width' => 35, 'result' => 'array', 'startwith' => 1, 'prefix' => 'Ulica']),
                    'Kraj' => h($client->city),
                    'NazivDrzave' => empty($client->country) ? 'Slovenija' : h($client->country),
                    'PostnaStevilka' => h($client->zip),
                    'KodaDrzave' => empty($client->country_code) ? 'SI' : h($client->country_code),
                ],

                'FinancniPodatkiPodjetja' => [
                    'BancniRacun' => [
                        'StevilkaBancnegaRacuna' => h($client->iban),
                        'NazivBanke1' => '',
                        //'BIC' => str_pad(h($client->primary_account->bic), 11, 'X'),
                    ],
                ],

                'ReferencniPodatkiPodjetja' => [
                    0 => [
                        'VrstaPodatkaPodjetja' => 'VA',
                        'PodatekPodjetja' => h($client->tax_no),
                    ],
                    1 => [
                        'VrstaPodatkaPodjetja' => 'GN',
                        'PodatekPodjetja' => h($client->mat_no), // matična št.
                    ],
                ],
            ];

            $transformed['IzdaniDokumenti']['Dokument'][$i]['PodatkiPodjetja'][] = $clientData;
        }
    }

    // IV - prejemnik
    if ($invoice->documents_counter->direction == 'issued') {
        $transformed['IzdaniDokumenti']['Dokument'][$i]['PodatkiPodjetja'][2] =
            $transformed['IzdaniDokumenti']['Dokument'][$i]['PodatkiPodjetja'][0];
    } else {
        $transformed['IzdaniDokumenti']['Dokument'][$i]['PodatkiPodjetja'][2] =
            $transformed['IzdaniDokumenti']['Dokument'][$i]['PodatkiPodjetja'][1];
    }
    $transformed['IzdaniDokumenti']['Dokument'][$i]['PodatkiPodjetja'][2]['NazivNaslovPodjetja']['VrstaPartnerja'] = 'IV';

    // besedilo
    $transformed['IzdaniDokumenti']['Dokument'][$i]['Besedilo'] = $invoice->descript;
}

    $XmlObject = Xml::fromArray($transformed, ['format' => 'tags', 'return' => 'domdocument', 'pretty' => true]);

    $besedila = $XmlObject->getElementsByTagName('Besedilo');
foreach ($besedila as $besedilo) {
    $cDataBesedilo = $XmlObject->createCDATASection($besedilo->nodeValue);
    $besedilo->nodeValue = null;
    $besedilo->appendChild($cDataBesedilo);
}

    echo $XmlObject->saveXML();
