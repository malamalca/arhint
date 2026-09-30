<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Documents\Lib\FursClient;
use Documents\Lib\FursXml;
use Documents\Model\Entity\InvoicesTaxConfirmation;
use Malamalca\FiscalPHP\FiscalQr;
use Throwable;

/**
 * InvoicesTaxConfirmations Model
 *
 * @method \Documents\Model\Entity\InvoicesTaxConfirmation newEmptyEntity()
 */
class InvoicesTaxConfirmationsTable extends Table
{
    public const ERROR_NO_CERTIFICATE = 'CONFIRMATION_ERROR_NO_CERTIFICATE';
    public const ERROR_NO_PREMISE = 'CONFIRMATION_ERROR_NO_PREMISE';
    public const ERROR_TAXNO = 'CONFIRMATION_ERROR_TAXNO';
    public const ERROR_SIGN = 'CONFIRMATION_ERROR_SIGN';
    public const ERROR_REQUEST = 'CONFIRMATION_ERROR_REQUEST';
    public const ERROR_XML = 'CONFIRMATION_ERROR_XML';

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('invoices_tax_confirmations');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Documents.Invoices', ['foreignKey' => 'invoice_id']);
    }

    /**
     * Find confirmation of invoice.
     *
     * @param string $invoiceId Invoice id.
     * @return \Documents\Model\Entity\InvoicesTaxConfirmation|null
     */
    public function findForInvoice(string $invoiceId): ?InvoicesTaxConfirmation
    {
        /** @var \Documents\Model\Entity\InvoicesTaxConfirmation|null $confirmation */
        $confirmation = $this->find()->where(['invoice_id' => $invoiceId])->first();

        return $confirmation;
    }

    /**
     * Create ZOI, sign and send invoice to FURS. Idempotent: an already confirmed invoice is never resent.
     * Failures are stored in the confirmation record (`error_code`) so the confirmation can be retried.
     *
     * @param string $invoiceId Invoice id.
     * @param string|null $userId User whose certificate is used.
     * @return \Documents\Model\Entity\InvoicesTaxConfirmation
     */
    public function signAndSend(string $invoiceId, ?string $userId): InvoicesTaxConfirmation
    {
        /** @var \Documents\Model\Table\InvoicesTable $Invoices */
        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        /** @var \Documents\Model\Entity\Invoice $invoice */
        $invoice = $Invoices->get($invoiceId, contain: ['DocumentsCounters', 'Issuers', 'Buyers', 'InvoicesItems']);

        $confirmation = $this->findForInvoice($invoiceId);
        if ($confirmation && $confirmation->isConfirmed()) {
            return $confirmation;
        }

        $isRetry = $confirmation !== null;
        if (!$confirmation) {
            $confirmation = $this->newEmptyEntity();
            $confirmation->invoice_id = $invoice->id;
            $confirmation->issued_at = new DateTime();
        }
        $confirmation->user_id = $userId;

        /** @var \Documents\Model\Table\TaxPremisesTable $TaxPremises */
        $TaxPremises = TableRegistry::getTableLocator()->get('Documents.TaxPremises');
        $premise = empty($invoice->documents_counter->tax_premise_id) ? null :
            $TaxPremises->find()->where(['id' => $invoice->documents_counter->tax_premise_id])->first();
        if (!$premise) {
            return $this->fail($confirmation, self::ERROR_NO_PREMISE, __d('documents', 'Business premise is not set.'));
        }
        $confirmation->bp_no = $premise->no;
        $confirmation->device_no = $invoice->documents_counter->device_no;

        $issuerTaxNo = FursXml::normalizeTaxNo($invoice->issuer->tax_no ?? null);
        if ($issuerTaxNo === null) {
            return $this->fail($confirmation, self::ERROR_TAXNO, __d('documents', 'Issuer tax number is invalid.'));
        }
        $confirmation->issuer_taxno = $issuerTaxNo;

        /** @var \Documents\Model\Table\TaxCertificatesTable $TaxCertificates */
        $TaxCertificates = TableRegistry::getTableLocator()->get('Documents.TaxCertificates');
        $client = $userId ? $TaxCertificates->clientForUser($userId) : null;
        if (!$client) {
            return $this->fail(
                $confirmation,
                self::ERROR_NO_CERTIFICATE,
                __d('documents', 'Certificate for tax confirmation is not available.'),
            );
        }
        $confirmation->operator_taxno = $TaxCertificates->findForUser((string)$userId)?->tax_no;

        if (empty($confirmation->zoi)) {
            $zoi = $client->zoi(FursXml::zoiString($confirmation, $invoice));
            if ($zoi === null) {
                return $this->fail($confirmation, self::ERROR_SIGN, __d('documents', 'ZOI could not be calculated.'));
            }
            $confirmation->zoi = $zoi;
            $confirmation->qr = FiscalQr::payload(
                $zoi,
                $issuerTaxNo,
                FursXml::localTime($confirmation->issued_at),
            );
        }

        $signed = $client->sign(FursXml::invoice($invoice, $confirmation, $isRetry), 'fu:InvoiceRequest');
        if ($signed === null) {
            return $this->fail($confirmation, self::ERROR_SIGN, __d('documents', 'Request could not be signed.'));
        }
        $confirmation->last_request = $signed;
        // persist ZOI before hitting the network
        $this->saveOrFail($confirmation);

        try {
            $response = $client->sendInvoice($signed);
        } catch (Throwable $e) {
            Log::error('FURS request error: ' . $e->getMessage(), 'furs');

            return $this->fail($confirmation, self::ERROR_REQUEST, $e->getMessage());
        }

        $confirmation->last_response = $response;
        $result = FursClient::parseResponse($response);
        if ($result['ok']) {
            $confirmation->eor = $result['eor'];
            $confirmation->error_code = null;
            $confirmation->error_message = null;
        } else {
            Log::error('FURS error: ' . $result['error'], 'furs');
            $confirmation->error_code = self::ERROR_XML;
            $confirmation->error_message = $result['error'];
        }

        return $this->saveOrFail($confirmation);
    }

    /**
     * Store failure.
     *
     * @param \Documents\Model\Entity\InvoicesTaxConfirmation $confirmation Confirmation.
     * @param string $code Error code.
     * @param string $message Error message.
     * @return \Documents\Model\Entity\InvoicesTaxConfirmation
     */
    private function fail(InvoicesTaxConfirmation $confirmation, string $code, string $message): InvoicesTaxConfirmation
    {
        $confirmation->error_code = $code;
        $confirmation->error_message = $message;

        return $this->saveOrFail($confirmation);
    }
}
