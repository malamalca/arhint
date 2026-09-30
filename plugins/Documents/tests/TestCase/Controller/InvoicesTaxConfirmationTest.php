<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\Event\Event;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Documents\Event\DocumentsEvents;
use Documents\Model\Entity\Invoice;

/**
 * Tax confirmation in invoice view, retry action and printed invoice.
 */
class InvoicesTaxConfirmationTest extends TestCase
{
    use IntegrationTestTrait;

    private const COUNTER_ISSUED = '1d53bc5b-de2d-4e85-b13b-81b39a97fc89';
    private const INVOICE_ISSUED = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6';

    public array $fixtures = [
        'Users' => 'app.Users',
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.Invoices',
        'plugin.Documents.InvoicesItems',
        'plugin.Documents.InvoicesTaxes',
        'plugin.Documents.DocumentsClients',
        'plugin.Documents.DocumentsLinks',
        'plugin.Documents.Vats',
        'plugin.Documents.TaxPremises',
        'plugin.Documents.TaxCertificates',
        'plugin.Documents.InvoicesTaxConfirmations',
    ];

    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    private function enableTaxConfirmation(): void
    {
        TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->updateAll(['tax_confirmation' => true, 'device_no' => 'BLAG1'], ['id' => self::COUNTER_ISSUED]);
    }

    private function confirm(?string $eor): void
    {
        $Confirmations = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxConfirmations');
        $Confirmations->saveOrFail($Confirmations->newEntity([
            'invoice_id' => self::INVOICE_ISSUED,
            'bp_no' => 'PP1',
            'device_no' => 'BLAG1',
            'issuer_taxno' => '10039953',
            'issued_at' => new DateTime('2026-09-29 08:05:03'),
            'zoi' => '34905bcff14b381039af2e9d7eee54bb',
            'eor' => $eor,
            'error_code' => $eor ? null : 'CONFIRMATION_ERROR_REQUEST',
            'error_message' => $eor ? null : 'CODECURL: timeout',
        ]));
    }

    public function testViewOfUnconfirmedInvoiceOffersRetry(): void
    {
        $this->login(USER_ADMIN);
        $this->enableTaxConfirmation();
        $this->confirm(null);

        $this->get('/documents/invoices/view/' . self::INVOICE_ISSUED);

        $this->assertResponseOk();
        $this->assertResponseContains('CODECURL: timeout');
        $this->assertResponseContains('tax-confirm/' . self::INVOICE_ISSUED);
    }

    public function testViewOfConfirmedInvoiceShowsZoiAndEor(): void
    {
        $this->login(USER_ADMIN);
        $this->enableTaxConfirmation();
        $this->confirm('eor-1234');

        $this->get('/documents/invoices/view/' . self::INVOICE_ISSUED);

        $this->assertResponseOk();
        $this->assertResponseContains('34905bcff14b381039af2e9d7eee54bb');
        $this->assertResponseContains('eor-1234');
        $this->assertResponseNotContains('tax-confirm/' . self::INVOICE_ISSUED);
    }

    public function testViewWithoutTaxCounterHasNoTaxPanel(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/invoices/view/' . self::INVOICE_ISSUED);

        $this->assertResponseOk();
        $this->assertResponseNotContains('Tax Confirmation');
    }

    public function testRetryWithoutCertificateShowsError(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->enableTaxConfirmation();

        $this->post('/documents/invoices/tax-confirm/' . self::INVOICE_ISSUED);

        $this->assertRedirect(['action' => 'view', self::INVOICE_ISSUED]);
        $confirmation = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxConfirmations')
            ->findForInvoice(self::INVOICE_ISSUED);
        $this->assertNotNull($confirmation);
        $this->assertFalse($confirmation->isConfirmed());
    }

    public function testRetryRequiresPost(): void
    {
        $this->login(USER_ADMIN);
        $this->enableTaxConfirmation();

        $this->get('/documents/invoices/tax-confirm/' . self::INVOICE_ISSUED);

        $this->assertResponseCode(405);
    }

    public function testExportHtmlGetsQrBlockOnlyWhenConfirmed(): void
    {
        $html = '<html><body><p>Invoice</p></body></html>';
        $invoice = new Invoice(['id' => self::INVOICE_ISSUED]);
        $events = new DocumentsEvents();

        $this->confirm(null);
        $this->assertSame($html, $events->showTaxBlock(new Event('x', $invoice), $html));

        $Confirmations = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxConfirmations');
        $confirmation = $Confirmations->findForInvoice(self::INVOICE_ISSUED);
        $confirmation->eor = 'eor-1234';
        $Confirmations->saveOrFail($confirmation);

        $result = $events->showTaxBlock(new Event('x', $invoice), $html);
        $this->assertStringContainsString('data:image/png;base64,', $result);
        $this->assertStringContainsString('34905bcff14b381039af2e9d7eee54bb', $result);
        $this->assertStringContainsString('eor-1234', $result);
        $this->assertStringEndsWith('</body></html>', $result);
    }

    public function testQrBlockIsPlacedAboveFooter(): void
    {
        $this->confirm('eor-1234');
        $html = '<body><div id="content">x</div><div id="footer1">footer</div></body>';

        $result = (new DocumentsEvents())->showTaxBlock(
            new Event('x', new Invoice(['id' => self::INVOICE_ISSUED])),
            $html,
        );

        $this->assertLessThan(strpos($result, 'id="footer1"'), strpos($result, 'tax-confirmation'));
        $this->assertGreaterThan(strpos($result, 'id="content"'), strpos($result, 'tax-confirmation'));
    }

    public function testQrBlockFillsSlotNextToInvoiceData(): void
    {
        $this->confirm('eor-1234');
        $html = '<body><div id="content"><div id="tax-block-slot" style="float: right; width: 28%;"></div>' .
            '<table class="basics"></table></div><div id="footer1">footer</div></body>';

        $result = (new DocumentsEvents())->showTaxBlock(
            new Event('x', new Invoice(['id' => self::INVOICE_ISSUED])),
            $html,
        );

        $this->assertLessThan(strpos($result, 'class="basics"'), strpos($result, 'tax-confirmation'));
        $this->assertGreaterThan(strpos($result, 'tax-block-slot'), strpos($result, 'tax-confirmation'));
        $this->assertSame(1, substr_count($result, 'tax-block-slot'));
        $this->assertStringContainsString('</div></div><table class="basics">', $result);
    }
}
