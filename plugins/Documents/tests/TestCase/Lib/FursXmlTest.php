<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Documents\Lib\FursClient;
use Documents\Lib\FursXml;
use Documents\Model\Entity\DocumentsClient;
use Documents\Model\Entity\Invoice;
use Documents\Model\Entity\InvoicesItem;
use Documents\Model\Entity\InvoicesTaxConfirmation;
use Documents\Model\Entity\TaxPremise;
use DOMDocument;

class FursXmlTest extends TestCase
{
    private function invoice(): Invoice
    {
        $invoice = new Invoice(['id' => 'i1', 'counter' => 12, 'no' => 'R-2026-12']);
        $invoice->buyer = new DocumentsClient(['tax_no' => 'SI12345678']);
        $invoice->invoices_items = [
            new InvoicesItem(['vat_id' => 'a', 'vat_percent' => 22, 'qty' => 2, 'price' => 50, 'discount' => 0]),
            new InvoicesItem(['vat_id' => 'b', 'vat_percent' => 9.5, 'qty' => 1, 'price' => 10, 'discount' => 0]),
            new InvoicesItem(['vat_id' => 'c', 'vat_percent' => 0, 'qty' => 1, 'price' => 5, 'discount' => 0]),
            new InvoicesItem(['vat_id' => null, 'vat_percent' => null, 'qty' => 1, 'price' => 7, 'discount' => 0]),
        ];
        $total = 0;
        foreach ($invoice->invoices_items as $item) {
            $total += $item->total;
        }
        $invoice->total = $total;

        return $invoice;
    }

    private function confirmation(): InvoicesTaxConfirmation
    {
        return new InvoicesTaxConfirmation([
            'bp_no' => 'PP1',
            'device_no' => 'BLAG1',
            'issuer_taxno' => '10039953',
            'operator_taxno' => '12345678',
            'issued_at' => new DateTime('2026-09-29 08:05:03', 'UTC'),
            'zoi' => '34905bcff14b381039af2e9d7eee54bb',
        ]);
    }

    /**
     * Validate the request element against FURS schema.
     */
    private function assertValidAgainstSchema(string $xml, string $requestElement): void
    {
        $source = new DOMDocument();
        $source->loadXML($xml);
        $node = $source->getElementsByTagNameNS(FursXml::NS_FU, $requestElement)->item(0);
        $this->assertNotNull($node);

        $doc = new DOMDocument();
        $doc->appendChild($doc->importNode($node, true));

        libxml_use_internal_errors(true);
        // the official schema imports xmldsig without a schemaLocation; the signature is added after building
        $schema = (string)preg_replace(
            '#<element ref="ds:Signature"[^>]*/>#',
            '<any namespace="http://www.w3.org/2000/09/xmldsig#" processContents="skip" minOccurs="0"/>',
            (string)file_get_contents(dirname(__DIR__, 3) . '/config/furs/FiscalVerificationSchema.xsd'),
        );
        $valid = $doc->schemaValidateSource($schema);
        $errors = array_map(fn($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();

        $this->assertTrue($valid, implode("\n", $errors));
    }

    public function testNormalizeTaxNo(): void
    {
        $this->assertSame('12345678', FursXml::normalizeTaxNo('SI12345678'));
        $this->assertSame('12345678', FursXml::normalizeTaxNo(' 12 345 678 '));
        $this->assertNull(FursXml::normalizeTaxNo('1234567'));
        $this->assertNull(FursXml::normalizeTaxNo(null));
    }

    public function testZoiStringUsesLocalTimeAndCounter(): void
    {
        $this->assertSame(
            '10039953' . '29.09.2026 10:05:03' . '12' . 'PP1' . 'BLAG1' . '144.95',
            FursXml::zoiString($this->confirmation(), $this->invoice()),
        );
    }

    public function testTaxSpecification(): void
    {
        $spec = FursXml::taxSpecification($this->invoice());

        $this->assertEquals(100, $spec['vat']['22.00']['base']);
        $this->assertEquals(22, $spec['vat']['22.00']['amount']);
        $this->assertEquals(10, $spec['vat']['9.50']['base']);
        $this->assertEquals(5, $spec['nontaxable']);
        $this->assertEquals(7, $spec['exempt']);
    }

    public function testInvoiceRequestMatchesSchema(): void
    {
        $xml = FursXml::invoice($this->invoice(), $this->confirmation());

        $this->assertValidAgainstSchema($xml, 'InvoiceRequest');
        $this->assertStringContainsString('<fu:InvoiceNumber>12</fu:InvoiceNumber>', $xml);
        $this->assertStringContainsString('<fu:IssueDateTime>2026-09-29T10:05:03</fu:IssueDateTime>', $xml);
        $this->assertStringContainsString('<fu:CustomerVATNumber>SI12345678</fu:CustomerVATNumber>', $xml);
        $this->assertStringNotContainsString('SubsequentSubmit', $xml);

        $this->assertStringContainsString(
            '<fu:SubsequentSubmit>true</fu:SubsequentSubmit>',
            FursXml::invoice($this->invoice(), $this->confirmation(), true),
        );
    }

    public function testPremiseRequestMatchesSchema(): void
    {
        $realEstate = new TaxPremise([
            'no' => 'PP1', 'kind' => 'RL', 'casadral_number' => '365', 'building_number' => '12',
            'building_section_number' => '3', 'street' => 'Slakova ulica', 'house_number' => '36',
            'house_number_additional' => 'A', 'community' => 'Trebnje', 'city' => 'Trebnje',
            'postal_code' => '8210', 'validity_date' => new DateTime('2026-10-01'), 'sw_taxno' => '10039953',
        ]);
        $this->assertValidAgainstSchema(FursXml::premise($realEstate, '10039953'), 'BusinessPremiseRequest');

        $moveable = new TaxPremise([
            'no' => 'MO1', 'kind' => 'MO', 'mo_type' => 'B', 'validity_date' => new DateTime('2026-10-01'),
            'sw_taxno' => '10039953', 'closed' => true, 'notes' => 'Kiosk',
        ]);
        $xml = FursXml::premise($moveable, '10039953');
        $this->assertValidAgainstSchema($xml, 'BusinessPremiseRequest');
        $this->assertStringContainsString('<fu:ClosingTag>Z</fu:ClosingTag>', $xml);
    }

    public function testParseResponse(): void
    {
        $ok = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fu="http://www.fu.gov.si/">' .
            '<soapenv:Body><fu:InvoiceResponse><fu:Header/><fu:UniqueInvoiceID>abc-123</fu:UniqueInvoiceID>' .
            '</fu:InvoiceResponse></soapenv:Body></soapenv:Envelope>';
        $this->assertSame(['ok' => true, 'eor' => 'abc-123', 'error' => null], FursClient::parseResponse($ok));

        $error = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fu="http://www.fu.gov.si/">' .
            '<soapenv:Body><fu:InvoiceResponse><fu:Error><fu:ErrorCode>s001</fu:ErrorCode>' .
            '<fu:ErrorMessage>Bad</fu:ErrorMessage></fu:Error></fu:InvoiceResponse></soapenv:Body></soapenv:Envelope>';
        $result = FursClient::parseResponse($error);
        $this->assertFalse($result['ok']);
        $this->assertSame('s001: Bad', $result['error']);

        $this->assertFalse(FursClient::parseResponse('<nonsense')['ok']);
    }

    public function testNonXmlResponseIsReportedAndConvertedToUtf8(): void
    {
        // the FURS firewall answers with an ISO-8859-2 HTML page
        $html = mb_convert_encoding(
            '<html><head><title>Request Rejected</title></head><body>Vaša zahteva je bila zavrnjena.</body></html>',
            'ISO-8859-2',
            'UTF-8',
        );

        $this->assertFalse(mb_check_encoding($html, 'UTF-8'));
        $this->assertSame(
            '<html><head><title>Request Rejected</title></head><body>Vaša zahteva je bila zavrnjena.</body></html>',
            FursClient::toUtf8($html),
        );

        $result = FursClient::parseResponse($html);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Request Rejected', (string)$result['error']);
        $this->assertStringContainsString('Vaša zahteva je bila zavrnjena.', (string)$result['error']);
    }
}
