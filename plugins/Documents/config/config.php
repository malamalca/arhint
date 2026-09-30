<?php

use Cake\I18n\I18n;

$banks = [];
$banksFile = dirname(__FILE__) . DS . I18n::getLocale() . DS . 'banks.php';
if (file_exists($banksFile)) {
    include $banksFile;
}

$sepaTypes = [];
$sepaTypesFile = dirname(__FILE__) . DS . I18n::getLocale() . DS . 'sepa_types.php';
if (file_exists($sepaTypesFile)) {
    include $sepaTypesFile;
}

$documentTypes = [];
$documentTypesFile = dirname(__FILE__) . DS . I18n::getLocale() . DS . 'document_types.php';
if (file_exists($documentTypesFile)) {
    include $documentTypesFile;
} else {
    include dirname(__FILE__) . DS . 'document_types.php';
}

return ['Documents' => [
    'uploadFolder' => dirname(APP) . DS . 'uploads' . DS . 'Documents',
    'enableScan' => false,
    'banks' => $banks,
    'sepaTypes' => $sepaTypes,
    'documentTypes' => $documentTypes,
    'pdfEngine' => 'WKHTML2PDF',
    'WKHTML2PDF' => [
        'binary' => 'C:\bin\wkhtmltopdf\bin\wkhtmltopdf.exe',
    ],
    // FURS tax confirmation of invoices (override in config/app_local.php)
    'furs' => [
        'production' => false,
        // total request timeout in seconds
        'timeout' => 30,
        'urls' => [
            'test' => 'https://blagajne-test.fu.gov.si:9002/v1/cash_registers',
            'production' => 'https://blagajne.fu.gov.si:9003/v1/cash_registers',
        ],
        // tax number of the software supplier prefilled on new business premises
        'vendorTaxNo' => null,
        'caFiles' => [
            dirname(__FILE__) . DS . 'furs' . DS . 'si-trust-root.crt',
            dirname(__FILE__) . DS . 'furs' . DS . 'sigov-ca2.crt',
        ],
    ],
]];
