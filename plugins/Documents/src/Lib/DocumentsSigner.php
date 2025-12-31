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
     * @return void
     */
    public function __construct(string $documentId)
    {
        $Exporter = new DocumentsExport();
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

        if (!@$instance->doc->loadXML($xmlString)) {
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

        $signedInfo = $this->doc->getElementsByTagName('ds:SignedInfo')->item(0);
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
        $signingTime = $this->doc->getElementsByTagName('xds:SigningTime')->item(0);
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

        $signatureValue = $this->doc->getElementsByTagName('ds:SignatureValue')->item(0);
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
        $certData = openssl_x509_parse("-----BEGIN CERTIFICATE-----\n" . chunk_split($X509Cert, 64, "\n") . "-----END CERTIFICATE-----\n");
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
            $X509Certificate = $this->doc->getElementsByTagName('ds:X509Certificate')->item(0);
            if ($X509Certificate instanceof DOMElement) {
                $X509Certificate->nodeValue = $X509Cert;
            }

            // add nodes
            $signingCertificate = $this->doc->getElementsByTagName('xds:SigningCertificate')->item(0);

            if ($signingCertificate instanceof DOMElement) {
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

            $signedInfo = $this->doc->getElementsByTagName('ds:SignedInfo')->item(0);
            if ($signedInfo instanceof DOMElement) {
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
    private function addSignature(): void
    {
        $signature = $this->doc->createElement('ds:Signature');
        $signatureId = $this->doc->createAttribute('Id');
        $signatureId->value = 'SignatureId';
        $signature->appendChild($signatureId);
        $this->doc->documentElement?->appendChild($signature);

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
    public function getXml(): string
    {
        return (string)$this->doc->saveXml();
    }

    /**
     * Get validation error message for error code
     *
     * @param string $code Error code
     * @param array<string, mixed> $params Optional parameters for message formatting
     * @return string Error message
     */
    public static function validationErrorMessage(string $code, array $params = []): string
    {
        return match ($code) {
            'signature_not_found' => __d('documents', 'Signature element not found'),
            'signedinfo_not_found' => __d('documents', 'SignedInfo element not found'),
            'digest_missing' => __d('documents', 'Missing DigestValue or DigestMethod for reference {0}', $params['uri'] ?? 'unknown'),
            'element_not_found' => __d('documents', 'Referenced element not found for URI: {0}', $params['uri'] ?? 'unknown'),
            'digest_mismatch' => __d('documents', 'Digest mismatch for reference {0}', $params['uri'] ?? 'unknown'),
            'signaturevalue_not_found' => __d('documents', 'SignatureValue element not found'),
            'signaturevalue_empty' => __d('documents', 'SignatureValue is empty'),
            'certificate_not_found' => __d('documents', 'X509Certificate not found'),
            'certificate_empty' => __d('documents', 'X509Certificate is empty'),
            'certificate_key_failed' => __d('documents', 'Failed to extract public key from certificate'),
            'signaturemethod_not_found' => __d('documents', 'SignatureMethod not found'),
            'signature_invalid' => __d('documents', 'Signature verification failed: signature is invalid'),
            'signature_error' => __d('documents', 'Signature verification error: {0}', $params['error'] ?? 'unknown'),
            'data_modified' => __d('documents', 'Invoice data has been modified after signing'),
            'data_element_not_found' => __d('documents', 'Data element not found in current invoice XML'),
            'signed_xml_parse_failed' => __d('documents', 'Failed to parse signed XML'),
            'signed_digest_not_found' => __d('documents', 'Digest reference for #data not found in signed XML'),
            'nosignature' => __d('documents', 'Document has not been signed'),
            'signing_time_mismatch' => __d('documents', 'Signing time has been modified'),
            default => __d('documents', 'Unknown validation error'),
        };
    }

    /**
     * Validates signature and digest values in the XML document
     *
     * @param bool $skipDataValidation Skip validating the data element digest (used when already validated externally)
     * @return array Returns array with 'valid' boolean, 'errorCode' string, and 'errors' array
     */
    public function validateSignature(bool $skipDataValidation = false): array
    {
        $errors = [];
        $valid = true;
        $errorCode = 'valid';

        // Get signature node
        $signature = $this->doc->getElementsByTagName('ds:Signature')->item(0);
        if (!($signature instanceof DOMElement)) {
            return ['valid' => false, 'errorCode' => 'invalid', 'errors' => [self::validationErrorMessage('signature_not_found')]];
        }

        // Validate digest values for all references
        $signedInfo = $this->doc->getElementsByTagName('ds:SignedInfo')->item(0);
        if (!($signedInfo instanceof DOMElement)) {
            return ['valid' => false, 'errorCode' => 'invalid', 'errors' => [self::validationErrorMessage('signedinfo_not_found')]];
        }

        $references = $signedInfo->getElementsByTagName('ds:Reference');
        for ($i = 0; $i < $references->length; $i++) {
            $reference = $references->item($i);
            if ($reference instanceof DOMElement) {
                $uri = $reference->getAttribute('URI');

                // Skip data validation if requested (already validated externally)
                if ($skipDataValidation && ($uri === '#data' || strpos($uri, 'Racun') !== false)) {
                    continue;
                }

                $digestValueNode = $reference->getElementsByTagName('ds:DigestValue')->item(0);
                $digestMethodNode = $reference->getElementsByTagName('ds:DigestMethod')->item(0);

                if (!($digestValueNode instanceof DOMElement) || !($digestMethodNode instanceof DOMElement)) {
                    $errors[] = self::validationErrorMessage('digest_missing', ['uri' => $uri]);
                    $valid = false;
                    continue;
                }

                $expectedDigest = $digestValueNode->nodeValue;
                $algorithm = $digestMethodNode->getAttribute('Algorithm');

                // Determine hash algorithm
                $hashAlgo = 'sha1'; // default
                if (strpos($algorithm, 'sha256') !== false) {
                    $hashAlgo = 'sha256';
                }

                // Find referenced element
                $xpath = new DOMXPath($this->doc);
                $elementId = substr($uri, 1); // remove leading #
                $query = $xpath->query(sprintf('//*[@Id="%s"]', $elementId));

                if (!$query || $query->length === 0) {
                    $errors[] = self::validationErrorMessage('element_not_found', ['uri' => $uri]);
                    $valid = false;
                    continue;
                }

                $node = $query->item(0);
                if ($node instanceof DOMElement) {
                    // Canonicalize and compute digest
                    $data = $node->C14N();
                    $computedDigest = base64_encode(hash($hashAlgo, $data, true));

                    if ($computedDigest !== $expectedDigest) {
                        $errors[] = self::validationErrorMessage('digest_mismatch', [
                            'uri' => $uri,
                            'expected' => $expectedDigest,
                            'computed' => $computedDigest,
                        ]);
                        $valid = false;
                        // Prioritize error codes: data digest > certificate digest
                        if ($elementId === 'data' || strpos($elementId, 'Racun') !== false) {
                            $errorCode = 'invaliddigest';
                        } elseif ($errorCode === 'valid') {
                            $errorCode = 'invalidcertificatedigest';
                        }
                    }
                }
            }
        }

        // Validate signature value
        $signatureValueNode = $this->doc->getElementsByTagName('ds:SignatureValue')->item(0);
        if (!($signatureValueNode instanceof DOMElement)) {
            return ['valid' => false, 'errorCode' => 'invalid', 'errors' => array_merge($errors, [self::validationErrorMessage('signaturevalue_not_found')])];
        }

        $signatureValue = $signatureValueNode->nodeValue;
        if (empty($signatureValue)) {
            return ['valid' => false, 'errorCode' => 'invalid', 'errors' => array_merge($errors, [self::validationErrorMessage('signaturevalue_empty')])];
        }

        // Get certificate
        $X509CertificateNode = $this->doc->getElementsByTagName('ds:X509Certificate')->item(0);
        if (!($X509CertificateNode instanceof DOMElement)) {
            return ['valid' => false, 'errorCode' => 'invalidcertificate', 'errors' => array_merge($errors, [self::validationErrorMessage('certificate_not_found')])];
        }

        $certData = $X509CertificateNode->nodeValue;
        if (empty($certData)) {
            return ['valid' => false, 'errorCode' => 'invalidcertificate', 'errors' => array_merge($errors, [self::validationErrorMessage('certificate_empty')])];
        }

        // Extract public key from certificate
        $cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split($certData, 64, "\n") . "-----END CERTIFICATE-----\n";
        $publicKey = openssl_pkey_get_public($cert);

        if (!$publicKey) {
            return ['valid' => false, 'errorCode' => 'invalidcertificate', 'errors' => array_merge($errors, [self::validationErrorMessage('certificate_key_failed')])];
        }

        // Get signature method algorithm
        $signatureMethodNode = $signedInfo->getElementsByTagName('ds:SignatureMethod')->item(0);
        if (!($signatureMethodNode instanceof DOMElement)) {
            return ['valid' => false, 'errorCode' => 'invalid', 'errors' => array_merge($errors, [self::validationErrorMessage('signaturemethod_not_found')])];
        }

        $signatureAlgorithm = $signatureMethodNode->getAttribute('Algorithm');

        // Determine OpenSSL signature algorithm
        $opensslAlgo = OPENSSL_ALGO_SHA1; // default
        if (strpos($signatureAlgorithm, 'sha256') !== false) {
            $opensslAlgo = OPENSSL_ALGO_SHA256;
        }

        // Canonicalize SignedInfo
        $signedInfoData = $signedInfo->C14N(false, false);

        // Verify signature
        $signatureBytes = base64_decode($signatureValue);
        $verifyResult = openssl_verify($signedInfoData, $signatureBytes, $publicKey, $opensslAlgo);

        if ($verifyResult === 1) {
            // Signature is valid
        } elseif ($verifyResult === 0) {
            $errors[] = self::validationErrorMessage('signature_invalid');
            $valid = false;
            if ($errorCode === 'valid') {
                $errorCode = 'invalidsignature';
            }
        } else {
            $errors[] = self::validationErrorMessage('signature_error', ['error' => openssl_error_string()]);
            $valid = false;
            if ($errorCode === 'valid') {
                $errorCode = 'invalidsignature';
            }
        }

        return ['valid' => $valid, 'errorCode' => $errorCode, 'errors' => $errors];
    }
}
