<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Documents\Lib\DocumentsSigner;
use DOMDocument;
use InvalidArgumentException;
use ReflectionClass;

class DocumentsSignerTest extends TestCase
{
    private string $exampleXmlPath;
    private string $signerServiceUrl = 'http://localhost:8082';

    public function setUp(): void
    {
        parent::setUp();
        $this->exampleXmlPath = dirname(__DIR__, 1) . DS . 'Lib' . DS . 'exampleInvoice.xml';
    }

    /**
     * Test signing XML via arhint-signer-webservice
     *
     * @return void
     */
    public function testSignXml(): void
    {
        // Check if the example XML file exists
        $this->assertFileExists($this->exampleXmlPath, 'Example invoice XML file not found');

        // Check if the signer service is running
        $ch = curl_init($this->signerServiceUrl . '/listCerts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->markTestSkipped('Signer service is not running on port 8082. Start the service at D:\Dev\arhint-signer\webservice');
        }

        $certResponse = json_decode($response, true);
        if (!isset($certResponse['result']) || empty($certResponse['result'])) {
            $this->markTestSkipped('No certificates available from signer service');
        }

        $certificates = $certResponse['result'];
        $firstCert = $certificates[0];

        // Read the example XML
        $xmlContent = file_get_contents($this->exampleXmlPath);
        $this->assertNotFalse($xmlContent, 'Failed to read example XML file');

        // Create a DocumentsSigner instance using reflection to bypass constructor
        $reflection = new ReflectionClass(DocumentsSigner::class);
        $signer = $reflection->newInstanceWithoutConstructor();

        // Set the doc property
        $docProperty = $reflection->getProperty('doc');
        $docProperty->setAccessible(true);
        $doc = new DOMDocument();
        $doc->loadXML($xmlContent);
        $docProperty->setValue($signer, $doc);

        // Set xadesAdded property
        $xadesProperty = $reflection->getProperty('xadesAdded');
        $xadesProperty->setAccessible(true);
        $xadesProperty->setValue($signer, false);

        // Call private methods to add signature structure
        $addSignatureMethod = $reflection->getMethod('addSignature');
        $addSignatureMethod->setAccessible(true);
        $addSignatureMethod->invoke($signer);

        $addReferenceMethod = $reflection->getMethod('addReferenceSha1');
        $addReferenceMethod->setAccessible(true);
        $addReferenceMethod->invoke($signer, '#data', 'http://www.gzs.si/shemas/eslog/racun/1.6#Racun');

        // Set signature datetime
        $signTime = new DateTime('2025-12-31 12:00:00');
        $result = $signer->setSignatureDatetime($signTime);
        $this->assertTrue($result, 'Failed to set signature datetime');

        // Set certificate FIRST (before getting signing hash)
        $certificate = $firstCert['cert'];
        $this->assertNotEmpty($certificate, 'Certificate should not be empty');

        $setCertResult = $signer->setCertificate($certificate);
        $this->assertTrue($setCertResult, 'Failed to set certificate');

        // Get signing hash (this will include the certificate info in the digest)
        $digest = $signer->getSigningHash();
        $this->assertNotFalse($digest, 'Failed to get signing hash');
        $this->assertIsString($digest, 'Digest should be a string');
        $this->assertNotEmpty($digest, 'Digest should not be empty');

        // Call the signer service to sign the hash
        $ch = curl_init($this->signerServiceUrl . '/sign');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'hash' => $digest,
            'thumbprint' => $firstCert['thumbprint'],
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->markTestSkipped('Signer service returned error: ' . $response);
        }

        $responseData = json_decode($response, true);
        $this->assertIsArray($responseData, 'Response should be JSON');

        if (isset($responseData['error'])) {
            $this->fail('Signing failed with error: ' . $responseData['error']);
        }

        $this->assertArrayHasKey('result', $responseData, 'Response should contain result');

        // Set the signature
        $signature = $responseData['result'];
        $this->assertNotEmpty($signature, 'Signature should not be empty');

        $setSignResult = $signer->setSignature($signature);
        $this->assertTrue($setSignResult, 'Failed to set signature');

        // Get the signed XML
        $signedXml = $signer->getXml();
        $this->assertNotEmpty($signedXml, 'Signed XML should not be empty');
        $this->assertStringContainsString('<ds:Signature', $signedXml, 'Signed XML should contain signature');
        $this->assertStringContainsString('<ds:SignatureValue>', $signedXml, 'Signed XML should contain signature value');
        $this->assertStringContainsString($signature, $signedXml, 'Signed XML should contain the signature');

        // Validate the signature immediately (before saving/reloading)
        $validationResult = $signer->validateSignature();
        $this->assertIsArray($validationResult, 'Validation result should be an array');
        $this->assertArrayHasKey('valid', $validationResult, 'Validation result should have "valid" key');

        if (!$validationResult['valid']) {
            $errorMsg = 'Signature validation failed: ' . implode('; ', $validationResult['errors']);
            $this->fail($errorMsg);
        }

        $this->assertTrue($validationResult['valid'], 'Signature should be valid immediately after signing');

        // Store signed XML for reference
        file_put_contents(dirname($this->exampleXmlPath) . DS . 'signedInvoice.xml', $signedXml);
    }

    /**
     * Test fromXml static factory method
     *
     * @return void
     */
    public function testFromXml(): void
    {
        $signedXmlPath = dirname($this->exampleXmlPath) . DS . 'signedInvoice.xml';

        // Check if signed XML exists
        if (!file_exists($signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found. Run testSignXml first.');
        }

        $signedXmlContent = file_get_contents($signedXmlPath);
        $this->assertNotFalse($signedXmlContent, 'Failed to read signed XML file');

        // Test creating DocumentsSigner from XML using the static factory method
        $signer = DocumentsSigner::fromXml($signedXmlContent);

        $this->assertInstanceOf(DocumentsSigner::class, $signer, 'fromXml should return DocumentsSigner instance');

        // Test that we can call methods on the instance
        $xml = $signer->getXml();
        $this->assertNotEmpty($xml, 'Should be able to get XML from instance');
        $this->assertStringContainsString('<ds:Signature', $xml, 'XML should contain signature');

        // Test that validation works
        $validationResult = $signer->validateSignature();
        $this->assertIsArray($validationResult, 'Validation result should be an array');
        $this->assertArrayHasKey('valid', $validationResult, 'Should have valid key');
        $this->assertArrayHasKey('errors', $validationResult, 'Should have errors key');

        // Test with invalid XML
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML provided');
        DocumentsSigner::fromXml('not valid xml');
    }
}
