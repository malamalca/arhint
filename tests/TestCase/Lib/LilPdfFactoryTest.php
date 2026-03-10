<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\LilPdfFactory;
use App\Lib\PdfEngines\LilTCPDFEngine;
use App\Lib\PdfEngines\LilWKHTML2PDFEngine;
use Cake\TestSuite\TestCase;

/**
 * App\Lib\LilPdfFactory Test Case
 */
class LilPdfFactoryTest extends TestCase
{
    /**
     * The TCPDF engine name returns a LilTCPDFEngine instance.
     */
    public function testCreateTcpdf(): void
    {
        $engine = LilPdfFactory::create('TCPDF', []);
        $this->assertInstanceOf(LilTCPDFEngine::class, $engine);
    }

    /**
     * An unrecognised engine name falls back to LilWKHTML2PDFEngine.
     */
    public function testCreateDefaultFallback(): void
    {
        $engine = LilPdfFactory::create('SomeUnknownEngine', []);
        $this->assertInstanceOf(LilWKHTML2PDFEngine::class, $engine);
    }

    /**
     * The empty-string engine name falls back to LilWKHTML2PDFEngine.
     */
    public function testCreateEmptyStringFallback(): void
    {
        $engine = LilPdfFactory::create('', []);
        $this->assertInstanceOf(LilWKHTML2PDFEngine::class, $engine);
    }
}
