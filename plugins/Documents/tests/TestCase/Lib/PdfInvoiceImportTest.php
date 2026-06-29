<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use App\Lib\AIAssistant;
use Cake\TestSuite\TestCase;
use Documents\Lib\PdfInvoiceImport;
use Exception;

/**
 * PdfInvoiceImport Test Case
 */
class PdfInvoiceImportTest extends TestCase
{
    private const SAMPLE_XML = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<Invoice xmlns="urn:eslog:2.00"><M_INVOIC><S_BGM><C_C106>'
        . '<D_1004>KR-360-47</D_1004></C_C106></S_BGM></M_INVOIC></Invoice>';

    /**
     * Build a PdfInvoiceImport whose AI assistant returns a canned completion.
     *
     * @param string $completion The text the mocked assistant returns.
     * @return \Documents\Lib\PdfInvoiceImport
     */
    private function importerReturning(string $completion): PdfInvoiceImport
    {
        $assistant = $this->createMock(AIAssistant::class);
        $assistant->method('complete')->willReturn($completion);

        return new PdfInvoiceImport($assistant);
    }

    /**
     * A clean XML response is returned as-is.
     *
     * @return void
     */
    public function testConvertSuccess(): void
    {
        $importer = $this->importerReturning(self::SAMPLE_XML);

        $xml = $importer->convert('%PDF-1.4 fake');

        $this->assertNotNull($xml);
        $this->assertStringContainsString('<Invoice', $xml);
        $this->assertStringContainsString('KR-360-47', $xml);
        $this->assertNull($importer->lastError);
    }

    /**
     * Markdown code fences around the XML are stripped.
     *
     * @return void
     */
    public function testConvertStripsCodeFences(): void
    {
        $importer = $this->importerReturning("```xml\n" . self::SAMPLE_XML . "\n```");

        $xml = $importer->convert('%PDF-1.4 fake');

        $this->assertNotNull($xml);
        $this->assertStringStartsWith('<?xml', $xml);
        $this->assertStringNotContainsString('```', $xml);
    }

    /**
     * Prose around the XML document is discarded, keeping only <Invoice>...</Invoice>.
     *
     * @return void
     */
    public function testConvertExtractsXmlFromProse(): void
    {
        $body = '<Invoice xmlns="urn:eslog:2.00"><M_INVOIC></M_INVOIC></Invoice>';
        $importer = $this->importerReturning("Here is the converted invoice:\n" . $body . "\nLet me know if you need more.");

        $xml = $importer->convert('%PDF-1.4 fake');

        $this->assertNotNull($xml);
        $this->assertStringEndsWith('</Invoice>', $xml);
        $this->assertStringNotContainsString('Let me know', $xml);
    }

    /**
     * The CANNOT_PARSE sentinel is treated as a failure.
     *
     * @return void
     */
    public function testConvertCannotParseSentinel(): void
    {
        $importer = $this->importerReturning('<error>CANNOT_PARSE</error>');

        $xml = $importer->convert('%PDF-1.4 fake');

        $this->assertNull($xml);
        $this->assertNotNull($importer->lastError);
    }

    /**
     * A response with no XML document at all is a failure.
     *
     * @return void
     */
    public function testConvertNoXml(): void
    {
        $importer = $this->importerReturning('I am sorry, I cannot help with that.');

        $this->assertNull($importer->convert('%PDF-1.4 fake'));
        $this->assertNotNull($importer->lastError);
    }

    /**
     * Empty PDF content fails fast without calling the AI.
     *
     * @return void
     */
    public function testConvertEmptyPdf(): void
    {
        $assistant = $this->createMock(AIAssistant::class);
        $assistant->expects($this->never())->method('complete');
        $importer = new PdfInvoiceImport($assistant);

        $this->assertNull($importer->convert('   '));
        $this->assertNotNull($importer->lastError);
    }

    /**
     * An AI transport error is caught and surfaced as a friendly message.
     *
     * @return void
     */
    public function testConvertAiException(): void
    {
        $assistant = $this->createMock(AIAssistant::class);
        $assistant->method('complete')->willThrowException(new Exception('connection refused'));
        $importer = new PdfInvoiceImport($assistant);

        $this->assertNull($importer->convert('%PDF-1.4 fake'));
        $this->assertNotNull($importer->lastError);
        $this->assertStringNotContainsString('connection refused', (string)$importer->lastError);
    }
}
