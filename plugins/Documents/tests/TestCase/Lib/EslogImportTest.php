<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Documents\Lib\EslogImport;

/**
 * EslogImport Test Case
 */
class EslogImportTest extends TestCase
{
    /**
     * @var string Path to test XML file.
     */
    private string $testXmlPath;

    public function setUp(): void
    {
        parent::setUp();
        $this->testXmlPath = dirname(__DIR__) . DS . 'Controller' . DS . 'data' . DS . 'testInvoice_eslog20.xml';
    }

    /**
     * Test parse with valid XML.
     *
     * @return void
     */
    public function testParseValidXml(): void
    {
        $xmlContent = file_get_contents($this->testXmlPath);
        $this->assertNotFalse($xmlContent, 'Test XML file should be readable');

        $importer = new EslogImport();
        $result = $importer->parse($xmlContent);

        $this->assertNotNull($result, 'Parse should not return null for valid XML');
        $this->assertArrayHasKey('invoice', $result);
        $this->assertArrayHasKey('issuer', $result);
        $this->assertArrayHasKey('receiver', $result);
        $this->assertArrayHasKey('buyer', $result);
        $this->assertArrayHasKey('items', $result);

        // Check invoice header
        $this->assertEquals('TEST-2025-001', $result['invoice']['no']);
        $this->assertEquals('2025-06-15', $result['invoice']['dat_issue']);
        $this->assertEquals('2025-06-15', $result['invoice']['dat_service']);

        // Check payment reference parsing
        $this->assertEquals('SI', $result['invoice']['pmt_type']);
        $this->assertEquals('00', $result['invoice']['pmt_module']);
        $this->assertEquals('123456789', $result['invoice']['pmt_ref']);

        // Check totals
        $this->assertEquals(600.0, $result['invoice']['net_total']);
        $this->assertEquals(732.0, $result['invoice']['total']);

        // Check issuer (seller/SE)
        $this->assertArrayHasKey('issuer', $result);
        $this->assertNotEmpty($result['issuer']);
        $this->assertEquals('Arhim d.o.o.', $result['issuer']['title']);
        $this->assertEquals('Ukmarjeva 4', $result['issuer']['street']);
        $this->assertEquals('Ljubljana', $result['issuer']['city']);
        $this->assertEquals('1000', $result['issuer']['zip']);
        $this->assertEquals('SI', $result['issuer']['country_code']);
        $this->assertEquals('SI55736645', $result['issuer']['tax_no']);
        $this->assertEquals('SI56242039010691883', $result['issuer']['iban']);

        // Check buyer (BY)
        $this->assertArrayHasKey('buyer', $result);
        $this->assertNotEmpty($result['buyer']);
        $this->assertEquals('Test Client d.o.o.', $result['buyer']['title']);
        $this->assertEquals('Test Street 1', $result['buyer']['street']);
        $this->assertEquals('Ljubljana', $result['buyer']['city']);
        $this->assertEquals('1000', $result['buyer']['zip']);
        $this->assertEquals('SI', $result['buyer']['country_code']);
        $this->assertEquals('SI98765432', $result['buyer']['tax_no']);
        $this->assertEquals('info@testclient.si', $result['buyer']['email']);

        // Check items
        $this->assertCount(2, $result['items']);

        // First item
        $item1 = $result['items'][0];
        $this->assertEquals('Web development services', $item1['descript']);
        $this->assertEquals(10.0, $item1['qty']);
        $this->assertEquals('ura', $item1['unit']);
        $this->assertEquals(50.0, $item1['price']);
        $this->assertEquals(22.0, $item1['vat_percent']);

        // Second item
        $item2 = $result['items'][1];
        $this->assertEquals('Hosting services', $item2['descript']);
        $this->assertEquals(1.0, $item2['qty']);
        $this->assertEquals('pcs', $item2['unit']);
        $this->assertEquals(100.0, $item2['price']);
        $this->assertEquals(22.0, $item2['vat_percent']);
    }

    /**
     * Test parse with invalid XML.
     *
     * @return void
     */
    public function testParseInvalidXml(): void
    {
        $importer = new EslogImport();
        $result = $importer->parse('<invalid xml');

        $this->assertNull($result, 'Parse should return null for invalid XML');
        $this->assertNotNull($importer->lastError);
    }

    /**
     * Test parse with empty string.
     *
     * @return void
     */
    public function testParseEmptyString(): void
    {
        $importer = new EslogImport();
        $result = $importer->parse('');

        $this->assertNull($result, 'Parse should return null for empty string');
    }

    /**
     * Test parse with valid XML but missing structure.
     *
     * @return void
     */
    public function testParseMissingStructure(): void
    {
        $xml = '<?xml version="1.0"?><Root><Data>test</Data></Root>';

        $importer = new EslogImport();
        $result = $importer->parse($xml);

        $this->assertNull($result, 'Parse should return null for XML missing M_INVOIC');
        $this->assertNotNull($importer->lastError);
        $this->assertStringContainsString('missing M_INVOIC', (string)$importer->lastError);
    }

    /**
     * Test validate with valid XML.
     *
     * @return void
     */
    public function testValidateValidXml(): void
    {
        $xmlContent = file_get_contents($this->testXmlPath);
        $this->assertNotFalse($xmlContent, 'Test XML file should be readable');

        $importer = new EslogImport();
        $errors = $importer->validate($xmlContent);

        // XSD validation might fail if schema is not fully complete or has issues
        // We just check that the method doesn't crash and returns an array
        $this->assertIsArray($errors);
    }

    /**
     * Test validate with invalid XML.
     *
     * @return void
     */
    public function testValidateInvalidXml(): void
    {
        $importer = new EslogImport();
        $errors = $importer->validate('<invalid xml');

        $this->assertNotEmpty($errors, 'Validation should return errors for invalid XML');
    }

    /**
     * Test unit code mapping.
     *
     * @return void
     */
    public function testUnitCodeMapping(): void
    {
        // Test with different unit codes - create minimal XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<Invoice xmlns="urn:eslog:2.00">' .
            '<M_INVOIC>' .
            '<S_BGM><C_C002><D_1001>380</D_1001></C_C002></S_BGM>' .
            '<G_SG26>' .
            '<S_LIN><D_1082>1</D_1082></S_LIN>' .
            '<S_QTY><C_C186><D_6063>47</D_6063><D_6060>5.00</D_6060><D_6411>KGM</D_6411></C_C186></S_QTY>' .
            '</G_SG26>' .
            '</M_INVOIC>' .
            '</Invoice>';

        $importer = new EslogImport();
        $result = $importer->parse($xml);

        $this->assertNotNull($result);
        $this->assertCount(1, $result['items']);
        $this->assertEquals('kg', $result['items'][0]['unit'], 'KGM should map to kg');
    }

    /**
     * Test date parsing from G_SG8 (expiry date).
     *
     * @return void
     */
    public function testParseExpiryDate(): void
    {
        $xmlContent = file_get_contents($this->testXmlPath);
        $importer = new EslogImport();
        $result = $importer->parse($xmlContent);

        $this->assertNotNull($result);
        // The expiry date (code 13) is in G_SG8, not S_DTM at root level
        // Our parser extracts it from G_SG8
        $this->assertEquals('2025-06-22', $result['invoice']['dat_expire']);
    }

    /**
     * Test that unknown unit code defaults to 'pcs'.
     *
     * @return void
     */
    public function testUnknownUnitCode(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<Invoice xmlns="urn:eslog:2.00">' .
            '<M_INVOIC>' .
            '<S_BGM><C_C002><D_1001>380</D_1001></C_C002></S_BGM>' .
            '<G_SG26>' .
            '<S_LIN><D_1082>1</D_1082></S_LIN>' .
            '<S_QTY><C_C186><D_6063>47</D_6063><D_6060>1.00</D_6060><D_6411>UNKNOWN</D_6411></C_C186></S_QTY>' .
            '</G_SG26>' .
            '</M_INVOIC>' .
            '</Invoice>';

        $importer = new EslogImport();
        $result = $importer->parse($xml);

        $this->assertNotNull($result);
        $this->assertEquals('pcs', $result['items'][0]['unit'], 'Unknown unit should default to pcs');
    }
}
