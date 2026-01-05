<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\I18n\DateTime;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use ReflectionClass;

class DocumentsSigner
{
    /**
     * XML Digital Signature namespace URI
     */
    private const DS_NS = 'http://www.w3.org/2000/09/xmldsig#';

    /**
     * XAdES namespace URI
     */
    private const XDS_NS = 'http://uri.etsi.org/01903/v1.1.1#';

    /**
     * @var \DOMDocument $doc
     */
    private DOMDocument $doc;
    /**
     * @var bool $xadesAdded
     */
    private ?bool $xadesAdded = false;

    /**
     * Constructor
     *
     * @param string $documentId Document id.
     * @param string $documentType Document type.
     * @return void
     */
    public function __construct(string $documentId, $documentType = 'Invoices')
    {
        $ExporterClass = '\\Documents\\Lib\\' . $documentType . 'Export';
        /** @var \Documents\Lib\InvoicesExport|\Documents\Lib\DocumentsExport $Exporter */
        $Exporter = new $ExporterClass();

        $documents = $Exporter->find(['id' => $documentId], 'Invoices');
        $document = $documents->first();

        $xml = $Exporter->export('xml', [$document]);

        $this->doc = new DOMDocument();
        if ($this->doc->loadXML($xml)) {
            $this->addSignature();
            $this->addReferenceSha1('#data', 'http://www.gzs.si/shemas/eslog/racun/1.6#Racun');
        }
    }

    /**
     * Create DocumentsSigner from XML string
     *
     * @param string $xmlString XML content to load
     * @return self
     */
    public static function fromXml(string $xmlString): self
    {
        $reflection = new ReflectionClass(self::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $instance->doc = new DOMDocument();
        $instance->doc->preserveWhiteSpace = true;
        $instance->doc->formatOutput = false;

        libxml_use_internal_errors(true);
        $loaded = $instance->doc->loadXML($xmlString);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (!$loaded) {
            throw new InvalidArgumentException('Invalid XML provided');
        }

        $instance->xadesAdded = null;

        return $instance;
    }

    /**
     * Retrieve "SignedInfo" node, canonicalize it to string, return sha256() digest.
     *
     * @return string|bool
     */
    public function getSigningHash(): string|bool
    {
        if (!$this->xadesAdded) {
            $this->xadesAdded = $this->addXadesReference();
        }

        $signedInfo = $this->doc->getElementsByTagNameNS(self::DS_NS, 'SignedInfo')->item(0);
        if ($signedInfo instanceof DOMElement) {
            $data = $signedInfo->C14N(false, false);
            $digest = base64_encode(hash('sha256', $data, true));

            return $digest;
        }

        return false;
    }

    /**
     * Set signature datetime.
     *
     * @param \Cake\I18n\DateTime $time Signature datetime.
     * @return bool
     */
    public function setSignatureDatetime(DateTime $time): bool
    {
        $signingTime = $this->doc->getElementsByTagNameNS(self::XDS_NS, 'SigningTime')->item(0);
        if ($signingTime instanceof DOMElement) {
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
    public function setSignature(string $signature): bool
    {
        if (!$this->xadesAdded) {
            $this->xadesAdded = $this->addXadesReference();
        }

        $signatureValue = $this->doc->getElementsByTagNameNS(self::DS_NS, 'SignatureValue')->item(0);
        if ($signatureValue instanceof DOMElement) {
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
    public function setCertificate(string $X509Cert): bool
    {
        $certData = openssl_x509_parse(
            "-----BEGIN CERTIFICATE-----\n" . chunk_split($X509Cert, 64, "\n") . "-----END CERTIFICATE-----\n",
        );
        if (!$certData) {
            dd(openssl_error_string());
        }
        if (!empty($certData['issuer']) && !empty($certData['serialNumber'])) {
            if (is_array($certData['issuer'])) {
                $parts = [];
                foreach ($certData['issuer'] as $key => $value) {
                    array_unshift($parts, "$key=$value");
                }
                $issuerName = implode(', ', $parts);
            } else {
                $issuerName = $certData['issuer'];
            }

            $digest = base64_encode(hash('sha1', base64_decode($X509Cert), true));

            // add certificate
            $X509Certificate = $this->doc->getElementsByTagNameNS(self::DS_NS, 'X509Certificate')->item(0);
            if ($X509Certificate instanceof DOMElement) {
                $X509Certificate->nodeValue = $X509Cert;
            }

            // add nodes
            $signingCertificate = $this->doc->getElementsByTagNameNS(self::XDS_NS, 'SigningCertificate')->item(0);

            if ($signingCertificate instanceof DOMElement) {
                $certNode = $this->doc->createElementNS(self::XDS_NS, 'xds:Cert');

                $certDigest = $this->doc->createElementNS(self::XDS_NS, 'xds:CertDigest');

                $digestMethod = $this->doc->createElementNS(self::XDS_NS, 'xds:DigestMethod');
                $digestMethodAlgorithm = $this->doc->createAttribute('Algorithm');
                $digestMethodAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#sha1';
                $digestMethod->appendChild($digestMethodAlgorithm);

                $digestValue = $this->doc->createElementNS(self::XDS_NS, 'xds:DigestValue', $digest);

                $certDigest->appendChild($digestMethod);
                $certDigest->appendChild($digestValue);

                $issuerSerial = $this->doc->createElementNS(self::XDS_NS, 'xds:IssuerSerial');
                $X509IssuerName = $this->doc->createElementNS(self::DS_NS, 'ds:X509IssuerName', $issuerName);
                $X509SerialNumber = $this->doc->createElementNS(
                    self::DS_NS,
                    'ds:X509SerialNumber',
                    $certData['serialNumber'],
                );

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
    private function addXadesReference(): bool
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
    private function addReferenceSha1(string $uri, string $type): bool
    {
        $xpath = new DOMXPath($this->doc);
        $query = $xpath->query(sprintf('//*[@Id="%s"]', substr($uri, 1)));
        if (!$query) {
            return false;
        }
        $node = $query->item(0);
        if ($node instanceof DOMElement) {
            $data = $node->C14N();
            $digest = base64_encode(hash('sha1', $data, true));

            $signedInfo = $this->doc->getElementsByTagNameNS(self::DS_NS, 'SignedInfo')->item(0);
            if ($signedInfo instanceof DOMElement) {
                $referenceNode = $this->doc->createElementNS(self::DS_NS, 'ds:Reference');
                $referenceURI = $this->doc->createAttribute('URI');
                $referenceURI->value = $uri;
                $referenceType = $this->doc->createAttribute('Type');
                $referenceType->value = $type;
                $referenceNode->appendChild($referenceURI);
                $referenceNode->appendChild($referenceType);

                $digestMethod = $this->doc->createElementNS(self::DS_NS, 'ds:DigestMethod');
                $digestMethodAlgorithm = $this->doc->createAttribute('Algorithm');
                $digestMethodAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#sha1';
                $digestMethod->appendChild($digestMethodAlgorithm);

                $digestValue = $this->doc->createElementNS(self::DS_NS, 'ds:DigestValue', $digest);

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
    private function addSignature(): void
    {
        $signature = $this->doc->createElementNS(self::DS_NS, 'ds:Signature');
        $signatureId = $this->doc->createAttribute('Id');
        $signatureId->value = 'SignatureId';
        $signature->appendChild($signatureId);
        $this->doc->documentElement?->appendChild($signature);

        $signedInfo = $this->doc->createElementNS(self::DS_NS, 'ds:SignedInfo');
        $signature->appendChild($signedInfo);

        $c14nMethod = $this->doc->createElementNS(self::DS_NS, 'ds:CanonicalizationMethod');
        $c14nAlgorithm = $this->doc->createAttribute('Algorithm');
        $c14nAlgorithm->value = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
        $c14nMethod->appendChild($c14nAlgorithm);
        $signedInfo->appendChild($c14nMethod);

        $signatureMethod = $this->doc->createElementNS(self::DS_NS, 'ds:SignatureMethod');
        $signatureMethodAlgorithm = $this->doc->createAttribute('Algorithm');

        //$signatureMethodAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
        $signatureMethodAlgorithm->value = 'http://www.w3.org/2000/09/xmldsig#rsa-sha256';

        $signatureMethod->appendChild($signatureMethodAlgorithm);
        $signedInfo->appendChild($signatureMethod);

        $signatureValue = $this->doc->createElementNS(self::DS_NS, 'ds:SignatureValue');
        $signature->appendChild($signatureValue);

        $keyInfo = $this->doc->createElementNS(self::DS_NS, 'ds:KeyInfo');
        $X509Data = $this->doc->createElementNS(self::DS_NS, 'ds:X509Data');
        $X509Certificate = $this->doc->createElementNS(self::DS_NS, 'ds:X509Certificate');
        $X509Data->appendChild($X509Certificate);
        $keyInfo->appendChild($X509Data);
        $signature->appendChild($keyInfo);

        $objectNode = $this->doc->createElementNS(self::DS_NS, 'ds:Object');

        $qualifyingProperties = $this->doc->createElementNS(self::XDS_NS, 'xds:QualifyingProperties');
        $qualifyingPropertiesId = $this->doc->createAttribute('Target');
        $qualifyingPropertiesId->value = '#SignatureId';
        $qualifyingProperties->appendChild($qualifyingPropertiesId);

        $signedProperties = $this->doc->createElementNS(self::XDS_NS, 'xds:SignedProperties');
        $signedPropertiesId = $this->doc->createAttribute('Id');
        $signedPropertiesId->value = 'SignedPropertiesId';
        $signedProperties->appendChild($signedPropertiesId);

        $signedSignatureProperties = $this->doc->createElementNS(self::XDS_NS, 'xds:SignedSignatureProperties');

        $signingTime = $this->doc->createElementNS(self::XDS_NS, 'xds:SigningTime');
        $signingCertificate = $this->doc->createElementNS(self::XDS_NS, 'xds:SigningCertificate');
        $signaturePolicyIdentifier = $this->doc->createElementNS(self::XDS_NS, 'xds:SignaturePolicyIdentifier');
        $signaturePolicyImplied = $this->doc->createElementNS(self::XDS_NS, 'xds:SignaturePolicyImplied');

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
    public function getXml(): string
    {
        return (string)$this->doc->saveXml();
    }
}
