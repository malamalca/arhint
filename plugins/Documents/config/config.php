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
]];
