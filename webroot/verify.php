<?php

define('DS', '\\');

$doc = new DOMDocument();
$doc->load('C:\Users\Miha Nahtigal\Downloads\Obcina_Trebnje_koledar_eslog (82).xml');


require dirname(dirname(__FILE__)) . DS . 'Plugin' . DS. 'LilInvoices' . DS . 'Lib' . DS . 'xmlseclibs_bes.php';

$objXMLSecDSig = new XMLSecurityDSig();

$objDSig  = $objXMLSecDSig->locateSignature($doc);
if (! $objDSig ) {
	throw new Exception("Cannot locate Signature Node");
}

$objXMLSecDSig->canonicalizeSignedInfo();
//$objXMLSecDSig->idKeys = array('xds:Id');
//$objXMLSecDSig->idNS = array('xds'=>'http://uri.etsi.org/01903/v1.1.1#');

$retVal = $objXMLSecDSig->validateReference();

if (! $retVal) {
	throw new Exception("Reference Validation Failed");
}

$objKey = $objXMLSecDSig->locateKey();
if (! $objKey ) {
	throw new Exception("We have no idea about the key");
}
$key = NULL;

$objKeyInfo = XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);
if (! $objKeyInfo->key && empty($key)) {
	$objKey->loadKey(dirname(__FILE__) . '/mycert.pem', TRUE);
}
if ($objXMLSecDSig->verify($objKey)) {
	print "Signature validated!";
} else {
	print "Failure!!!!!!!!";
}
print "\n";