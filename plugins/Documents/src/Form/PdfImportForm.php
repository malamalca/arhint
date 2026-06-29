<?php
declare(strict_types=1);

namespace Documents\Form;

use App\Lib\AIAssistant;
use App\Model\Entity\User;
use Cake\Form\Schema;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Documents\Lib\PdfInvoiceImport;

/**
 * PdfImportForm - Import an invoice from a PDF file via AI.
 *
 * The uploaded PDF is converted to eSlog 2.0 XML by {@see \Documents\Lib\PdfInvoiceImport} and
 * then handed to the shared eSlog processing pipeline (inherited from {@see EslogImportForm}),
 * so the rest of the import (client lookup, session hand-off, edit prefill) is identical to the
 * plain XML upload.
 */
class PdfImportForm extends EslogImportForm
{
    /**
     * @var string|null Human-readable error when the AI conversion fails or returns too little data.
     */
    public ?string $aiError = null;

    /**
     * @var \Documents\Lib\PdfInvoiceImport|null Injected converter (tests); created on demand otherwise.
     */
    private ?PdfInvoiceImport $converter;

    /**
     * @param \Cake\Http\ServerRequest $request Request object.
     * @param \Documents\Lib\PdfInvoiceImport|null $converter Optional converter override (for testing).
     */
    public function __construct(ServerRequest $request, ?PdfInvoiceImport $converter = null)
    {
        parent::__construct($request);
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
        return $schema->addField('pdf_file', ['type' => 'string'])
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
            ->requirePresence('pdf_file', 'create')
            ->notEmptyFile('pdf_file', __d('documents', 'Please select a PDF file to upload.'))
            ->add('pdf_file', 'validPdfExtension', [
                'rule' => [$this, 'validatePdfExtension'],
                'message' => __d('documents', 'Please upload a valid PDF file.'),
            ])
            ->add('pdf_file', 'readableFile', [
                'rule' => [$this, 'validateReadable'],
                'message' => __d('documents', 'Failed to read the uploaded file.'),
            ]);
    }

    /**
     * Validate that the uploaded file has a .pdf extension.
     *
     * @param mixed $file Uploaded file data (array or UploadedFile object).
     * @return bool
     */
    public function validatePdfExtension(mixed $file): bool
    {
        $fileName = $this->getFileName($file);
        if ($fileName === '') {
            return true;
        }

        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'pdf';
    }

    /**
     * Execute the import process: PDF -> eSlog XML (AI) -> shared eSlog pipeline.
     *
     * @param array<string, mixed> $data Form data including uploaded file.
     * @return bool True on success, false on failure (see {@see $aiError}).
     */
    protected function _execute(array $data): bool
    {
        $this->aiError = null;

        /** @var mixed $uploadedFile */
        $uploadedFile = $this->request->getData('pdf_file');
        if (empty($uploadedFile)) {
            return false;
        }

        $tmpName = $this->getTmpName($uploadedFile);
        if ($tmpName === '') {
            return false;
        }
        $pdfContent = file_get_contents($tmpName);
        if ($pdfContent === false) {
            $this->aiError = __d('documents', 'Failed to read the uploaded file.');

            return false;
        }

        $xml = $this->getConverter()->convert($pdfContent, $this->getFileName($uploadedFile) ?: 'invoice.pdf');
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
