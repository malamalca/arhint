<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Documents\Lib\DocumentsSignatureInfo;
use InvalidArgumentException;

class DocumentsSignatureInfoTest extends TestCase
{
    private string $signedXmlPath;
    private string $exampleXmlPath;

    public function setUp(): void
    {
        parent::setUp();
        $this->signedXmlPath = dirname(__DIR__, 1) . DS . 'Lib' . DS . 'signedInvoice.xml';
        $this->exampleXmlPath = dirname(__DIR__, 1) . DS . 'Lib' . DS . 'exampleInvoice.xml';
    }

    /**
     * Test constructor with valid XML
     *
     * @return void
     */
    public function testConstructorWithValidXml(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found. Run DocumentsSignerTest::testSignXml first.');
        }

        $xmlContent = file_get_contents($this->signedXmlPath);
        $this->assertNotFalse($xmlContent);

        $signatureInfo = new DocumentsSignatureInfo($xmlContent);
        $this->assertInstanceOf(DocumentsSignatureInfo::class, $signatureInfo);
    }

    /**
     * Test constructor with invalid XML
     *
     * @return void
     */
    public function testConstructorWithInvalidXml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML provided');
        new DocumentsSignatureInfo('not valid xml');
    }

    /**
     * Test validationErrorMessage static method
     *
     * @return void
     */
    public function testValidationErrorMessage(): void
    {
        $message = DocumentsSignatureInfo::validationErrorMessage('signature_not_found');
        $this->assertIsString($message);
        $this->assertNotEmpty($message);

        $messageWithParams = DocumentsSignatureInfo::validationErrorMessage('digest_mismatch', ['uri' => '#data']);
        $this->assertIsString($messageWithParams);
        $this->assertNotEmpty($messageWithParams);

        $unknownMessage = DocumentsSignatureInfo::validationErrorMessage('unknown_code');
        $this->assertIsString($unknownMessage);
        $this->assertNotEmpty($unknownMessage);
    }

    /**
     * Test validateSignature method
     *
     * @return void
     */
    public function testValidateSignature(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found. Run DocumentsSignerTest::testSignXml first.');
        }

        $xmlContent = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($xmlContent);

        $result = $signatureInfo->validateSignature();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errorCode', $result);
        $this->assertArrayHasKey('errors', $result);

        if (!$result['valid']) {
            $this->fail('Signature validation failed: ' . implode('; ', $result['errors']));
        }

        $this->assertTrue($result['valid']);
        $this->assertEquals('valid', $result['errorCode']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test validateSignature with skipDataValidation parameter
     *
     * @return void
     */
    public function testValidateSignatureSkipDataValidation(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $xmlContent = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($xmlContent);

        $result = $signatureInfo->validateSignature(skipDataValidation: true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertTrue($result['valid']);
    }

    /**
     * Test getSignatureDate method
     *
     * @return void
     */
    public function testGetSignatureDate(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $xmlContent = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($xmlContent);

        $signatureDate = $signatureInfo->getSignatureDate();

        $this->assertInstanceOf(DateTime::class, $signatureDate);
        $this->assertNotNull($signatureDate);
    }

    /**
     * Test getCertificateInfo method
     *
     * @return void
     */
    public function testGetCertificateInfo(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $xmlContent = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($xmlContent);

        $certInfo = $signatureInfo->getCertificateInfo();

        $this->assertIsArray($certInfo);
        $this->assertArrayHasKey('raw', $certInfo);
        $this->assertArrayHasKey('pem', $certInfo);
        $this->assertArrayHasKey('subject', $certInfo);
        $this->assertArrayHasKey('issuer', $certInfo);
        $this->assertArrayHasKey('serialNumber', $certInfo);

        $this->assertNotEmpty($certInfo['raw']);
        $this->assertNotEmpty($certInfo['pem']);
        $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $certInfo['pem']);
        $this->assertStringContainsString('-----END CERTIFICATE-----', $certInfo['pem']);
    }

    /**
     * Test getXml method
     *
     * @return void
     */
    public function testGetXml(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $xmlContent = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($xmlContent);

        $xml = $signatureInfo->getXml();

        $this->assertIsString($xml);
        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<ds:Signature', $xml);
    }

    /**
     * Test calculateDataDigest method
     *
     * @return void
     */
    public function testCalculateDataDigest(): void
    {
        // We need some XML to test with, use example invoice wrapped with signature structure
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $signedXml = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($signedXml);

        $digest = $signatureInfo->calculateDataDigest($signedXml);

        $this->assertIsString($digest);
        $this->assertNotEmpty($digest);
        // Base64 encoded string
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $digest);
    }

    /**
     * Test calculateDataDigest with invalid XML
     *
     * @return void
     */
    public function testCalculateDataDigestWithInvalidXml(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $signedXml = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($signedXml);

        // XML without data element
        $xmlWithoutData = '<?xml version="1.0"?><root><element>test</element></root>';
        $digest = $signatureInfo->calculateDataDigest($xmlWithoutData);

        $this->assertNull($digest);
    }

    /**
     * Test getSignedDataDigest method
     *
     * @return void
     */
    public function testGetSignedDataDigest(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $xmlContent = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($xmlContent);

        $digest = $signatureInfo->getSignedDataDigest();

        $this->assertIsString($digest);
        $this->assertNotEmpty($digest);
        // Base64 encoded string
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $digest);
    }

    /**
     * Test validateSigningTime method
     *
     * @return void
     */
    public function testValidateSigningTime(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $xmlContent = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($xmlContent);

        // Get the actual signing time from the document
        $actualSigningTime = $signatureInfo->getSignatureDate();
        $this->assertNotNull($actualSigningTime);

        // Test with matching time
        $result = $signatureInfo->validateSigningTime($actualSigningTime);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);

        // Test with different time
        $differentTime = new DateTime('2020-01-01 00:00:00');
        $result = $signatureInfo->validateSigningTime($differentTime);
        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Test compareWithCurrent method with matching data
     *
     * @return void
     */
    public function testCompareWithCurrentMatching(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $signedXml = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($signedXml);

        // Use the same XML as current (should match)
        $result = $signatureInfo->compareWithCurrent($signedXml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errorCode', $result);
        $this->assertArrayHasKey('errors', $result);

        if (!$result['valid']) {
            $this->fail('Comparison failed: ' . implode('; ', $result['errors']));
        }

        $this->assertTrue($result['valid']);
        $this->assertEquals('valid', $result['errorCode']);
    }

    /**
     * Test compareWithCurrent method with signing time
     *
     * @return void
     */
    public function testCompareWithCurrentWithSigningTime(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $signedXml = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($signedXml);

        $actualSigningTime = $signatureInfo->getSignatureDate();
        $this->assertNotNull($actualSigningTime);

        // Test with matching signing time
        $result = $signatureInfo->compareWithCurrent($signedXml, $actualSigningTime);

        $this->assertIsArray($result);
        $this->assertTrue($result['valid']);
        $this->assertEquals('valid', $result['errorCode']);

        // Test with different signing time
        $differentTime = new DateTime('2020-01-01 00:00:00');
        $result = $signatureInfo->compareWithCurrent($signedXml, $differentTime);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertEquals('invalidmetadata', $result['errorCode']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Test compareWithCurrent with modified data
     *
     * @return void
     */
    public function testCompareWithCurrentModifiedData(): void
    {
        if (!file_exists($this->signedXmlPath)) {
            $this->markTestSkipped('Signed XML not found.');
        }

        $signedXml = file_get_contents($this->signedXmlPath);
        $signatureInfo = new DocumentsSignatureInfo($signedXml);

        // Modify the XML content slightly
        $modifiedXml = str_replace('<Stevilka>2025-35</Stevilka>', '<Stevilka>2025-99</Stevilka>', $signedXml);

        $result = $signatureInfo->compareWithCurrent($modifiedXml);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertEquals('invaliddigest', $result['errorCode']);
        $this->assertNotEmpty($result['errors']);
    }
}
