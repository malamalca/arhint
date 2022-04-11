<?php
            use Cake\Utility\Xml;

    $Lil = $this->loadHelper('Lil.Lil');

    $transformed = ['Dokumenti' => []];

    $i = 0;
foreach ($documents as $document) {
    $transformed['Dokumenti']['Dokument'][$i] = [
        '@Id' => 'data',

        'NazivLokacije' => substr(h($document->location), 0, 70),
        'Datum' => $document->dat_issue ? $document->dat_issue->format('c') : '',
        'Naslov' => h($document->title),
        'Tip' => h($document->tpl_title),
        'Izdajatelj' => $document->issuer->person,
        'Stevilka' => $document->no,

        'PodatkiPodjetja' => [
        ],
    ];

    foreach ([$document->issuer, $document->receiver] as $client) {
        if ($client) {
            $clientData = [
                'NazivNaslovPodjetja' => [
                    'VrstaPartnerja' => $client->kind,                                              // II - izdajatelj, BY - kupec, IV - prejemnik
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

            $transformed['Dokumenti']['Dokument'][$i]['PodatkiPodjetja'][] = $clientData;
        }
    }

    // besedilo
    $transformed['Dokumenti']['Dokument'][$i]['Besedilo'] = $document->descript;
}

    $XmlObject = Xml::fromArray($transformed, ['format' => 'tags', 'return' => 'domdocument', 'pretty' => true]);

    $besedila = $XmlObject->getElementsByTagName('Besedilo');

foreach ($besedila as $besedilo) {
    $cDataBesedilo = $XmlObject->createCDATASection($besedilo->nodeValue);
    $besedilo->nodeValue = null;
    $besedilo->appendChild($cDataBesedilo);
}

    echo $XmlObject->saveXML();
