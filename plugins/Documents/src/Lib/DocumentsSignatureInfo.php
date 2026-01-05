<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\I18n\DateTime;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use InvalidArgumentException;

/**
 * DocumentsSignatureInfo class
 *
 * Analyzes signed XML documents and extracts information from signatures
 */
class DocumentsSignatureInfo
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
     * Constructor
     *
     * @param string $xmlString Signed XML content to analyze
     * @throws \InvalidArgumentException When XML is invalid
     */
    public function __construct(string $xmlString)
    {
        $this->doc = new DOMDocument();
        $this->doc->preserveWhiteSpace = true;
        $this->doc->formatOutput = false;

        libxml_use_internal_errors(true);
        $loaded = $this->doc->loadXML($xmlString);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (!$loaded) {
            throw new InvalidArgumentException('Invalid XML provided');
        }
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
            'digest_missing' => __d('documents', 'Missing DigestValue or DigestMethod for reference {0}',
                $params['uri'] ?? 'unknown'),
            'element_not_found' => __d('documents', 'Referenced element not found for URI: {0}',
                $params['uri'] ?? 'unknown'),
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
     * @return array<string, mixed> Returns array with 'valid' boolean, 'errorCode' string, and 'errors' array
     */
    public function validateSignature(bool $skipDataValidation = false): array
    {
        $errors = [];
        $valid = true;
        $errorCode = 'valid';

        // Get signature node
        $signature = $this->doc->getElementsByTagNameNS(self::DS_NS, 'Signature')->item(0);
        if (!($signature instanceof DOMElement)) {
            return [
                'valid' => false,
                'errorCode' => 'invalid',
                'errors' => [self::validationErrorMessage('signature_not_found')],
            ];
        }

        // Validate digest values for all references
        $signedInfo = $this->doc->getElementsByTagNameNS(self::DS_NS, 'SignedInfo')->item(0);
        if (!($signedInfo instanceof DOMElement)) {
            return [
                'valid' => false,
                'errorCode' => 'invalid',
                'errors' => [self::validationErrorMessage('signedinfo_not_found')],
            ];
        }

        $references = $signedInfo->getElementsByTagNameNS(self::DS_NS, 'Reference');
        for ($i = 0; $i < $references->length; $i++) {
            $reference = $references->item($i);
            if ($reference instanceof DOMElement) {
                $uri = $reference->getAttribute('URI');

                // Skip data validation if requested (already validated externally)
                if ($skipDataValidation && ($uri === '#data' || strpos($uri, 'Racun') !== false)) {
                    continue;
                }

                $digestValueNode = $reference->getElementsByTagNameNS(self::DS_NS, 'DigestValue')->item(0);
                $digestMethodNode = $reference->getElementsByTagNameNS(self::DS_NS, 'DigestMethod')->item(0);

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
        $signatureValueNode = $this->doc->getElementsByTagNameNS(self::DS_NS, 'SignatureValue')->item(0);
        if (!($signatureValueNode instanceof DOMElement)) {
            return [
                'valid' => false,
                'errorCode' => 'invalid',
                'errors' => array_merge($errors, [self::validationErrorMessage('signaturevalue_not_found')]),
            ];
        }

        $signatureValue = $signatureValueNode->nodeValue;
        if (empty($signatureValue)) {
            return [
                'valid' => false,
                'errorCode' => 'invalid',
                'errors' => array_merge($errors, [self::validationErrorMessage('signaturevalue_empty')]),
            ];
        }

        // Get certificate
        $X509CertificateNode = $this->doc->getElementsByTagNameNS(self::DS_NS, 'X509Certificate')->item(0);
        if (!($X509CertificateNode instanceof DOMElement)) {
            return [
                'valid' => false,
                'errorCode' => 'invalidcertificate',
                'errors' => array_merge($errors, [self::validationErrorMessage('certificate_not_found')]),
            ];
        }

        $certData = $X509CertificateNode->nodeValue;
        if (empty($certData)) {
            return [
                'valid' => false,
                'errorCode' => 'invalidcertificate',
                'errors' => array_merge($errors, [self::validationErrorMessage('certificate_empty')]),
            ];
        }

        // Extract public key from certificate
        $cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split($certData, 64, "\n") . "-----END CERTIFICATE-----\n";
        $publicKey = openssl_pkey_get_public($cert);

        if (!$publicKey) {
            return [
                'valid' => false,
                'errorCode' => 'invalidcertificate',
                'errors' => array_merge($errors, [self::validationErrorMessage('certificate_key_failed')]),
            ];
        }

        // Get signature method algorithm
        $signatureMethodNode = $signedInfo->getElementsByTagNameNS(self::DS_NS, 'SignatureMethod')->item(0);
        if (!($signatureMethodNode instanceof DOMElement)) {
            return [
                'valid' => false,
                'errorCode' => 'invalid',
                'errors' => array_merge($errors, [self::validationErrorMessage('signaturemethod_not_found')]),
            ];
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

    /**
     * Extract signature date from the XML
     *
     * @return \Cake\I18n\DateTime|null Returns the signing time or null if not found
     */
    public function getSignatureDate(): ?DateTime
    {
        $signingTime = $this->doc->getElementsByTagNameNS(self::XDS_NS, 'SigningTime')->item(0);
        if ($signingTime instanceof DOMElement && !empty($signingTime->nodeValue)) {
            try {
                return new DateTime($signingTime->nodeValue);
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Extract certificate information from the XML
     *
     * @return array<string, mixed>|null Returns certificate data or null if not found
     */
    public function getCertificateInfo(): ?array
    {
        $X509CertificateNode = $this->doc->getElementsByTagNameNS(self::DS_NS, 'X509Certificate')->item(0);
        if (!($X509CertificateNode instanceof DOMElement) || empty($X509CertificateNode->nodeValue)) {
            return null;
        }

        $certData = $X509CertificateNode->nodeValue;
        $cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split($certData, 64, "\n") . "-----END CERTIFICATE-----\n";

        $parsedCert = openssl_x509_parse($cert);
        if (!$parsedCert) {
            return null;
        }

        $result = [
            'raw' => $certData,
            'pem' => $cert,
            'subject' => [],
            'issuer' => [],
            'validFrom' => null,
            'validTo' => null,
            'serialNumber' => $parsedCert['serialNumber'] ?? null,
            'serialNumberHex' => null,
        ];

        // Convert serial number to hex (handling large numbers)
        if (!empty($parsedCert['serialNumber'])) {
            if (function_exists('gmp_init')) {
                $result['serialNumberHex'] = strtoupper(gmp_strval(gmp_init($parsedCert['serialNumber'], 10), 16));
            } else {
                // Fallback for systems without GMP
                $result['serialNumberHex'] = strtoupper(dechex((int)$parsedCert['serialNumber']));
            }
        }

        // Format subject
        if (!empty($parsedCert['subject'])) {
            if (is_array($parsedCert['subject'])) {
                $result['subject'] = $parsedCert['subject'];
                $result['subjectFormatted'] = $this->formatDistinguishedName($parsedCert['subject']);
            } else {
                $result['subjectFormatted'] = $parsedCert['subject'];
            }
        }

        // Format issuer
        if (!empty($parsedCert['issuer'])) {
            if (is_array($parsedCert['issuer'])) {
                $result['issuer'] = $parsedCert['issuer'];
                $result['issuerFormatted'] = $this->formatDistinguishedName($parsedCert['issuer']);
                // Extract issuer common name
                if (!empty($parsedCert['issuer']['CN'])) {
                    $result['issuerName'] = $parsedCert['issuer']['CN'];
                }
            } else {
                $result['issuerFormatted'] = $parsedCert['issuer'];
            }
        }

        // Valid dates
        if (!empty($parsedCert['validFrom_time_t'])) {
            $result['validFrom'] = new DateTime('@' . $parsedCert['validFrom_time_t']);
        }
        if (!empty($parsedCert['validTo_time_t'])) {
            $result['validTo'] = new DateTime('@' . $parsedCert['validTo_time_t']);
        }

        // Extract common name, email, organization, etc.
        if (!empty($result['subject']['CN'])) {
            $result['commonName'] = $result['subject']['CN'];
        }
        if (!empty($result['subject']['O'])) {
            $result['organization'] = $result['subject']['O'];
        }
        // Extract given name and surname for personal certificates
        if (!empty($result['subject']['GN'])) {
            $result['givenName'] = $result['subject']['GN'];
        }
        if (!empty($result['subject']['SN'])) {
            $result['surname'] = $result['subject']['SN'];
        }
        // Construct full name from given name and surname if available
        if (!empty($result['givenName']) && !empty($result['surname'])) {
            $result['fullName'] = $result['givenName'] . ' ' . $result['surname'];
        }
        if (!empty($parsedCert['extensions']['subjectAltName'])) {
            // Extract email from subject alternative name
            if (preg_match('/email:([^\s,]+)/', $parsedCert['extensions']['subjectAltName'], $matches)) {
                $result['email'] = $matches[1];
            }
        }

        return $result;
    }

    /**
     * Format distinguished name from certificate data
     *
     * @param array<string, mixed> $dn Distinguished name array
     * @return string Formatted distinguished name
     */
    private function formatDistinguishedName(array $dn): string
    {
        $parts = [];
        foreach ($dn as $key => $value) {
            array_unshift($parts, "$key=$value");
        }

        return implode(', ', $parts);
    }

    /**
     * Get the raw XML string
     *
     * @return string
     */
    public function getXml(): string
    {
        return (string)$this->doc->saveXml();
    }

    /**
     * Compare current invoice XML with signed XML to verify data integrity
     *
     * @param string $currentXml Current XML generated from invoice data
     * @param \Cake\I18n\DateTime|null $expectedSigningTime Expected signing time from database
     * @return array<string, mixed> Array with 'valid' boolean, 'errorCode' string, and 'errors' array
     */
    public function compareWithCurrent(string $currentXml, ?DateTime $expectedSigningTime = null): array
    {
        $errors = [];
        $errorCode = 'valid';

        try {
            // Calculate digest of current data
            $currentDigest = $this->calculateDataDigest($currentXml);
            if ($currentDigest === null) {
                return [
                    'valid' => false,
                    'errorCode' => 'invalid',
                    'errors' => [self::validationErrorMessage('data_element_not_found')],
                ];
            }

            // Extract digest from signed XML
            $signedDigest = $this->getSignedDataDigest();
            if ($signedDigest === null) {
                return [
                    'valid' => false,
                    'errorCode' => 'invalid',
                    'errors' => [self::validationErrorMessage('signed_digest_not_found')],
                ];
            }

            // Compare digests
            if ($currentDigest !== $signedDigest) {
                $errorCode = 'invaliddigest';
                $errors[] = self::validationErrorMessage('data_modified', [
                    'current' => $currentDigest,
                    'signed' => $signedDigest,
                ]);

                return ['valid' => false, 'errorCode' => $errorCode, 'errors' => $errors];
            }

            // Data matches, now check signing time if provided
            if ($expectedSigningTime !== null) {
                $signingTimeResult = $this->validateSigningTime($expectedSigningTime);
                if (!$signingTimeResult['valid']) {
                    $errorCode = 'invalidmetadata';
                    $errors = array_merge($errors, $signingTimeResult['errors']);
                }
            }

            // Finally validate the signature itself
            $signatureResult = $this->validateSignature(skipDataValidation: true);
            if (!$signatureResult['valid']) {
                if ($errorCode === 'valid') {
                    $errorCode = $signatureResult['errorCode'];
                }
                $errors = array_merge($errors, $signatureResult['errors']);
            }

            return [
                'valid' => empty($errors),
                'errorCode' => empty($errors) ? 'valid' : $errorCode,
                'errors' => $errors,
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'errorCode' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Calculate digest of the data element from XML string
     *
     * @param string $xmlString XML content containing data element
     * @return string|null Base64-encoded SHA1 digest or null if data element not found
     */
    public function calculateDataDigest(string $xmlString): ?string
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmlString);

        $xpath = new DOMXPath($doc);
        $queryResult = $xpath->query('//*[@Id="data"]');
        if ($queryResult === false || $queryResult->length === 0) {
            return null;
        }

        $dataNode = $queryResult->item(0);
        if (!($dataNode instanceof DOMElement)) {
            return null;
        }

        return base64_encode(hash('sha1', $dataNode->C14N(), true));
    }

    /**
     * Extract the data digest from the signed XML
     *
     * @return string|null Base64-encoded digest or null if not found
     */
    public function getSignedDataDigest(): ?string
    {
        $digestValues = $this->doc->getElementsByTagNameNS(self::DS_NS, 'DigestValue');

        // Find the one belonging to the #data reference
        for ($i = 0; $i < $digestValues->length; $i++) {
            $digestNode = $digestValues->item($i);
            if (!($digestNode instanceof DOMElement)) {
                continue;
            }
            $refNode = $digestNode->parentNode; // Should be ds:Reference
            if (
                $refNode instanceof DOMElement &&
                $refNode->hasAttribute('URI') &&
                $refNode->getAttribute('URI') === '#data'
            ) {
                return trim($digestNode->nodeValue ?? '');
            }
        }

        return null;
    }

    /**
     * Validate that the signing time in XML matches expected time
     *
     * @param \Cake\I18n\DateTime $expectedTime Expected signing time
     * @return array<string, mixed> Array with 'valid' boolean and 'errors' array
     */
    public function validateSigningTime(DateTime $expectedTime): array
    {
        $signingTimeNodes = $this->doc->getElementsByTagNameNS(self::XDS_NS, 'SigningTime');
        if ($signingTimeNodes->length === 0) {
            return ['valid' => true, 'errors' => []];
        }

        $signingTimeNode = $signingTimeNodes->item(0);
        $signingTimeXml = $signingTimeNode instanceof DOMElement ?
            trim($signingTimeNode->nodeValue ?? '') : '';

        $expectedTimeStr = $expectedTime->format('c');

        if ($expectedTimeStr !== $signingTimeXml) {
            return [
                'valid' => false,
                'errors' => [
                    self::validationErrorMessage('signing_time_mismatch', [
                        'database' => $expectedTimeStr,
                        'xml' => $signingTimeXml,
                    ]),
                ],
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Get human-readable signature status message
     *
     * @param array<string, mixed> $data Validation result data
     * @return string Status message
     */
    public static function signatureStatusMessage(array $data): string
    {
        switch ($data['status']) {
            case 'valid':
                $cert = $data['signatureInfo']['certificate'] ?? [];
                $parts = [];

                // Use fullName if available (from givenName + surname), otherwise use commonName
                $name = $cert['fullName'] ?? $cert['commonName'] ?? null;
                if (!empty($name)) {
                    if (!empty($cert['organization'])) {
                        $parts[] = h($name) . ' (' . h($cert['organization']) . ')';
                    } else {
                        $parts[] = h($name);
                    }
                } elseif (!empty($cert['organization'])) {
                    $parts[] = h($cert['organization']);
                }

                if (!empty($cert['issuerName'])) {
                    $parts[] = __d('documents', 'Issuer: {0}', h($cert['issuerName']));
                }

                if (!empty($cert['serialNumberHex'])) {
                    $parts[] = __d('documents', 'Serial: {0}', h($cert['serialNumberHex']));
                }

                $description = implode(' · ', $parts);

                return __d('documents', 'Valid Signature') .
                    (!empty($description) ? ' <span class="light">(' . $description . ')</span>' : '');
            case 'nosignature':
                return __d('documents', 'No Signature');
            default:
                return __d('documents', 'Invalid Signature');
        }
    }
}
