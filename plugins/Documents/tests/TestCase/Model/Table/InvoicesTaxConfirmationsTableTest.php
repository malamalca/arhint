<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Lib\FursClient;
use Documents\Model\Table\InvoicesTaxConfirmationsTable;
use Documents\Model\Table\TaxCertificatesTable;
use Documents\Test\TestCase\FakeFursClient;
use Documents\Test\TestCase\FursTestCertificateTrait;

class InvoicesTaxConfirmationsTableTest extends TestCase
{
    use FursTestCertificateTrait;

    private const COUNTER_ISSUED = '1d53bc5b-de2d-4e85-b13b-81b39a97fc89';
    private const INVOICE_ISSUED = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6';
    private const USER = '048acacf-d87c-4088-a3a7-4bab30f6a040';

    public array $fixtures = [
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.Invoices',
        'plugin.Documents.InvoicesItems',
        'plugin.Documents.DocumentsClients',
        'plugin.Documents.Vats',
        'plugin.Documents.TaxPremises',
        'plugin.Documents.TaxCertificates',
        'plugin.Documents.InvoicesTaxConfirmations',
    ];

    private InvoicesTaxConfirmationsTable $Confirmations;
    private FakeFursClient $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new FakeFursClient($this->createP12(), self::P12_PASSWORD);

        // certificate lookups return the fake client instead of decrypting a stored p12
        $certificates = new class (['alias' => 'TaxCertificates', 'table' => 'documents_tax_certificates']) extends TaxCertificatesTable {
            public ?FursClient $client = null;

            public function clientForUser(string $userId): ?FursClient
            {
                return $this->client;
            }
        };
        $certificates->client = $this->client;
        TableRegistry::getTableLocator()->set('Documents.TaxCertificates', $certificates);

        $this->Confirmations = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxConfirmations');

        $premise = TableRegistry::getTableLocator()->get('Documents.TaxPremises')->newEntity([
            'no' => 'PP1',
            'kind' => 'MO',
            'mo_type' => 'B',
            'validity_date' => '2026-10-01',
            'sw_taxno' => '10039953',
        ]);
        $premise->owner_id = COMPANY_FIRST;
        TableRegistry::getTableLocator()->get('Documents.TaxPremises')->saveOrFail($premise);

        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counters->updateAll(
            ['tax_confirmation' => true, 'tax_premise_id' => $premise->id, 'device_no' => 'BLAG1'],
            ['id' => self::COUNTER_ISSUED],
        );
        // the fixture's issuer needs a valid tax number
        TableRegistry::getTableLocator()->get('Documents.DocumentsClients')
            ->updateAll(['tax_no' => 'SI10039953'], ['document_id' => self::INVOICE_ISSUED, 'kind' => 'II']);
    }

    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->clear();

        parent::tearDown();
    }

    private function okResponse(string $eor = 'eor-1234'): string
    {
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fu="http://www.fu.gov.si/">' .
            '<soapenv:Body><fu:InvoiceResponse><fu:UniqueInvoiceID>' . $eor . '</fu:UniqueInvoiceID>' .
            '</fu:InvoiceResponse></soapenv:Body></soapenv:Envelope>';
    }

    private function errorResponse(): string
    {
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fu="http://www.fu.gov.si/">' .
            '<soapenv:Body><fu:InvoiceResponse><fu:Error><fu:ErrorCode>s002</fu:ErrorCode>' .
            '<fu:ErrorMessage>Invalid certificate</fu:ErrorMessage></fu:Error></fu:InvoiceResponse>' .
            '</soapenv:Body></soapenv:Envelope>';
    }

    public function testSignAndSendConfirmsInvoice(): void
    {
        $this->client->responses = [$this->okResponse()];

        $confirmation = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);

        $this->assertTrue($confirmation->isConfirmed());
        $this->assertSame('eor-1234', $confirmation->eor);
        $this->assertNull($confirmation->error_code);
        $this->assertSame('PP1', $confirmation->bp_no);
        $this->assertSame('BLAG1', $confirmation->device_no);
        $this->assertSame('10039953', $confirmation->issuer_taxno);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $confirmation->zoi);
        $this->assertMatchesRegularExpression('/^\d{60}$/', $confirmation->qr);
        $this->assertStringContainsString('<fu:ProtectedID>' . $confirmation->zoi . '</fu:ProtectedID>', $this->client->lastRequest);
        $this->assertStringContainsString('Signature', $this->client->lastRequest);
        $this->assertStringNotContainsString('SubsequentSubmit', $this->client->lastRequest);
    }

    public function testConfirmedInvoiceIsNeverSentTwice(): void
    {
        $this->client->responses = [$this->okResponse()];

        $first = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);
        $second = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);

        $this->assertSame(1, $this->client->sent);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $this->Confirmations->find()->count());
    }

    public function testFursErrorIsStoredAndRetryKeepsZoiAndIssueTime(): void
    {
        $this->client->responses = [$this->errorResponse(), $this->okResponse('eor-retry')];

        $failed = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);
        $this->assertFalse($failed->isConfirmed());
        $this->assertSame(InvoicesTaxConfirmationsTable::ERROR_XML, $failed->error_code);
        $this->assertSame('s002: Invalid certificate', $failed->error_message);
        $zoi = $failed->zoi;
        $issuedAt = $failed->issued_at->toIso8601String();

        $retried = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);
        $this->assertSame('eor-retry', $retried->eor);
        $this->assertNull($retried->error_code);
        $this->assertSame($zoi, $retried->zoi);
        $this->assertSame($issuedAt, $retried->issued_at->toIso8601String());
        $this->assertStringContainsString('<fu:SubsequentSubmit>true</fu:SubsequentSubmit>', $this->client->lastRequest);
        $this->assertSame(1, $this->Confirmations->find()->count());
    }

    public function testTransportErrorIsStored(): void
    {
        $this->client->responses = ['throw:CODECURL: timeout'];

        $confirmation = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);

        $this->assertFalse($confirmation->isConfirmed());
        $this->assertSame(InvoicesTaxConfirmationsTable::ERROR_REQUEST, $confirmation->error_code);
        $this->assertSame('CODECURL: timeout', $confirmation->error_message);
        // ZOI is kept so the invoice can still be printed and confirmed later
        $this->assertNotEmpty($confirmation->zoi);
    }

    public function testMissingCertificate(): void
    {
        TableRegistry::getTableLocator()->get('Documents.TaxCertificates')->client = null;

        $confirmation = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);

        $this->assertSame(InvoicesTaxConfirmationsTable::ERROR_NO_CERTIFICATE, $confirmation->error_code);
        $this->assertSame(0, $this->client->sent);
    }

    public function testMissingPremise(): void
    {
        TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->updateAll(['tax_premise_id' => null], ['id' => self::COUNTER_ISSUED]);

        $confirmation = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);

        $this->assertSame(InvoicesTaxConfirmationsTable::ERROR_NO_PREMISE, $confirmation->error_code);
    }

    public function testInvalidIssuerTaxNumber(): void
    {
        TableRegistry::getTableLocator()->get('Documents.DocumentsClients')
            ->updateAll(['tax_no' => '123'], ['document_id' => self::INVOICE_ISSUED, 'kind' => 'II']);

        $confirmation = $this->Confirmations->signAndSend(self::INVOICE_ISSUED, self::USER);

        $this->assertSame(InvoicesTaxConfirmationsTable::ERROR_TAXNO, $confirmation->error_code);
    }

    public function testNewIssuedInvoiceIsConfirmedAfterSave(): void
    {
        $this->client->responses = [$this->okResponse('eor-new')];

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->newEntity([
            'owner_id' => COMPANY_FIRST,
            'user_id' => self::USER,
            'counter_id' => self::COUNTER_ISSUED,
            'counter' => 99,
            'no' => 'I-99',
            'title' => 'New issued invoice',
            'dat_issue' => '2026-09-29',
            'dat_service' => '2026-09-29',
            'dat_expire' => '2026-10-07',
            'issuer' => ['kind' => 'II', 'title' => 'Issuer', 'tax_no' => 'SI10039953'],
            'buyer' => ['kind' => 'BY', 'title' => 'Buyer'],
            'invoices_items' => [
                ['vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c', 'descript' => 'Item', 'vat_title' => '22 %', 'vat_percent' => 22, 'qty' => 1, 'price' => 100, 'discount' => 0],
            ],
        ], ['associated' => ['Issuers', 'Buyers', 'InvoicesItems']]);
        $invoice->doc_type = 'IV';

        $this->assertNotFalse($Invoices->save($invoice, ['associated' => ['Issuers', 'Buyers', 'InvoicesItems']]), json_encode($invoice->getErrors()));

        $confirmation = $this->Confirmations->findForInvoice($invoice->id);
        $this->assertNotNull($confirmation);
        $this->assertSame('eor-new', $confirmation->eor);
        $this->assertStringContainsString('<fu:InvoiceAmount>122.00</fu:InvoiceAmount>', $this->client->lastRequest);
    }

    public function testInvoicesOfCountersWithoutTaxConfirmationAreNotSent(): void
    {
        TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->updateAll(['tax_confirmation' => false], ['id' => self::COUNTER_ISSUED]);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->newEntity([
            'owner_id' => COMPANY_FIRST,
            'counter_id' => self::COUNTER_ISSUED,
            'counter' => 100,
            'no' => 'I-100',
            'title' => 'No tax',
            'dat_issue' => '2026-09-29',
            'dat_service' => '2026-09-29',
            'dat_expire' => '2026-10-07',
        ]);
        $invoice->doc_type = 'IV';
        $Invoices->saveOrFail($invoice);

        $this->assertSame(0, $this->client->sent);
        $this->assertSame(0, $this->Confirmations->find()->count());
    }
}
