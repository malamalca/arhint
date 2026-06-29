<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Form;

use App\Lib\AIAssistant;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Form\InvoiceImportForm;
use Documents\Lib\PdfInvoiceImport;
use Laminas\Diactoros\UploadedFile;
use const UPLOAD_ERR_OK;

/**
 * InvoiceImportForm Test Case
 *
 * Exercises the single import entry point for both the XML-direct path and the PDF-via-AI path
 * (AI conversion stubbed), covering validation, type detection, AI-error handling, the
 * data-sufficiency check and the session hand-off — without any network access.
 */
class InvoiceImportFormTest extends TestCase
{
    /**
     * @var array<string>
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.Crm.Contacts',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
    ];

    /**
     * Counter owned by COMPANY_FIRST (the admin user's company).
     */
    private const COUNTER_ID = '1d53bc5b-de2d-4e85-b13b-81b39a97fc90';

    /**
     * @var string Path to a throwaway PDF used as the upload source.
     */
    private string $pdfPath;

    public function setUp(): void
    {
        parent::setUp();
        // Unique per test: the UploadedFile stream stays open for the test's lifetime, which on
        // Windows would otherwise lock a shared path and break sibling tests.
        $this->pdfPath = TMP . 'invoice_import_form_test_' . uniqid() . '.pdf';
        file_put_contents($this->pdfPath, '%PDF-1.4 test invoice');
    }

    public function tearDown(): void
    {
        // Force the UploadedFile/Stream handles to close before unlinking on Windows.
        gc_collect_cycles();
        if (file_exists($this->pdfPath)) {
            unlink($this->pdfPath);
        }
        parent::tearDown();
    }

    /**
     * Build a request carrying the identity, counter and an uploaded file.
     *
     * @param string $path Source file path.
     * @param string $name Client file name (its extension drives type detection).
     * @param string $counterId Counter id to place in the request body.
     * @return \Cake\Http\ServerRequest
     */
    private function buildRequest(string $path, string $name, string $counterId = self::COUNTER_ID): ServerRequest
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $uploaded = new UploadedFile($path, (int)filesize($path), UPLOAD_ERR_OK, $name, 'application/octet-stream');

        return (new ServerRequest())
            ->withAttribute('identity', $user)
            ->withParsedBody(['import_file' => $uploaded, 'counter_id' => $counterId]);
    }

    /**
     * Build a request for the throwaway PDF.
     *
     * @param string $counterId Counter id.
     * @return \Cake\Http\ServerRequest
     */
    private function buildPdfRequest(string $counterId = self::COUNTER_ID): ServerRequest
    {
        return $this->buildRequest($this->pdfPath, 'invoice.pdf', $counterId);
    }

    /**
     * Build an InvoiceImportForm whose AI conversion returns the given canned completion.
     *
     * @param \Cake\Http\ServerRequest $request Request.
     * @param string $completion The text the mocked assistant returns.
     * @return \Documents\Form\InvoiceImportForm
     */
    private function formReturning(ServerRequest $request, string $completion): InvoiceImportForm
    {
        $assistant = $this->createMock(AIAssistant::class);
        $assistant->method('complete')->willReturn($completion);

        return new InvoiceImportForm($request, new PdfInvoiceImport($assistant));
    }

    /**
     * An uploaded XML is parsed directly (no AI) and stashed in the session, without a PDF attachment.
     *
     * @return void
     */
    public function testExecuteXmlGoesDirect(): void
    {
        $xmlPath = dirname(__DIR__) . DS . 'Controller' . DS . 'data' . DS . 'testInvoice_eslog20.xml';
        $request = $this->buildRequest($xmlPath, 'invoice.xml');

        // The converter must never be consulted for an XML upload.
        $assistant = $this->createMock(AIAssistant::class);
        $assistant->expects($this->never())->method('complete');
        $form = new InvoiceImportForm($request, new PdfInvoiceImport($assistant));

        $result = $form->execute($request->getData());

        $this->assertTrue($result);
        $this->assertNull($form->aiError);
        $stored = $request->getSession()->read('ImportEslogData');
        $this->assertIsArray($stored);
        $this->assertSame('TEST-2025-001', $stored['invoice']['no']);
        // XML uploads are not attached.
        $this->assertNull($request->getSession()->read('ImportPdfAttachment'));
    }

    /**
     * A PDF is converted via AI, parsed and stashed, and the original PDF is kept for attachment.
     *
     * @return void
     */
    public function testExecutePdfStoresParsedDataAndAttachment(): void
    {
        $xml = (string)file_get_contents(
            dirname(__DIR__) . DS . 'Controller' . DS . 'data' . DS . 'testInvoice_eslog20.xml',
        );
        $request = $this->buildPdfRequest();
        $form = $this->formReturning($request, $xml);

        $result = $form->execute($request->getData());

        $this->assertTrue($result, 'Execute should succeed for a well-formed converted invoice');
        $this->assertNull($form->aiError);
        $stored = $request->getSession()->read('ImportEslogData');
        $this->assertIsArray($stored);
        $this->assertSame('TEST-2025-001', $stored['invoice']['no']);
        $this->assertSame('Web development project', $stored['invoice']['title']);
        // Buyer tax SI98765432 is not in the Contacts fixture for this company.
        $this->assertFalse($form->clientExists);
        $this->assertSame('SI98765432', $form->missingClientInfo['tax_no']);

        // The original PDF is stashed for attachment after the invoice is saved.
        $attachment = $request->getSession()->read('ImportPdfAttachment');
        $this->assertIsArray($attachment);
        $this->assertSame('invoice.pdf', $attachment['name']);
        $this->assertFileExists($attachment['path']);
        unlink($attachment['path']);
    }

    /**
     * Importing an XML clears any pending PDF attachment left over from a previous import.
     *
     * @return void
     */
    public function testXmlImportClearsStalePdfAttachment(): void
    {
        $xmlPath = dirname(__DIR__) . DS . 'Controller' . DS . 'data' . DS . 'testInvoice_eslog20.xml';
        $request = $this->buildRequest($xmlPath, 'invoice.xml');
        $request->getSession()->write('ImportPdfAttachment', ['path' => 'stale', 'name' => 'old.pdf']);

        $assistant = $this->createMock(AIAssistant::class);
        $assistant->expects($this->never())->method('complete');
        $form = new InvoiceImportForm($request, new PdfInvoiceImport($assistant));

        $this->assertTrue($form->execute($request->getData()));
        $this->assertNull($request->getSession()->read('ImportPdfAttachment'));
    }

    /**
     * The CANNOT_PARSE sentinel surfaces an AI error and does not write the session.
     *
     * @return void
     */
    public function testExecuteAiCannotParse(): void
    {
        $request = $this->buildPdfRequest();
        $form = $this->formReturning($request, '<error>CANNOT_PARSE</error>');

        $result = $form->execute($request->getData());

        $this->assertFalse($result);
        $this->assertNotNull($form->aiError);
        $this->assertNull($request->getSession()->read('ImportEslogData'));
    }

    /**
     * A parseable but near-empty AI result is rejected as insufficient.
     *
     * @return void
     */
    public function testExecuteInsufficientData(): void
    {
        $request = $this->buildPdfRequest();
        $form = $this->formReturning(
            $request,
            '<Invoice xmlns="urn:eslog:2.00"><M_INVOIC></M_INVOIC></Invoice>',
        );

        $result = $form->execute($request->getData());

        $this->assertFalse($result);
        $this->assertNotNull($form->aiError);
    }

    /**
     * An invalid counter fails validation before the AI is ever consulted.
     *
     * @return void
     */
    public function testExecuteRejectsForeignCounter(): void
    {
        // Counter 1d53bc...fc89 is owned by a different company.
        $request = $this->buildPdfRequest('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
        $assistant = $this->createMock(AIAssistant::class);
        $assistant->expects($this->never())->method('complete');
        $form = new InvoiceImportForm($request, new PdfInvoiceImport($assistant));

        $result = $form->execute($request->getData());

        $this->assertFalse($result);
    }
}
