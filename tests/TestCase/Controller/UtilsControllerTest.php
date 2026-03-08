<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

/**
 * App\Controller\UtilsController Test Case
 *
 * @uses \App\Controller\UtilsController
 */
class UtilsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * Path to test PDF resources shared with LilPdfProcessorTest
     */
    private string $resourcesPath;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resourcesPath = dirname(__DIR__) . DS . 'Lib' . DS . 'resources' . DS;
        $this->enableCsrfToken();
    }

    /**
     * Log in as the given user by injecting an Auth session entry.
     *
     * @param string $userId User UUID
     * @return void
     */
    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Build a Laminas UploadedFile from a local file path.
     *
     * @param string $path Absolute path to the file
     * @param string $clientName Name the client would see
     * @param string $mimeType MIME type of the file
     * @return \Laminas\Diactoros\UploadedFile
     */
    private function makeUploadedFile(string $path, string $clientName, string $mimeType): UploadedFile
    {
        return new UploadedFile(
            $path,
            (int)filesize($path),
            UPLOAD_ERR_OK,
            $clientName,
            $mimeType,
        );
    }

    // -------------------------------------------------------------------------
    // pdfMerge
    // -------------------------------------------------------------------------

    /**
     * Test pdfMerge GET - no action taken, response is OK (or no-content)
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfMerge()
     */
    public function testPdfMergeGet(): void
    {
        $this->login(USER_ADMIN);
        $this->get('/utils/pdf-merge');
        // No POST data → action returns null; CakePHP may render (empty view) or redirect
        // We just verify there is no server-error.
        $this->assertResponseCode(200);
    }

    /**
     * Test pdfMerge POST with no files returns 400
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfMerge()
     */
    public function testPdfMergePostNoFiles(): void
    {
        $this->login(USER_ADMIN);
        $this->post('/utils/pdf-merge', ['file' => []]);
        $this->assertResponseCode(400);
    }

    /**
     * Test pdfMerge POST with a single valid PDF returns JSON filename
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfMerge()
     */
    public function testPdfMergePostSingleFile(): void
    {
        $this->login(USER_ADMIN);
        $file = $this->makeUploadedFile($this->resourcesPath . 'sample.pdf', 'sample.pdf', 'application/pdf');

        $this->post('/utils/pdf-merge', [
            'file' => [$file],
            'filename' => 'merged.pdf',
            'compression' => 'default',
        ]);

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('filename', $body);
        $this->assertStringEndsWith('.pdf', $body['filename']);

        // Clean up the merged file left in TMP
        if (file_exists(TMP . $body['filename'])) {
            unlink(TMP . $body['filename']);
        }
    }

    /**
     * Test pdfMerge POST with two PDFs returns merged JSON filename
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfMerge()
     */
    public function testPdfMergePostMultipleFiles(): void
    {
        $this->login(USER_ADMIN);
        $file1 = $this->makeUploadedFile($this->resourcesPath . 'sample.pdf', 'sample.pdf', 'application/pdf');
        $file2 = $this->makeUploadedFile($this->resourcesPath . 'sample.pdf', 'sample.pdf', 'application/pdf');

        $this->post('/utils/pdf-merge', [
            'file' => [$file1, $file2],
            'filename' => 'merged_two.pdf',
            'compression' => 'default',
        ]);

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('filename', $body);

        if (file_exists(TMP . $body['filename'])) {
            unlink(TMP . $body['filename']);
        }
    }

    /**
     * Test pdfMerge POST with an invalid compression level returns 400
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfMerge()
     */
    public function testPdfMergePostInvalidCompression(): void
    {
        $this->login(USER_ADMIN);
        $file = $this->makeUploadedFile($this->resourcesPath . 'sample.pdf', 'sample.pdf', 'application/pdf');

        $this->post('/utils/pdf-merge', [
            'file' => [$file],
            'compression' => 'invalid_level',
        ]);

        $this->assertResponseCode(400);
    }

    // -------------------------------------------------------------------------
    // pdfSplice
    // -------------------------------------------------------------------------

    /**
     * Test pdfSplice GET - no action taken
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfSplice()
     */
    public function testPdfSpliceGet(): void
    {
        $this->login(USER_ADMIN);
        $this->get('/utils/pdf-splice');
        $this->assertResponseCode(200);
    }

    /**
     * Test pdfSplice POST with no file returns 400
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfSplice()
     */
    public function testPdfSplicePostNoFile(): void
    {
        $this->login(USER_ADMIN);
        $this->post('/utils/pdf-splice', []);
        $this->assertResponseCode(400);
    }

    /**
     * Test pdfSplice POST - single page extraction returns a PDF download
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfSplice()
     */
    public function testPdfSplicePostSinglePage(): void
    {
        $this->login(USER_ADMIN);
        $file = $this->makeUploadedFile($this->resourcesPath . 'sample.pdf', 'sample.pdf', 'application/pdf');

        $this->post('/utils/pdf-splice', [
            'file' => $file,
            'firstPage' => 1,
            'lastPage' => 1,
            'multiPage' => 0,
        ]);

        $this->assertResponseOk();
        $this->assertContentType('application/pdf');
        // Content-Disposition should indicate a download
        $this->assertHeader('Content-Disposition', 'attachment; filename="sample.pdf"');

        if (file_exists(TMP . 'sample.pdf')) {
            unlink(TMP . 'sample.pdf');
        }
    }

    /**
     * Test pdfSplice POST - multi-page extraction returns a ZIP download
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfSplice()
     */
    public function testPdfSplicePostMultiPage(): void
    {
        $this->login(USER_ADMIN);
        $file = $this->makeUploadedFile($this->resourcesPath . 'sample2p.pdf', 'sample2p.pdf', 'application/pdf');

        $this->post('/utils/pdf-splice', [
            'file' => $file,
            'firstPage' => 1,
            'lastPage' => 2,
            'multiPage' => 1,
        ]);

        $this->assertResponseOk();
        $this->assertContentType('application/zip');
        $this->assertHeader('Content-Disposition', 'attachment; filename="sample2p.zip"');

        if (file_exists(TMP . 'sample2p.zip')) {
            unlink(TMP . 'sample2p.zip');
        }
    }

    // -------------------------------------------------------------------------
    // pdfSignClient
    // -------------------------------------------------------------------------

    /**
     * Test pdfSignClient with a non-existent file returns 400
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfSignClient()
     */
    public function testPdfSignClientNotFound(): void
    {
        $this->login(USER_ADMIN);
        $this->get('/utils/pdf-sign-client/nonexistent');
        $this->assertResponseCode(400);
    }

    /**
     * Test pdfSignClient with an existing sign-folder file returns 200
     *
     * @return void
     * @uses \App\Controller\UtilsController::pdfSignClient()
     */
    public function testPdfSignClientFound(): void
    {
        $this->login(USER_ADMIN);
        // Place a temporary PDF into the sign folder
        $signDir = TMP . 'sign' . DS;
        if (!is_dir($signDir)) {
            mkdir($signDir, 0755, true);
        }
        $testName = 'testfile';
        copy($this->resourcesPath . 'sample.pdf', $signDir . $testName . '.pdf');

        $this->get('/utils/pdf-sign-client/' . $testName);
        $this->assertResponseCode(200);

        if (file_exists($signDir . $testName . '.pdf')) {
            unlink($signDir . $testName . '.pdf');
        }
    }
}
