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

return ['LilInvoices' => [
    'uploadFolder' => dirname(APP) . DS . 'uploads' . DS . 'Invoices',
    'enableScan' => false,
    'banks' => $banks,
    'sepaTypes' => $sepaTypes,
    'documentTypes' => $documentTypes,
    'invoiceDocTypes' => ['IV', 'AAB', 'CD'],
    'pdfEngine' => 'WKHTML2PDF',
    'TCPDF' => [
        'user-style-sheet' => dirname(dirname(__FILE__)) . DS . 'webroot' . DS . 'css' . DS . 'lil_invoices_pdf.css',
    ],
    'WKHTML2PDF' => [
        'binary' => 'C:\bin\wkhtmltopdf\bin\wkhtmltopdf.exe',
        'no-outline', // Make Chrome not complain
        'print-media-type',
        'dpi' => 96,
        'margin-top' => 30,
        'margin-right' => 0,
        'margin-bottom' => 20,
        'margin-left' => 0,

        // Default page options
        'disable-smart-shrinking',
        'user-style-sheet' => dirname(dirname(__FILE__)) . DS . 'webroot' . DS . 'css' . DS . 'lil_invoices_pdf.css',
    ],
]];
