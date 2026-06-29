<?php
declare(strict_types=1);

namespace Documents\Form;

use Cake\Core\Plugin;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Documents\Lib\EslogImport;
use DOMDocument;
use Laminas\Diactoros\UploadedFile;

/**
 * EslogImportForm - Form for importing eSlog 2.0 XML invoices.
 */
class EslogImportForm extends Form
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
     * Form constructor.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     */
    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Schema definition.
     *
     * @param \Cake\Form\Schema $schema Schema object.
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema->addField('eslog_file', ['type' => 'string'])
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
            ->requirePresence('eslog_file', 'create')
            ->notEmptyFile('eslog_file', __d('documents', 'Please select an XML file to upload.'))
            ->add('eslog_file', 'validXmlExtension', [
                'rule' => [$this, 'validateXmlExtension'],
                'message' => __d('documents', 'Please upload a valid XML file.'),
            ])
            ->add('eslog_file', 'readableFile', [
                'rule' => [$this, 'validateReadable'],
                'message' => __d('documents', 'Failed to read the uploaded file.'),
            ])
            ->add('eslog_file', 'validEslogXml', [
                'rule' => [$this, 'validateEslogSchema'],
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
     * Validate that the file has .xml extension.
     *
     * @param mixed $file Uploaded file data (array or UploadedFile object).
     * @return bool
     */
    public function validateXmlExtension(mixed $file): bool
    {
        $fileName = $this->getFileName($file);
        if ($fileName === '') {
            return true;
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return $extension === 'xml';
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
     * Validate that the XML is a valid eSlog 2.0 invoice against XSD schema.
     *
     * @param mixed $file Uploaded file data (array or UploadedFile object).
     * @return bool
     */
    public function validateEslogSchema(mixed $file): bool
    {
        $tmpName = $this->getTmpName($file);
        if ($tmpName === '') {
            return false;
        }

        $xmlContent = file_get_contents($tmpName);
        if ($xmlContent === false) {
            return false;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        if (!$dom->loadXML($xmlContent, LIBXML_NOCDATA | LIBXML_NONET)) {
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
     * Execute the import process.
     *
     * @param array<string, mixed> $data Form data including uploaded file.
     * @return bool True on success (parsed and stored), false on failure.
     */
    protected function _execute(array $data): bool
    {
        // Get uploaded file info from request
        /** @var mixed|null $uploadedFile */
        $uploadedFile = $this->request->getData('eslog_file');
        if (empty($uploadedFile)) {
            return false;
        }

        // Read file content
        $tmpName = $this->getTmpName($uploadedFile);
        if ($tmpName === '') {
            return false;
        }
        $xmlContent = file_get_contents($tmpName);
        if ($xmlContent === false) {
            return false;
        }

        return $this->processEslogXml($xmlContent);
    }

    /**
     * Parse eSlog 2.0 XML content, check whether the invoice client already exists and stash the
     * parsed payload in the session for transfer to the invoice edit form.
     *
     * Shared by the XML upload flow and the PDF-to-XML (AI) flow.
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
}
