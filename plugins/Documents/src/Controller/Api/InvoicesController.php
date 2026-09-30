<?php
declare(strict_types=1);

namespace Documents\Controller\Api;

use App\AppPluginsEnum;
use App\Model\Entity\User;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Documents\Lib\EslogImport;
use Documents\Lib\EslogInvoiceBuilder;
use Documents\Lib\InvoicesExport;
use Documents\Model\Entity\DocumentsCounter;
use Documents\Model\Table\ApiRequestsTable;
use Documents\Model\Table\InvoicesTaxConfirmationsTable;
use Throwable;

/**
 * Invoices API - external systems post an eSlog 2.0 invoice and receive the issued (tax confirmed) PDF.
 *
 * POST /documents/api/invoices?counter=<counter id>
 *   Authorization: Basic <username:password>
 *   Content-Type: application/xml   (body: eSlog 2.0 document)
 *   Idempotency-Key: <unique key>   (optional, see docs/api-invoices.md)
 *
 * 201 application/pdf on success (X-Invoice-Id, X-Invoice-No, X-Tax-Zoi and X-Tax-Eor headers),
 * otherwise a JSON error `{"error": "<code>", "message": "..."}`.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class InvoicesController extends Controller
{
    /**
     * Initialize API-specific components.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // identity is optional at the component level; the action answers with a JSON 401 itself
        $this->loadComponent('Authentication.Authentication', ['requireIdentity' => false]);
    }

    /**
     * POST /documents/api/invoices
     *
     * @return \Cake\Http\Response
     */
    public function create(): Response
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->error(401, 'unauthorized', 'Authentication required.')
                ->withHeader('WWW-Authenticate', 'Basic realm="arhint"');
        }

        /** @var \App\Model\Entity\User $user */
        $user = $identity->getOriginalData();
        if (!$user->hasAccess(AppPluginsEnum::Documents) || !$user->hasRole('editor')) {
            return $this->error(403, 'forbidden', 'The user is not allowed to issue invoices.');
        }

        /** @var \Documents\Model\Table\DocumentsCountersTable $Counters */
        $Counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counterId = (string)$this->getRequest()->getQuery('counter');
        /** @var \Documents\Model\Entity\DocumentsCounter|null $counter */
        $counter = $counterId === '' ? null : $Counters->find()
            ->where(['id' => $counterId, 'owner_id' => $user->company_id])
            ->first();
        if (!$counter) {
            return $this->error(404, 'counter_not_found', 'Counter not found.');
        }
        if ($counter->kind !== 'Invoices' || $counter->direction !== 'issued' || !$counter->active) {
            return $this->error(422, 'invalid_counter', 'The counter must be an active counter of issued invoices.');
        }

        $key = $this->getRequest()->getHeaderLine('Idempotency-Key');
        if ($key !== '' && !preg_match('/^[\x21-\x7E]{1,255}$/', $key)) {
            return $this->error(
                400,
                'invalid_idempotency_key',
                'The Idempotency-Key must have 1 to 255 printable ASCII characters.',
            );
        }

        $xml = (string)$this->getRequest()->getBody();
        if (trim($xml) === '') {
            return $this->error(400, 'empty_body', 'The request body must contain an eSlog 2.0 XML document.');
        }
        $parser = new EslogImport();
        $data = $parser->parse($xml);
        if ($data === null) {
            return $this->error(400, 'invalid_xml', (string)$parser->lastError);
        }
        if (empty($data['items'])) {
            return $this->error(422, 'invalid_invoice', 'The invoice has no items.');
        }
        if (empty($data['buyer']['title'] ?? $data['receiver']['title'] ?? null)) {
            return $this->error(422, 'invalid_invoice', 'The invoice has no buyer.');
        }

        // unknown VAT rates would be reported to the tax authority as VAT exempt, so they are refused
        /** @var \Documents\Model\Table\VatsTable $Vats */
        $Vats = TableRegistry::getTableLocator()->get('Documents.Vats');
        $rates = [];
        foreach ($Vats->levels((string)$user->company_id) as $vat) {
            $rates[] = (float)$vat->percent;
        }
        foreach ($data['items'] as $item) {
            $percent = (float)($item['vat_percent'] ?? 0);
            $known = array_filter($rates, fn($rate) => abs($rate - $percent) < 0.01);
            if (!$known) {
                return $this->error(
                    422,
                    'invalid_invoice',
                    sprintf('The VAT rate %s %% is not defined for the company.', (float)$percent),
                );
            }
        }

        /** @var \Documents\Model\Table\ApiRequestsTable $ApiRequests */
        $ApiRequests = TableRegistry::getTableLocator()->get('Documents.ApiRequests');
        $claimed = null;
        if ($key !== '') {
            $hash = hash('sha256', $counterId . "\n" . $xml);
            for ($attempt = 0; $attempt < 2; $attempt++) {
                [$status, $record] = $ApiRequests->claim((string)$user->id, $key, $hash);

                if ($status === ApiRequestsTable::MISMATCH) {
                    return $this->error(
                        422,
                        'idempotency_key_reused',
                        'The Idempotency-Key was already used with a different request.',
                    );
                }
                if ($status === ApiRequestsTable::IN_PROGRESS) {
                    return $this->error(409, 'request_in_progress', 'The request is already being processed.')
                        ->withHeader('Retry-After', '5');
                }
                if ($status === ApiRequestsTable::REPLAY) {
                    $replay = $this->replay((string)$record->invoice_id);
                    if ($replay !== null) {
                        return $replay;
                    }
                    // the invoice was deleted meanwhile, so the request is processed again
                    $ApiRequests->deleteAll(['id' => $record->id]);
                    continue;
                }

                $claimed = $record;
                break;
            }
        }

        $result = $this->issue($user, $counter, $data);
        if ($result instanceof Response) {
            // nothing was created, so the same request may be retried with the same key
            if ($claimed) {
                $ApiRequests->release($claimed);
            }

            return $result;
        }

        [$invoiceId, $zoi, $eor] = $result;
        if ($claimed) {
            $ApiRequests->complete($claimed, $invoiceId);
        }

        return $this->pdfResponse($invoiceId, $zoi, $eor, 201);
    }

    /**
     * Create and (if the counter requires it) tax confirm the invoice in one transaction.
     *
     * @param \App\Model\Entity\User $user User.
     * @param \Documents\Model\Entity\DocumentsCounter $counter Counter.
     * @param array<string, mixed> $data Parsed eSlog data.
     * @return \Cake\Http\Response|array{0: string, 1: string|null, 2: string|null} Error response or
     *   invoice id, ZOI and EOR.
     */
    private function issue(User $user, DocumentsCounter $counter, array $data): Response|array
    {
        /** @var \Documents\Model\Table\InvoicesTable $Invoices */
        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');

        $conn = $Invoices->getConnection();
        $conn->begin();
        try {
            // issuer is always the company of the user; the counter and templates are the counter's
            $invoice = $Invoices->parseRequest($this->getRequest());
            $invoice->user_id = $user->id;
            $invoice->tpl_header_id = $counter->tpl_header_id;
            $invoice->tpl_body_id = $counter->tpl_body_id;
            $invoice->tpl_footer_id = $counter->tpl_footer_id;

            unset($data['issuer']);
            $invoice = (new EslogInvoiceBuilder())->apply($invoice, $data, (string)$user->company_id);

            $today = Date::today();
            $invoice->dat_issue ??= $today;
            $invoice->dat_service ??= $invoice->dat_issue;
            $invoice->dat_expire ??= $invoice->dat_issue->addDays((int)($counter->pmt_days ?: 8));
            $invoice->location ??= $invoice->issuer->city ?? null;
            if (empty($invoice->title)) {
                $invoice->title = (string)$counter->title;
            }
            $invoice->descript = (string)($invoice->descript ?: $counter->template_descript);

            $invoice->getNextCounterNo();

            $saved = $Invoices->save($invoice, [
                'associated' => ['Issuers', 'Buyers', 'Receivers', 'InvoicesItems', 'InvoicesTaxes'],
            ]);
            if (!$saved) {
                $conn->rollback();

                return $this->error(422, 'invoice_not_saved', 'The invoice could not be saved.', [
                    'errors' => $invoice->getErrors(),
                ]);
            }

            $confirmation = null;
            if ($counter->tax_confirmation) {
                /** @var \Documents\Model\Table\InvoicesTaxConfirmationsTable $Confirmations */
                $Confirmations = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxConfirmations');
                $confirmation = $Confirmations->findForInvoice($invoice->id);

                if (!$confirmation || !$confirmation->isConfirmed()) {
                    $conn->rollback();

                    $configErrors = [
                        InvoicesTaxConfirmationsTable::ERROR_NO_CERTIFICATE,
                        InvoicesTaxConfirmationsTable::ERROR_NO_PREMISE,
                        InvoicesTaxConfirmationsTable::ERROR_TAXNO,
                    ];

                    return $this->error(
                        in_array($confirmation?->error_code, $configErrors, true) ? 422 : 502,
                        'tax_confirmation_failed',
                        (string)($confirmation?->error_message ?: 'Tax confirmation failed.'),
                        ['tax_error_code' => $confirmation?->error_code],
                    );
                }
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();

            return $this->error(500, 'server_error', 'The invoice could not be created.');
        }

        return [(string)$invoice->id, $confirmation?->zoi, $confirmation?->eor];
    }

    /**
     * Response for a repeated request: the PDF of the invoice created by the first one.
     *
     * @param string $invoiceId Invoice id.
     * @return \Cake\Http\Response|null Null if the invoice no longer exists.
     */
    private function replay(string $invoiceId): ?Response
    {
        /** @var \Documents\Model\Table\InvoicesTable $Invoices */
        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        if (!$Invoices->exists(['id' => $invoiceId])) {
            return null;
        }

        /** @var \Documents\Model\Table\InvoicesTaxConfirmationsTable $Confirmations */
        $Confirmations = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxConfirmations');
        $confirmation = $Confirmations->findForInvoice($invoiceId);

        return $this->pdfResponse($invoiceId, $confirmation?->zoi, $confirmation?->eor, 200)
            ->withHeader('Idempotent-Replayed', 'true');
    }

    /**
     * Render the committed invoice to PDF.
     *
     * @param string $invoiceId Invoice id.
     * @param string|null $zoi ZOI, if tax confirmed.
     * @param string|null $eor EOR, if tax confirmed.
     * @param int $status HTTP status of a successful response.
     * @return \Cake\Http\Response
     */
    private function pdfResponse(string $invoiceId, ?string $zoi, ?string $eor, int $status): Response
    {
        $exporter = new InvoicesExport();
        $invoice = null;
        $pdf = false;

        try {
            $invoice = $exporter->find(['id' => $invoiceId])->first();
            $pdf = $invoice ? $exporter->export('pdf', [$invoice]) : false;
        } catch (Throwable $e) {
            $pdf = false;
            Log::error('Invoice API PDF export failed: ' . $e->getMessage());
        }

        if (empty($pdf)) {
            Log::error('Invoice API PDF export failed: ' . (string)$exporter->lastError);
            // the invoice already exists (and is confirmed), so the client must not blindly retry
            return $this->error(500, 'pdf_failed', 'The invoice was created but its PDF could not be rendered.', [
                'invoice_id' => $invoiceId,
                'tax_zoi' => $zoi,
                'tax_eor' => $eor,
            ]);
        }

        $response = $exporter->response('pdf', (string)$pdf, [
            'download' => true,
            'filename' => 'racun-' . preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)$invoice?->no),
        ])
            ->withStatus($status)
            ->withHeader('X-Invoice-Id', $invoiceId)
            ->withHeader('X-Invoice-No', rawurlencode((string)$invoice?->no));

        if ($zoi) {
            $response = $response->withHeader('X-Tax-Zoi', $zoi);
        }
        if ($eor) {
            $response = $response->withHeader('X-Tax-Eor', $eor);
        }

        return $response;
    }

    /**
     * JSON error response.
     *
     * @param int $status HTTP status.
     * @param string $code Machine readable error code.
     * @param string $message Human readable message.
     * @param array<string, mixed> $extra Additional fields.
     * @return \Cake\Http\Response
     */
    private function error(int $status, string $code, string $message, array $extra = []): Response
    {
        return $this->response
            ->withType('json')
            ->withStatus($status)
            ->withStringBody((string)json_encode(['error' => $code, 'message' => $message] + $extra));
    }
}
