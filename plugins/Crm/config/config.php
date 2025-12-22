<?php

use Cake\I18n\I18n;

$banks = [];
$banks_file = dirname(__FILE__) . DS . I18n::getLocale() . DS . 'banks.php';
if (file_exists($banks_file)) {
    include $banks_file;
}

$country_list = [];
$countries_file = dirname(__FILE__) . DS . I18n::getLocale() . DS . 'countries.php';
if (file_exists($countries_file)) {
    include $countries_file;
}

$zip_list = [];
$zips_file = dirname(__FILE__) . DS . I18n::getLocale() . DS . 'zips.php';
if (file_exists($zips_file)) {
    include $zips_file;
}

$config = [
    'Crm.showSidebar' => true,

    'Crm.emailTypes' => [
        'P' => __d('crm', 'primary'),
        'W' => __d('crm', 'work'),
    ],
    'Crm.phoneTypes' => [
        'P' => __d('crm', 'primary'),
        'M' => __d('crm', 'mobile'),
        'W' => __d('crm', 'work'),
        'F' => __d('crm', 'fax'),
        'H' => __d('crm', 'home'),
    ],
    'Crm.addressTypes' => [
        'P' => __d('crm', 'primary'),
        'H' => __d('crm', 'home'),
        'W' => __d('crm', 'work'),
        'O' => __d('crm', 'temporary'),
    ],
    'Crm.accountTypes' => [
        'P' => __d('crm', 'primary'),
        'B' => __d('crm', 'business'),
    ],

    'Crm.banks' => $banks,
    'Crm.countries' => $country_list,
    'Crm.zip_codes' => $zip_list,

    'Crm.defaultCountry' => 'SI',
    'Crm.googleApiClientId' => '',
    'Crm.googleApiClientSecret' => '',

    'Crm.labelTemplates' => [
        'slo_priporoceno' => 'Priporočena pošta',
        'slo_povratnica' => 'Povratnica',
        'smak_s47' => 'Nalepke Smak S-47',
    ],

    'Crm.emailTemplates' => [
        'dopis' => 'Splošni dopis',
        'slo_pogoji' => 'Projektni pogoji',
        'slo_mnenja' => 'Mnenja',
    ],

    'Crm.email.dopis' => [
        'form' => [
            'subject' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.subject',
                    'options' => [
                        'type' => 'text',
                        'label' => 'Subject' . ':',
                    ],
                ],
            ],
            'body' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.body',
                    'options' => [
                        'type' => 'text',
                        'label' => 'Body' . ':',
                    ],
                ],
            ],
            'atch' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.atch',
                    'options' => [
                        'type' => 'file',
                        'label' => 'Attachment' . ':',
                    ],
                ],
            ],
        ],
    ],

    'Crm.email.slo_pogoji' => [
        'form' => [
            'xls' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.xls',
                    'options' => [
                        'type' => 'file',
                        'label' => 'Priloga 8A [xlsx]' . ':',
                    ],
                ],
            ],
        ],
        'address' => [
            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.opis',
                    'options' => [
                        'type' => 'text',
                        'label' => 'Opis' . ':',
                    ],
                ],
            ],
        ],
    ],
    'Crm.email.slo_mnenja' => [
        'form' => [
            'xls' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.xls',
                    'options' => [
                        'type' => 'file',
                        'label' => 'Priloga 9A [xlsx]' . ':',
                    ],
                ],
            ],
        ],
        'address' => [
            'opis' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.opis',
                    'options' => [
                        'type' => 'text',
                        'label' => 'Opis' . ':',
                    ],
                ],
            ],
            'stPogojev' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.stPogojev',
                    'options' => [
                        'type' => 'text',
                        'label' => 'Opis' . ':',
                    ],
                ],
            ],
            'datumPogojev' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.datumPogojev',
                    'options' => [
                        'type' => 'date',
                        'label' => 'Opis' . ':',
                    ],
                ],
            ],
            'atch' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'data.atch',
                    'options' => [
                        'type' => 'file',
                        'label' => 'Attachment' . ':',
                    ],
                ],
            ],
        ],
    ],

    'Crm.label.slo_priporoceno' => [
        'header' => false,
        'footer' => false,
        'orientation' => 'L',
        'format' => [147, 105],
        'margin' => ['left' => 0, 'top' => 0, 'right' => 0],
    ],

    'Crm.label.slo_povratnica' => [
        'header' => ['margin' => 0, 'lines' => false],
        'footer' => ['margin' => 0, 'lines' => false],
        'orientation' => 'L',
        'format' => [210, 100],
        'margin' => ['left' => 0, 'top' => 0, 'right' => 0],
        'form' => [
            'zip' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'sprejemna_posta',
                    'options' => [
                        'label' => 'Sprejemna pošta' . ':',
                    ],
                ],
            ],
            'date' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'datum',
                    'options' => [
                        'label' => 'Datum' . ':',
                    ],
                ],
            ],
            'address2' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'podnaslov',
                    'options' => [
                        'label' => 'Podnaslov' . ':',
                    ],
                ],
            ],
        ],
    ],

    'Crm.label.smak_s47' => [
        'header' => ['margin' => 0, 'lines' => false],
        'footer' => ['margin' => 0, 'lines' => false],
        'orientation' => 'P',
        'format' => [210, 297],
        'margin' => ['left' => 0, 'top' => 0, 'right' => 0],
        'form' => [
            'start_row' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'start_row',
                    'options' => [
                        'label' => 'Začni pri vrstici' . ':',
                        'default' => 1,
                    ],
                ],
            ],
            'start_col' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'start_col',
                    'options' => [
                        'label' => 'Začni pri stolpcu' . ':',
                        'default' => 1,
                    ],
                ],
            ],
        ],
    ],
];

return $config;
