<?php
declare(strict_types=1);

namespace Documents\Form;

use App\Lib\AIAssistant;
use App\Model\Entity\User;
use Cake\Core\Plugin;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Documents\Lib\EslogImport;
use Documents\Lib\PdfInvoiceImport;
use DOMDocument;
use Laminas\Diactoros\UploadedFile;

/**
 * InvoiceImportForm - Single entry point for importing invoices from a file.
 *
 * Accepts either an eSlog 2.0 XML file or a PDF. XML is parsed directly; a PDF is first
 * converted to eSlog 2.0 XML with AI ({@see \Documents\Lib\PdfInvoiceImport}). Both paths then
 * feed the same downstream pipeline ({@see processEslogXml()}), so the invoice edit prefill,
 * client lookup and (for PDFs) attachment hand-off are identical.
 */
class InvoiceImportForm extends Form
{
    /**
     * @var \Cake\Http\ServerRequest
     */
    protected ServerRequest $request;

    /**
     * @var array<string, mixed>|null Parsed data from last execute attempt.
     */
    public ?array $parsedData = null;

    /**
     * @var bool Whether the client (buyer/receiver) exists in contacts.
     */
    public bool $clientExists = true;

    /**
     * @var array<string, mixed> Missing client info if client doesn't exist.
     */
    public array $missingClientInfo = [];

    /**
     * @var string|null Human-readable error when AI conversion of a PDF fails or yields too little data.
     */
    public ?string $aiError = null;

    /**
     * @var \Documents\Lib\PdfInvoiceImport|null Injected PDF converter (tests); built on demand otherwise.
     */
    private ?PdfInvoiceImport $converter;

    /**
     * @param \Cake\Http\ServerRequest $request Request object.
     * @param \Documents\Lib\PdfInvoiceImport|null $converter Optional converter override (for testing).
     */
    public function __construct(ServerRequest $request, ?PdfInvoiceImport $converter = null)
    {
        $this->request = $request;
        $this->converter = $converter;
    }

    /**
     * Schema definition.
     *
     * @param \Cake\Form\Schema $schema Schema object.
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema->addField('import_file', ['type' => 'string'])
            ->addField('counter_id', ['type' => 'string']);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator object.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->requirePresence('counter_id', 'create')
            ->notEmptyString('counter_id', __d('documents', 'Counter ID is required.'))
            ->add('counter_id', 'validCounter', [
                'rule' => [$this, 'validateCounter'],
                'message' => __d('documents', 'Invalid counter selected.'),
            ])
            ->requirePresence('import_file', 'create')
            ->notEmptyFile('import_file', __d('documents', 'Please select an XML or PDF file to upload.'))
            ->add('import_file', 'validExtension', [
                'rule' => [$this, 'validateImportExtension'],
                'message' => __d('documents', 'Please upload an eSlog XML or a PDF file.'),
            ])
            ->add('import_file', 'readableFile', [
                'rule' => [$this, 'validateReadable'],
                'message' => __d('documents', 'Failed to read the uploaded file.'),
            ])
            ->add('import_file', 'validContent', [
                'rule' => [$this, 'validateImportContent'],
                'message' => __d('documents', 'The uploaded XML is not a valid eSlog 2.0 invoice.'),
            ]);
    }

    /**
     * Validate that the counter exists and belongs to the current user's company.
     *
     * @param string $counterId Counter ID.
     * @return bool
     */
    public function validateCounter(string $counterId): bool
    {
        if (empty($counterId)) {
            return false;
        }

        /** @var \Documents\Model\Table\DocumentsCountersTable $CountersTable */
        $CountersTable = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

        // Get current user's company_id from identity
        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return false;
        }

        /** @var \App\Model\Entity\User $identity */
        $ownerId = (string)$identity->company_id;

        return $CountersTable->isOwnedBy($counterId, $ownerId);
    }

    /**
     * Validate that the file has an accepted extension (.xml or .pdf).
     *
     * @param mixed $file Uploaded file data (array or UploadedFile object).
     * @return bool
     */
    public function validateImportExtension(mixed $file): bool
    {
        $fileName = $this->getFileName($file);
        if ($fileName === '') {
            return true;
        }

        return in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), ['xml', 'pdf'], true);
    }

    /**
     * Validate that the file is readable.
     *
     * @param mixed $file Uploaded file data (array or UploadedFile object).
     * @return bool
     */
    public function validateReadable(mixed $file): bool
    {
        $tmpName = $this->getTmpName($file);
        if ($tmpName === '') {
            return true;
        }

        return file_get_contents($tmpName) !== false;
    }

    /**
     * Validate the file content. XML must be a valid eSlog 2.0 document; PDFs are validated
     * later by the AI conversion step, so they pass here.
     *
     * @param mixed $file Uploaded file data (array or UploadedFile object).
     * @return bool
     */
    public function validateImportContent(mixed $file): bool
    {
        $tmpName = $this->getTmpName($file);
        if ($tmpName === '') {
            return false;
        }

        $content = file_get_contents($tmpName);
        if ($content === false) {
            return false;
        }

        // PDFs are handled by the AI step, not validated against the eSlog XSD.
        if ($this->isPdf($this->getFileName($file), $content)) {
            return true;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        if (!$dom->loadXML($content, LIBXML_NOCDATA | LIBXML_NONET)) {
            libxml_clear_errors();

            return false;
        }

        $xsdPath = Plugin::path('Documents') . 'webroot' . DS . 'schema' . DS . 'eSLOG20_INVOIC_v200.xsd';
        if (!file_exists($xsdPath)) {
            libxml_clear_errors();

            return false;
        }

        $isValid = $dom->schemaValidate($xsdPath);
        libxml_clear_errors();

        return $isValid;
    }

    /**
     * Get file name from uploaded file (array or UploadedFile object).
     *
     * @param mixed $file Uploaded file data.
     * @return string
     */
    protected function getFileName(mixed $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientFilename() ?: '';
        }
        if (is_array($file)) {
            $name = $file['name'] ?? '';

            return is_string($name) ? $name : '';
        }

        return '';
    }

    /**
     * Get temp file path from uploaded file (array or UploadedFile object).
     *
     * @param mixed $file Uploaded file data.
     * @return string
     */
    protected function getTmpName(mixed $file): string
    {
        if ($file instanceof UploadedFile) {
            $stream = $file->getStream();
            $uri = $stream->getMetadata('uri');

            return is_string($uri) ? $uri : '';
        }
        if (is_array($file)) {
            $tmpName = $file['tmp_name'] ?? '';

            return is_string($tmpName) ? $tmpName : '';
        }

        return '';
    }

    /**
     * Execute the import: detect XML vs PDF, obtain eSlog XML, then run the shared pipeline.
     *
     * @param array<string, mixed> $data Form data including uploaded file.
     * @return bool True on success (parsed and stored), false on failure (see {@see $aiError}).
     */
    protected function _execute(array $data): bool
    {
        $this->aiError = null;

        // Reset any pending attachment from a previous (possibly abandoned) import; the PDF path
        // re-stashes it below, the XML path intentionally leaves it cleared.
        $this->request->getSession()->delete('ImportPdfAttachment');

        /** @var mixed $uploadedFile */
        $uploadedFile = $this->request->getData('import_file');
        if (empty($uploadedFile)) {
            return false;
        }

        $tmpName = $this->getTmpName($uploadedFile);
        if ($tmpName === '') {
            return false;
        }
        $content = file_get_contents($tmpName);
        if ($content === false) {
            $this->aiError = __d('documents', 'Failed to read the uploaded file.');

            return false;
        }

        $filename = $this->getFileName($uploadedFile) ?: 'invoice';

        if ($this->isPdf($filename, $content)) {
            return $this->executePdf($content, $filename);
        }

        return $this->processEslogXml($content);
    }

    /**
     * Convert a PDF to eSlog XML via AI, run the shared pipeline and stash the PDF for attachment.
     *
     * @param string $pdfContent Raw PDF bytes.
     * @param string $filename Original client file name.
     * @return bool
     */
    private function executePdf(string $pdfContent, string $filename): bool
    {
        $xml = $this->getConverter()->convert($pdfContent, $filename);
        if ($xml === null) {
            $this->aiError = $this->getConverter()->lastError
                ?? __d('documents', 'The AI could not process the uploaded invoice.');

            return false;
        }

        if (!$this->processEslogXml($xml)) {
            $this->aiError = __d('documents', 'The AI produced an invoice that could not be read.');

            return false;
        }

        if (!$this->hasSufficientData()) {
            $this->aiError = __d(
                'documents',
                'The AI could not extract enough data from the invoice. Please enter it manually.',
            );

            return false;
        }

        // Stash the original PDF so it can be attached to the invoice once it is saved.
        $this->storePdfForAttachment($pdfContent, $filename);

        return true;
    }

    /**
     * Determine whether an uploaded file is a PDF, by extension or by the %PDF magic header.
     *
     * @param string $filename Client file name.
     * @param string $content Raw file content.
     * @return bool
     */
    private function isPdf(string $filename, string $content): bool
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf'
            || str_starts_with($content, '%PDF');
    }

    /**
     * Parse eSlog 2.0 XML content, check whether the invoice client already exists and stash the
     * parsed payload in the session for transfer to the invoice edit form.
     *
     * @param string $xmlContent Raw eSlog 2.0 XML content.
     * @return bool True when parsed and stored, false when the XML could not be parsed.
     */
    protected function processEslogXml(string $xmlContent): bool
    {
        // Parse the XML using EslogImport library
        $importer = new EslogImport();
        $parsedData = $importer->parse($xmlContent);
        if ($parsedData === null) {
            return false;
        }

        $this->parsedData = $parsedData;

        // Check if client exists by tax number from the receiver/buyer
        /** @var array<string, mixed> $receiver */
        $receiver = $parsedData['receiver'] ?? [];
        /** @var array<string, mixed> $buyer */
        $buyer = $parsedData['buyer'] ?? [];
        $receiverTaxNo = $receiver['tax_no'] ?? '';
        $buyerTaxNo = $buyer['tax_no'] ?? '';
        $taxNo = is_string($receiverTaxNo) ? $receiverTaxNo : (is_string($buyerTaxNo) ? $buyerTaxNo : null);
        $this->clientExists = true;
        $this->missingClientInfo = [];

        if (!empty($taxNo)) {
            // tax_no is unique per owner (see ContactsTable uniqueTax rule), so the
            // lookup must be scoped to the current company to avoid matching another
            // tenant's contact.
            $identity = $this->request->getAttribute('identity');
            /** @var \App\Model\Entity\User|null $identity */
            $ownerId = $identity !== null ? (string)$identity->company_id : '';

            /** @var \Crm\Model\Table\ContactsTable $ContactsTable */
            $ContactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
            $existingContact = $ContactsTable->find()
                ->where([
                    'Contacts.owner_id' => $ownerId,
                    'Contacts.tax_no' => $taxNo,
                ])
                ->first();

            if (empty($existingContact)) {
                $this->clientExists = false;
                $receiverTitle = is_string($receiver['title'] ?? '') ? $receiver['title'] : '';
                $buyerTitle = is_string($buyer['title'] ?? '') ? $buyer['title'] : '';
                $this->missingClientInfo = [
                    'tax_no' => $taxNo,
                    'title' => $receiverTitle ?: $buyerTitle,
                ];
            }
        }

        // Store parsed data in session for transfer to edit(). The counter id and
        // missing-client info travel via the request/query and view variables, so
        // only the parsed payload needs to be persisted here.
        $session = $this->request->getSession();
        $session->write('ImportEslogData', $parsedData);

        return true;
    }

    /**
     * Whether the parsed invoice contains the minimum data worth pre-filling a form with:
     * at least one line item, an identifier (number or issue date) and a named party.
     *
     * @return bool
     */
    private function hasSufficientData(): bool
    {
        $data = $this->parsedData ?? [];
        $invoice = is_array($data['invoice'] ?? null) ? $data['invoice'] : [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $issuer = is_array($data['issuer'] ?? null) ? $data['issuer'] : [];
        $receiver = is_array($data['receiver'] ?? null) ? $data['receiver'] : [];
        $buyer = is_array($data['buyer'] ?? null) ? $data['buyer'] : [];

        $hasItems = $items !== [];
        $hasIdentifier = !empty($invoice['no']) || !empty($invoice['dat_issue']);
        $hasParty = !empty($issuer['title']) || !empty($issuer['tax_no'])
            || !empty($receiver['title']) || !empty($buyer['title']);

        return $hasItems && $hasIdentifier && $hasParty;
    }

    /**
     * Persist the uploaded PDF to a temp location and record it in the session so the invoice
     * edit form can attach it to the invoice after it is first saved.
     *
     * @param string $pdfContent Raw PDF bytes.
     * @param string $filename Original client file name.
     * @return void
     */
    private function storePdfForAttachment(string $pdfContent, string $filename): void
    {
        $dir = TMP . 'import_pdf' . DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir . Text::uuid() . '.pdf';
        if (file_put_contents($path, $pdfContent) === false) {
            // Non-fatal: the import still succeeds, just without the attachment.
            return;
        }

        $this->request->getSession()->write('ImportPdfAttachment', [
            'path' => $path,
            'name' => $filename,
        ]);
    }

    /**
     * Resolve the PDF converter, building one from the current user's AI configuration when no
     * override was injected.
     *
     * @return \Documents\Lib\PdfInvoiceImport
     */
    private function getConverter(): PdfInvoiceImport
    {
        if ($this->converter === null) {
            $identity = $this->request->getAttribute('identity');
            $user = $identity instanceof User ? $identity : null;
            $this->converter = new PdfInvoiceImport(new AIAssistant($user));
        }

        return $this->converter;
    }
}
