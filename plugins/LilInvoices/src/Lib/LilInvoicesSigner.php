<?php
declare(strict_types=1);

namespace LilInvoices\Lib;

class LilInvoicesSigner
{
    /**
     * @var \DOMDocument $doc
     */
    private $doc = null;
    /**
     * @var bool $xadesAdded
     */
    private $xadesAdded = false;

    /**
     * Constructor
     *
     * @param string $invoiceId Invoice id.
     * @return void
     */
    public function __construct($invoiceId)
    {
        $Exporter = new LilInvoicesExport();
        $invoices = $Exporter->find(['id' => $invoiceId]);
        $invoice = $invoices->first();

        $xml = $Exporter->export('eslog', [$invoice]);

        $this->doc = new \DOMDocument();
        if ($this->doc->loadXML($xml)) {
            $this->addSignature();
            $this->addReferenceSha1('#data', 'http://www.gzs.si/shemas/eslog/racun/1.6#Racun');
        }
    }

    /**
     * Retrieve "SignedInfo" node, canonicalize it to string, return sha256() digest.
     *
     * @return string|bool
     */
    public function getSigningHash()
    {
        if (!$this->xadesAdded) {
            $this->xadesAdded = $this->addXadesReference();
        }

        $signedInfo = $this->doc->getElementsByTagName('ds:SignedInfo')->item(0);
        if ($signedInfo instanceof \DomElement) {
            $data = $signedInfo->C14N(false, false);
            $digest = hash('sha256', $data);

            return $digest;
        }

        return false;
    }

    /**
     * Set signature datetime.
     *
     * @param \Cake\I18n\FrozenTime $time Signature datetime.
     * @return bool
     */
    public function setSignatureDatetime($time)
    {
        $signingTime = $this->doc->getElementsByTagName('xds:SigningTime')->item(0);
        if ($signingTime instanceof \DomElement) {
            $signingTime->nodeValue = $time->toIso8601String();

            return true;
        }

        return false;
    }

    /**
     * Set signature returned by browser
     *
     * @param string $signature Signature string
     * @return bool
     */
    public function setSignature($signature)
    {
        if (!$this->xadesAdded) {
            $this->xadesAdded = $this->addXadesReference();
        }

        $signatureValue = $this->doc->getElementsByTagName('ds:SignatureValue')->item(0);
        if ($signatureValue instanceof \DomElement) {
            $signatureValue->nodeValue = $signature;

            return true;
        }

        return false;
    }

    /**
     * Set public certificate returned by browser
     *
     * @param string $X509Cert Certificate
     * @return bool
     */
    public function setCertificate($X509Cert)
    {
        //$certData = openssl_x509_parse("-----BEGIN CERTIFICATE-----\n" . chunk_split($X509Cert, 64, "\n") . "-----END CERTIFICATE-----\n");
        $certData = openssl_x509_parse($X509Cert);
        if (! empty($certData['issuer']) && ! empty($certData['serialNumber'])) {
            if (is_array($certData['issuer'])) {
                $parts = [];
                foreach ($certData['issuer'] as $key => $value) {
                    array_unshift($parts, "$key=$value");
                }
                $issuerName = implode(', ', $parts);
            } else {
                $issuerName = $certData['issuer'];
            }

            // calc digest hash of public cert
            $arr = explode("\n", trim(str_replace("\r", '', $X509Cert)));
            array_shift($arr);
            array_pop($arr);

            $rawCert = implode(PHP_EOL, $arr);
            $digest = base64_encode(hash('sha1', base64_decode($rawCert), true));

            // add certificate
            $X509Certificate = $this->doc->getElementsByTagName('ds:X509Certificate')->item(0);
            if ($X509Certificate instanceof \DomElement) {
                $X509Certificate->nodeValue = implode('', $arr);
            }

            // add nodes
            $signingCertificate = $this->doc->getElementsByTagName('xds:SigningCertificate')->item(0);
            if ($signingCertificate instanceof \DomElement) {
                $certNode = $this->doc->createElement('xds:Cert');

                $certDigest = $this->doc->createElement('xds:CertDigest');

                $digestMethod = $this->doc->createElement('xds:DigestMethod');
                $digestMethodAlgorithm = $this->doc->createAttribute('Algorithm');
                $digestMethodAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#sha1';
                $digestMethod->appendChild($digestMethodAlgorithm);

                $digestValue = $this->doc->createElement('xds:DigestValue', $digest);

                $certDigest->appendChild($digestMethod);
                $certDigest->appendChild($digestValue);

                $issuerSerial = $this->doc->createElement('xds:IssuerSerial');
                $X509IssuerName = $this->doc->createElement('X509IssuerName', $issuerName);
                $x509IssuerNameXmlns = $this->doc->createAttribute('xmlns');
                $x509IssuerNameXmlns->value = 'http://www.w3.org/2000/09/xmldsig#';
                $X509IssuerName->appendChild($x509IssuerNameXmlns);

                $X509SerialNumber = $this->doc->createElement('X509SerialNumber', $certData['serialNumber']);
                $x509SerialNumberXmlns = $this->doc->createAttribute('xmlns');
                $x509SerialNumberXmlns->value = 'http://www.w3.org/2000/09/xmldsig#';
                $X509SerialNumber->appendChild($x509SerialNumberXmlns);

                $issuerSerial->appendChild($X509IssuerName);
                $issuerSerial->appendChild($X509SerialNumber);

                $certNode->appendChild($certDigest);
                $certNode->appendChild($issuerSerial);

                $signingCertificate->appendChild($certNode);

                return true;
            }
        }

        return false;
    }

    /**
     * Adds reference to Xades block
     *
     * @return bool
     */
    private function addXadesReference()
    {
        return $this->addReferenceSha1('#SignedPropertiesId', 'http://uri.etsi.org/01903#SignedProperties');
    }

    /**
     * Adds SHA1 reference to signature block
     *
     * @param string $uri Id of elements block to add reference for eg. #test for <racun Id="test">
     * @param string $type Type of reference block
     * @return bool
     */
    private function addReferenceSha1($uri, $type)
    {
        $xpath = new \DomXpath($this->doc);
        $node = $xpath->query(sprintf('//*[@Id="%s"]', substr($uri, 1)))->item(0);
        if ($node instanceof \DomElement) {
            $data = $node->C14N();
            $digest = base64_encode(hash('sha1', $data, true));

            $signedInfo = $this->doc->getElementsByTagName('ds:SignedInfo')->item(0);
            if ($signedInfo instanceof \DomElement) {
                $referenceNode = $this->doc->createElement('ds:Reference');
                $referenceURI = $this->doc->createAttribute('URI');
                $referenceURI->value = $uri;
                $referenceType = $this->doc->createAttribute('Type');
                $referenceType->value = $type;
                $referenceNode->appendChild($referenceURI);
                $referenceNode->appendChild($referenceType);

                $digestMethod = $this->doc->createElement('ds:DigestMethod');
                $digestMethodAlgorithm = $this->doc->createAttribute('Algorithm');
                $digestMethodAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#sha1';
                $digestMethod->appendChild($digestMethodAlgorithm);

                $digestValue = $this->doc->createElement('ds:DigestValue', $digest);

                $referenceNode->appendChild($digestMethod);
                $referenceNode->appendChild($digestValue);

                $signedInfo->appendChild($referenceNode);

                return true;
            }
        }

        return false;
    }

    /**
     * Add signature block to XML
     *
     * @return void
     */
    private function addSignature()
    {
        $signature = $this->doc->createElement('ds:Signature');
        $signatureId = $this->doc->createAttribute('Id');
        $signatureId->value = 'SignatureId';
        $signature->appendChild($signatureId);
        $this->doc->documentElement->appendChild($signature);

        $signedInfo = $this->doc->createElement('ds:SignedInfo');
        $signature->appendChild($signedInfo);

        $c14nMethod = $this->doc->createElement('ds:CanonicalizationMethod');
        $c14nAlgorithm = $this->doc->createAttribute('Algorithm');
        $c14nAlgorithm->value = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
        $c14nMethod->appendChild($c14nAlgorithm);
        $signedInfo->appendChild($c14nMethod);

        $signatureMethod = $this->doc->createElement('ds:SignatureMethod');
        $signatureMethodAlgorithm = $this->doc->createAttribute('Algorithm');

        //$signatureMethodAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
        $signatureMethodAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#rsa-sha256';

        $signatureMethod->appendChild($signatureMethodAlgorithm);
        $signedInfo->appendChild($signatureMethod);

        $signatureValue = $this->doc->createElement('ds:SignatureValue');
        $signature->appendChild($signatureValue);

        $keyInfo = $this->doc->createElement('ds:KeyInfo');
        $X509Data = $this->doc->createElement('ds:X509Data');
        $X509Certificate = $this->doc->createElement('ds:X509Certificate');
        $X509Data->appendChild($X509Certificate);
        $keyInfo->appendChild($X509Data);
        $signature->appendChild($keyInfo);

        $objectNode = $this->doc->createElement('ds:Object');

        $qualifyingProperties = $this->doc->createElement('xds:QualifyingProperties');
        $qualifyingPropertiesId = $this->doc->createAttribute('Target');
        $qualifyingPropertiesId->value = '#SignatureId';
        $qualifyingProperties->appendChild($qualifyingPropertiesId);

        $signedProperties = $this->doc->createElement('xds:SignedProperties');
        $signedPropertiesId = $this->doc->createAttribute('Id');
        $signedPropertiesId->value = 'SignedPropertiesId';
        $signedProperties->appendChild($signedPropertiesId);

        $signedSignatureProperties = $this->doc->createElement('xds:SignedSignatureProperties');

        $signingTime = $this->doc->createElement('xds:SigningTime');
        $signingCertificate = $this->doc->createElement('xds:SigningCertificate');
        $signaturePolicyIdentifier = $this->doc->createElement('xds:SignaturePolicyIdentifier');
        $signaturePolicyImplied = $this->doc->createElement('xds:SignaturePolicyImplied');

        $signaturePolicyIdentifier->appendChild($signaturePolicyImplied);

        $signedSignatureProperties->appendChild($signingTime);
        $signedSignatureProperties->appendChild($signingCertificate);
        $signedSignatureProperties->appendChild($signaturePolicyIdentifier);

        $signedProperties->appendChild($signedSignatureProperties);

        $qualifyingProperties->appendChild($signedProperties);

        $objectNode->appendChild($qualifyingProperties);

        $signature->appendChild($objectNode);
    }

    /**
     * Returns xml
     *
     * @return string
     */
    public function getXml()
    {
        return $this->doc->saveXml();
    }

    /**
     * Convert HEX-Pairs string to base64 encoded string
     *
     * @param string $hex Hex pairs
     * @return string
     */
    /*private function hex2base64($hex)
    {
        foreach (str_split($hex, 2) as $pair) {
            $return .= chr(hexdec($pair));
        }

        return base64_encode($return);
    }*/
}
