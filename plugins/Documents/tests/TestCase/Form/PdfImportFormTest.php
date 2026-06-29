<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Form;

use App\Lib\AIAssistant;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Form\PdfImportForm;
use Documents\Lib\PdfInvoiceImport;
use Laminas\Diactoros\UploadedFile;
use const UPLOAD_ERR_OK;

/**
 * PdfImportForm Test Case
 *
 * Exercises the full PDF import flow with the AI conversion stubbed out, so the form's
 * validation, AI-error handling, data-sufficiency check and session hand-off are covered
 * without any network access.
 */
class PdfImportFormTest extends TestCase
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
        $this->pdfPath = TMP . 'pdf_import_form_test_' . uniqid() . '.pdf';
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
     * Build a request carrying the identity, counter and uploaded PDF the form expects.
     *
     * @param string $counterId Counter id to place in the request body.
     * @return \Cake\Http\ServerRequest
     */
    private function buildRequest(string $counterId = self::COUNTER_ID): ServerRequest
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $uploaded = new UploadedFile(
            $this->pdfPath,
            (int)filesize($this->pdfPath),
            UPLOAD_ERR_OK,
            'invoice.pdf',
            'application/pdf',
        );

        return (new ServerRequest())
            ->withAttribute('identity', $user)
            ->withParsedBody(['pdf_file' => $uploaded, 'counter_id' => $counterId]);
    }

    /**
     * Build a PdfImportForm whose AI conversion returns the given canned completion.
     *
     * @param \Cake\Http\ServerRequest $request Request.
     * @param string $completion The text the mocked assistant returns.
     * @return \Documents\Form\PdfImportForm
     */
    private function formReturning(ServerRequest $request, string $completion): PdfImportForm
    {
        $assistant = $this->createMock(AIAssistant::class);
        $assistant->method('complete')->willReturn($completion);

        return new PdfImportForm($request, new PdfInvoiceImport($assistant));
    }

    /**
     * A valid converted invoice is parsed and stashed in the session for the edit form.
     *
     * @return void
     */
    public function testExecuteStoresParsedDataInSession(): void
    {
        $xml = (string)file_get_contents(
            dirname(__DIR__) . DS . 'Controller' . DS . 'data' . DS . 'testInvoice_eslog20.xml',
        );
        $request = $this->buildRequest();
        $form = $this->formReturning($request, $xml);

        $result = $form->execute($request->getData());

        $this->assertTrue($result, 'Execute should succeed for a well-formed converted invoice');
        $this->assertNull($form->aiError);
        $stored = $request->getSession()->read('ImportEslogData');
        $this->assertIsArray($stored);
        $this->assertSame('TEST-2025-001', $stored['invoice']['no']);
        // Buyer tax SI98765432 is not in the Contacts fixture for this company.
        $this->assertFalse($form->clientExists);
        $this->assertSame('SI98765432', $form->missingClientInfo['tax_no']);
    }

    /**
     * The CANNOT_PARSE sentinel surfaces an AI error and does not write the session.
     *
     * @return void
     */
    public function testExecuteAiCannotParse(): void
    {
        $request = $this->buildRequest();
        $form = $this->formReturning($request, '<error>CANNOT_PARSE</error>');

        $result = $form->execute($request->getData());

        $this->assertFalse($result);
        $this->assertNotNull($form->aiError);
        $this->assertNull($request->getSession()->read('ImportEslogData'));
    }

    /**
     * A parseable but near-empty invoice is rejected as insufficient.
     *
     * @return void
     */
    public function testExecuteInsufficientData(): void
    {
        $request = $this->buildRequest();
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
        $request = $this->buildRequest('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
        $assistant = $this->createMock(AIAssistant::class);
        $assistant->expects($this->never())->method('complete');
        $form = new PdfImportForm($request, new PdfInvoiceImport($assistant));

        $result = $form->execute($request->getData());

        $this->assertFalse($result);
    }
}
