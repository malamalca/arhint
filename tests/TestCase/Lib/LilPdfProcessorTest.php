<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\LilPdfProcessor;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use finfo;
use InvalidArgumentException;

/**
 * App\Lib\LilPdfProcessor Test Case
 */
class LilPdfProcessorTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Lib\LilPdfProcessor
     */
    protected LilPdfProcessor $LilPdfProcessor;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $gsPath = Configure::read('Ghostscript.executable', '/usr/bin/gs');
        if (!file_exists($gsPath)) {
            $this->markTestSkipped('Ghostscript not available at: ' . $gsPath);
        }
        $this->LilPdfProcessor = new LilPdfProcessor();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->LilPdfProcessor);

        parent::tearDown();
    }

    /**
     * Test addFile method
     *
     * @return void
     * @uses \App\Lib\LilPdfProcessor::addFile()
     */
    public function testAddFile(): void
    {
        $this->LilPdfProcessor->addFile(dirname(__FILE__) . DS . 'resources' . DS . 'sample.pdf');
        $this->assertEquals(1, $this->LilPdfProcessor->count());

        $this->expectException(InvalidArgumentException::class);
        $this->LilPdfProcessor->addFile(dirname(__FILE__) . DS . 'resources' . DS . 'nonexistant.pdf');
    }

    /**
     * Test extractPages method
     *
     * @return void
     * @uses \App\Lib\LilPdfProcessor::extractPages()
     */
    public function testExtractPagesToPdf(): void
    {
        $this->LilPdfProcessor->addFile(dirname(__FILE__) . DS . 'resources' . DS . 'sample.pdf');
        $files = $this->LilPdfProcessor->extractPages(1, -1, false, 'sample_extract_test');
        $this->assertIsArray($files);
        $this->assertCount(1, $files);

        $finfo = new finfo(FILEINFO_MIME);
        $this->assertEquals('application/pdf', substr($finfo->file($files[0]), 0, 15));

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Test extractPages method
     *
     * @return void
     * @uses \App\Lib\LilPdfProcessor::extractPages()
     */
    public function testExtractPagesToPng(): void
    {
        $this->LilPdfProcessor->addFile(dirname(__FILE__) . DS . 'resources' . DS . 'sample.pdf');
        $files = $this->LilPdfProcessor->extractPages(1, 1, true, null, LilPdfProcessor::FORMAT_PNG);
        $this->assertIsArray($files);
        $this->assertCount(1, $files);

        $finfo = new finfo(FILEINFO_MIME);
        $this->assertEquals('image/png', substr($finfo->file($files[0]), 0, 9));
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Test extractPages method with multiPage option
     *
     * @return void
     * @uses \App\Lib\LilPdfProcessor::extractPages()
     */
    public function testExtractPagesMultiPage(): void
    {
        $this->LilPdfProcessor->addFile(dirname(__FILE__) . DS . 'resources' . DS . 'sample2p.pdf');
        $files = $this->LilPdfProcessor->extractPages(1, 2, true);
        $this->assertIsArray($files);
        $this->assertCount(2, $files);

        $finfo = new finfo(FILEINFO_MIME);
        foreach ($files as $file) {
            $this->assertFileExists($file);
            $this->assertEquals('application/pdf', substr($finfo->file($file), 0, 15));
            $this->assertMatchesRegularExpression('/sample2p_\d{3}\.pdf$/', $file);
            unlink($file);
        }
    }
}
