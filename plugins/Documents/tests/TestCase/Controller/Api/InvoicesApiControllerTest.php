<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller\Api;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Documents\Lib\FursClient;
use Documents\Model\Table\TaxCertificatesTable;
use Documents\Test\TestCase\FakeFursClient;
use Documents\Test\TestCase\FursTestCertificateTrait;

/**
 * POST /documents/api/invoices
 */
class InvoicesApiControllerTest extends TestCase
{
    use FursTestCertificateTrait;
    use IntegrationTestTrait;

    private const URL = '/documents/api/invoices';
    private const COUNTER_ISSUED = '1d53bc5b-de2d-4e85-b13b-81b39a97fc89';
    private const COUNTER_RECEIVED = '1d53bc5b-de2d-4e85-b13b-81b39a97fc88';

    public array $fixtures = [
        'Users' => 'app.Users',
        'Attachments' => 'app.Attachments',
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsAddresses' => 'plugin.Crm.ContactsAddresses',
        'ContactsAccounts' => 'plugin.Crm.ContactsAccounts',
        'plugin.Documents.Invoices',
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.DocumentsLinks',
        'plugin.Documents.InvoicesItems',
        'plugin.Documents.InvoicesTaxes',
        'plugin.Documents.DocumentsClients',
        'plugin.Documents.DocumentsTemplates',
        'plugin.Documents.Vats',
        'plugin.Documents.TaxPremises',
        'plugin.Documents.TaxCertificates',
        'plugin.Documents.InvoicesTaxConfirmations',
        'plugin.Documents.ApiRequests',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->configRequest(['environment' => ['SERVER_NAME' => 'localhost']]);

        // TCPDF renders deterministically in tests; wkhtmltopdf is used in production
        Configure::write('Pdf.pdfEngine', 'TCPDF');
        Configure::write('Pdf.TCPDF', []);

        // the fixture's issued counter belongs to another company
        TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->updateAll(['owner_id' => COMPANY_FIRST], ['id' => self::COUNTER_ISSUED]);
    }

    private function auth(string $user = 'admin', string $password = 'pass', ?string $idempotencyKey = null): void
    {
        // PHP populates PHP_AUTH_USER / PHP_AUTH_PW from the Basic Authorization header. Environment values
        // survive between requests of one test, headers do not.
        $environment = ['PHP_AUTH_USER' => $user, 'PHP_AUTH_PW' => $password];
        if ($idempotencyKey !== null) {
            $environment['HTTP_IDEMPOTENCY_KEY'] = $idempotencyKey;
        }

        $this->configRequest([
            'environment' => $environment,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    private function xml(): string
    {
        return (string)file_get_contents(dirname(__DIR__) . '/data/testInvoice_eslog20.xml');
    }

    private function invoicesCount(): int
    {
        return TableRegistry::getTableLocator()->get('Documents.Invoices')->find()->count();
    }

    private function counterValue(): int
    {
        return (int)TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->get(self::COUNTER_ISSUED)->counter;
    }

    private function enableTaxConfirmation(): void
    {
        $premise = TableRegistry::getTableLocator()->get('Documents.TaxPremises')->newEntity([
            'no' => 'PP1', 'kind' => 'MO', 'mo_type' => 'B', 'validity_date' => '2026-10-01', 'sw_taxno' => '10039953',
        ]);
        $premise->owner_id = COMPANY_FIRST;
        TableRegistry::getTableLocator()->get('Documents.TaxPremises')->saveOrFail($premise);

        TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')->updateAll(
            ['tax_confirmation' => true, 'tax_premise_id' => $premise->id, 'device_no' => 'BLAG1'],
            ['id' => self::COUNTER_ISSUED],
        );
        // issuer data is copied from the company contact
        TableRegistry::getTableLocator()->get('Crm.Contacts')
            ->updateAll(['tax_no' => 'SI10039953'], ['id' => COMPANY_FIRST]);
    }

    private function fakeCertificate(FakeFursClient $client): void
    {
        $certificates = new class (['alias' => 'TaxCertificates', 'table' => 'documents_tax_certificates']) extends TaxCertificatesTable {
            public ?FursClient $client = null;

            public function clientForUser(string $userId): ?FursClient
            {
                return $this->client;
            }
        };
        $certificates->client = $client;
        TableRegistry::getTableLocator()->set('Documents.TaxCertificates', $certificates);
    }

    public function testRequiresAuthentication(): void
    {
        $this->configRequest(['headers' => ['Content-Type' => 'application/xml']]);

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(401);
        $this->assertHeader('WWW-Authenticate', 'Basic realm="arhint"');
        $this->assertSame(2, $this->invoicesCount());
    }

    public function testWrongPassword(): void
    {
        $this->auth('admin', 'wrong');

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(401);
    }

    public function testOnlyPost(): void
    {
        $this->auth();

        $this->get(self::URL . '?counter=' . self::COUNTER_ISSUED);

        $this->assertResponseCode(404);
    }

    public function testUnknownCounter(): void
    {
        $this->auth();

        $this->post(self::URL . '?counter=00000000-0000-0000-0000-000000000000', $this->xml());

        $this->assertResponseCode(404);
        $this->assertSame('counter_not_found', json_decode((string)$this->_response->getBody(), true)['error']);
    }

    public function testCounterOfReceivedInvoicesIsRefused(): void
    {
        $this->auth();

        $this->post(self::URL . '?counter=' . self::COUNTER_RECEIVED, $this->xml());

        $this->assertResponseCode(422);
        $this->assertSame('invalid_counter', json_decode((string)$this->_response->getBody(), true)['error']);
    }

    public function testInvalidXml(): void
    {
        $this->auth();

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, '<nonsense');

        $this->assertResponseCode(400);
        $this->assertSame('invalid_xml', json_decode((string)$this->_response->getBody(), true)['error']);
        $this->assertSame(2, $this->invoicesCount());
    }

    public function testCreatesInvoiceAndReturnsPdf(): void
    {
        $this->auth();
        $counter = $this->counterValue();

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(201);
        $this->assertContentType('application/pdf');
        $this->assertStringStartsWith('%PDF', (string)$this->_response->getBody());
        $this->assertSame(3, $this->invoicesCount());
        $this->assertSame($counter + 1, $this->counterValue());

        $invoice = TableRegistry::getTableLocator()->get('Documents.Invoices')
            ->get($this->_response->getHeaderLine('X-Invoice-Id'), contain: ['Issuers', 'InvoicesItems']);
        $this->assertSame(COMPANY_FIRST, $invoice->owner_id);
        $this->assertSame(self::COUNTER_ISSUED, $invoice->counter_id);
        $this->assertNotEmpty($invoice->invoices_items);
    }

    public function testTaxConfirmedInvoiceReturnsZoiAndEor(): void
    {
        $this->enableTaxConfirmation();
        $client = new FakeFursClient($this->createP12(), self::P12_PASSWORD);
        $client->responses = ['<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" ' .
            'xmlns:fu="http://www.fu.gov.si/"><soapenv:Body><fu:InvoiceResponse><fu:UniqueInvoiceID>eor-api-1' .
            '</fu:UniqueInvoiceID></fu:InvoiceResponse></soapenv:Body></soapenv:Envelope>'];
        $this->fakeCertificate($client);
        $this->auth();

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(201);
        $this->assertHeader('X-Tax-Eor', 'eor-api-1');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $this->_response->getHeaderLine('X-Tax-Zoi'));
        $this->assertSame(1, $client->sent);
    }

    public function testFursErrorRollsBackTheInvoiceAndCounter(): void
    {
        $this->enableTaxConfirmation();
        $client = new FakeFursClient($this->createP12(), self::P12_PASSWORD);
        $client->responses = ['<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" ' .
            'xmlns:fu="http://www.fu.gov.si/"><soapenv:Body><fu:InvoiceResponse><fu:Error><fu:ErrorCode>s005' .
            '</fu:ErrorCode><fu:ErrorMessage>Bad tax number</fu:ErrorMessage></fu:Error></fu:InvoiceResponse>' .
            '</soapenv:Body></soapenv:Envelope>'];
        $this->fakeCertificate($client);
        $this->auth();
        $counter = $this->counterValue();

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(502);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('tax_confirmation_failed', $body['error']);
        $this->assertStringContainsString('Bad tax number', $body['message']);
        $this->assertSame(2, $this->invoicesCount());
        $this->assertSame($counter, $this->counterValue());
        $this->assertSame(0, TableRegistry::getTableLocator()->get('Documents.InvoicesTaxConfirmations')->find()->count());
    }

    public function testMissingCertificateIsReportedAsConfigurationError(): void
    {
        $this->enableTaxConfirmation();
        $this->auth();

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('tax_confirmation_failed', $body['error']);
        $this->assertSame('CONFIRMATION_ERROR_NO_CERTIFICATE', $body['tax_error_code']);
        $this->assertSame(2, $this->invoicesCount());
    }

    private function okFursResponse(string $eor): string
    {
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" ' .
            'xmlns:fu="http://www.fu.gov.si/"><soapenv:Body><fu:InvoiceResponse><fu:UniqueInvoiceID>' . $eor .
            '</fu:UniqueInvoiceID></fu:InvoiceResponse></soapenv:Body></soapenv:Envelope>';
    }

    public function testSameIdempotencyKeyReturnsTheSameInvoiceAgain(): void
    {
        $this->enableTaxConfirmation();
        $client = new FakeFursClient($this->createP12(), self::P12_PASSWORD);
        $client->responses = [$this->okFursResponse('eor-idem-1')];
        $this->fakeCertificate($client);
        $this->auth('admin', 'pass', 'order-1001');
        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());
        $this->assertResponseCode(201);
        $invoiceId = $this->_response->getHeaderLine('X-Invoice-Id');
        $this->assertSame('', $this->_response->getHeaderLine('Idempotent-Replayed'));
        $this->assertSame(3, $this->invoicesCount());

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(200);
        $this->assertHeader('Idempotent-Replayed', 'true');
        $this->assertHeader('X-Invoice-Id', $invoiceId);
        $this->assertHeader('X-Tax-Eor', 'eor-idem-1');
        $this->assertStringStartsWith('%PDF', (string)$this->_response->getBody());
        $this->assertSame(3, $this->invoicesCount());
        $this->assertSame(1, $client->sent);
    }

    public function testIdempotencyKeyWithDifferentRequestIsRefused(): void
    {
        $this->auth('admin', 'pass', 'order-1002');
        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());
        $this->assertResponseCode(201);

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, str_replace('Web development project', 'Other', $this->xml()));

        $this->assertResponseCode(422);
        $this->assertSame('idempotency_key_reused', json_decode((string)$this->_response->getBody(), true)['error']);
        $this->assertSame(3, $this->invoicesCount());
    }

    public function testFailedRequestReleasesTheKey(): void
    {
        $this->enableTaxConfirmation();
        $client = new FakeFursClient($this->createP12(), self::P12_PASSWORD);
        $client->responses = ['throw:CODECURL: timeout', $this->okFursResponse('eor-idem-2')];
        $this->fakeCertificate($client);
        $this->auth('admin', 'pass', 'order-1003');
        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());
        $this->assertResponseCode(502);
        $this->assertSame(2, $this->invoicesCount());

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(201);
        $this->assertHeader('X-Tax-Eor', 'eor-idem-2');
        $this->assertSame(3, $this->invoicesCount());
    }

    public function testRequestInProgressIsRefused(): void
    {
        $ApiRequests = TableRegistry::getTableLocator()->get('Documents.ApiRequests');
        [$status] = $ApiRequests->claim(
            USER_ADMIN,
            'order-1004',
            hash('sha256', self::COUNTER_ISSUED . "\n" . $this->xml()),
        );
        $this->assertSame('claimed', $status);
        $this->auth('admin', 'pass', 'order-1004');

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(409);
        $this->assertHeader('Retry-After', '5');
        $this->assertSame(2, $this->invoicesCount());
    }

    public function testInvalidIdempotencyKey(): void
    {
        $this->auth('admin', 'pass', str_repeat('a', 256));

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, $this->xml());

        $this->assertResponseCode(400);
        $this->assertSame('invalid_idempotency_key', json_decode((string)$this->_response->getBody(), true)['error']);
    }

    public function testUnknownVatRateIsRefused(): void
    {
        $this->auth();

        $this->post(self::URL . '?counter=' . self::COUNTER_ISSUED, str_replace('22.0', '13.0', $this->xml()));

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('invalid_invoice', $body['error']);
        $this->assertStringContainsString('13', $body['message']);
        $this->assertSame(2, $this->invoicesCount());
    }
}
