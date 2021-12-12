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
    'LilCrm.showSidebar' => true,

    'LilCrm.emailTypes' => [
        'P' => __d('lil_crm', 'primary'),
        'W' => __d('lil_crm', 'work'),
    ],
    'LilCrm.phoneTypes' => [
        'P' => __d('lil_crm', 'primary'),
        'M' => __d('lil_crm', 'mobile'),
        'W' => __d('lil_crm', 'work'),
        'F' => __d('lil_crm', 'fax'),
        'H' => __d('lil_crm', 'home'),
    ],
    'LilCrm.addressTypes' => [
        'P' => __d('lil_crm', 'primary'),
        'H' => __d('lil_crm', 'home'),
        'W' => __d('lil_crm', 'work'),
        'O' => __d('lil_crm', 'temporary'),
    ],
    'LilCrm.accountTypes' => [
        'P' => __d('lil_crm', 'primary'),
        'B' => __d('lil_crm', 'business'),
    ],

    'LilCrm.banks' => $banks,
    'LilCrm.countries' => $country_list,
    'LilCrm.zip_codes' => $zip_list,

    'LilCrm.defaultCountry' => 'SI',
    'LilCrm.googleApiClientId' => '',
    'LilCrm.googleApiClientSecret' => '',

    'LilCrm.labelTemplates' => [
        'slo_priporoceno' => 'Priporočena pošta',
        'slo_povratnica' => 'Povratnica',
        'smak_s47' => 'Nalepke Smak S-47',
    ],

    'LilCrm.label.slo_priporoceno' => [
        'header' => false,
        'footer' => false,
        'orientation' => 'L',
        'format' => [147, 105],
        'margin' => ['left' => 0, 'top' => 0, 'right' => 0],
    ],

    'LilCrm.label.slo_povratnica' => [
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

    'LilCrm.label.smak_s47' => [
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
