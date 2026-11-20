<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Lib\DocumentsSigner;
use Documents\Lib\InvoicesExport;
use Documents\Model\Entity\Invoice;

/**
 * Documents\Lib\InvoicesExport Test Case
 */
class InvoicesExportTest extends TestCase
{
    private const INVOICE_ISSUED = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6';

    protected array $fixtures = [
        'app.Attachments',
        'plugin.Documents.Invoices',
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.DocumentsLinks',
        'plugin.Documents.InvoicesItems',
        'plugin.Documents.InvoicesTaxes',
        'plugin.Documents.DocumentsClients',
        'plugin.Documents.DocumentsTemplates',
        'plugin.Documents.Vats',
    ];

    /**
     * Invoice with the associations the export needs.
     *
     * @param string $id Invoice id.
     * @return \Documents\Model\Entity\Invoice
     */
    private function invoice(string $id): Invoice
    {
        /** @var \Documents\Model\Table\InvoicesTable $invoices */
        $invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');

        $invoice = $invoices->get($id, contain: [
            'InvoicesItems',
            'DocumentsCounters',
            'InvoicesTaxes',
            'Receivers',
            'Buyers',
            'Issuers',
            'Attachments',
            'DocumentsLinks' => ['Invoices'],
            'TplHeaders',
            'TplBodies',
            'TplFooters',
        ]);

        // the fixture clients carry a title only, the eSlog template needs an address too
        foreach ([$invoice->issuer, $invoice->receiver, $invoice->buyer] as $client) {
            $client->street = 'Testna ulica 1';
            $client->city = 'Ljubljana';
            $client->zip = '1000';
            $client->iban = 'SI56020170014356205';
        }

        return $invoice;
    }

    /**
     * The printed invoice carries the payment reference, so the payer can use it.
     *
     * @return void
     */
    public function testHtmlExportContainsPaymentReference(): void
    {
        $invoice = $this->invoice(self::INVOICE_ISSUED);
        $this->assertSame('SI0001', $invoice->paymentReference());

        $html = (new InvoicesExport())->export('html', [$invoice]);
        $this->assertIsString($html);

        $this->assertStringContainsString('Sklic', $html);
        // the default stylesheet separates the type and the model from the content
        $this->assertStringContainsString('SI00 - 01', $html);
    }

    /**
     * The reference goes into the eSlog XML as the payment reference (PQ).
     *
     * @return void
     */
    public function testEslogExportContainsPaymentReference(): void
    {
        $xml = (new InvoicesExport())->export('eslog', [$this->invoice(self::INVOICE_ISSUED)]);
        $this->assertIsString($xml);

        $this->assertStringContainsString('<StevilkaSklica>SI0001</StevilkaSklica>', $xml);
    }

    /**
     * Partners without an e-mail address get no contact block (no empty D_3148 element).
     *
     * @return void
     */
    public function testEslog20OmitsEmptyContactEmail(): void
    {
        $invoice = $this->invoice(self::INVOICE_ISSUED);
        $invoice->buyer->email = '';
        $invoice->issuer->email = null;

        $xml = (new InvoicesExport())->export('eslog20', [$invoice]);
        $this->assertIsString($xml);
        $this->assertStringNotContainsString('<D_3148></D_3148>', $xml);
        $this->assertStringNotContainsString('<D_3148/>', $xml);
    }

    /**
     * The eSlog 2.0 export carries the registration number (0199) of both partners and the
     * buyer's bank account (BB); each is left out when the data is missing.
     *
     * @return void
     */
    public function testEslog20PartnerRegistrationAndBuyerAccount(): void
    {
        $invoice = $this->invoice(self::INVOICE_ISSUED);
        $invoice->buyer->mat_no = '1111111000';
        $invoice->buyer->iban = 'SI56020170014356205';
        $invoice->issuer->mat_no = '2222222000';

        $xml = (string)(new InvoicesExport())->export('eslog20', [$invoice]);
        $this->assertStringContainsString('<D_1153>0199</D_1153>', $xml);
        $this->assertStringContainsString('<D_1154>1111111000</D_1154>', $xml);
        $this->assertStringContainsString('<D_1154>2222222000</D_1154>', $xml);
        $this->assertStringContainsString('<D_3035>BB</D_3035>', $xml);

        $invoice->buyer->mat_no = null;
        $invoice->buyer->iban = '';
        $xml = (string)(new InvoicesExport())->export('eslog20', [$invoice]);
        $this->assertStringNotContainsString('<D_1154>1111111000</D_1154>', $xml);
        $this->assertStringNotContainsString('<D_3035>BB</D_3035>', $xml);
        $this->assertStringNotContainsString('<D_1154></D_1154>', $xml);
        $this->assertStringNotContainsString('<D_3194></D_3194>', $xml);
    }

    /**
     * A signed eSlog 2.0 document is exported with its signature while the data is unchanged;
     * after the invoice changes the stale signature is dropped.
     *
     * @return void
     */
    public function testEslog20ExportIncludesSignature(): void
    {
        $Exporter = new InvoicesExport();
        // same read path as the signer, so that the digests are comparable
        $invoice = $Exporter->find(['id' => self::INVOICE_ISSUED])->first();

        $unsigned = $Exporter->exportEslog20($invoice);
        $this->assertStringNotContainsString('Signature', $unsigned);

        $signer = new DocumentsSigner(self::INVOICE_ISSUED, 'Invoices', 'eslog20');
        $signedXml = $signer->getXml();
        $this->assertStringContainsString('<ds:Signature', $signedXml);
        $this->assertStringContainsString('URI="#data"', $signedXml);
        $this->assertStringContainsString('urn:eslog:2.00#Invoice', $signedXml);

        $invoice->signed_eslog20 = $signedXml;
        $exported = (string)$Exporter->export('eslog20', [$invoice]);
        $this->assertStringContainsString('<ds:Signature', $exported);
        $this->assertSame($signedXml, $exported);

        // the signing itself must not pick up an already signed copy
        $this->assertSame(1, substr_count($exported, '<ds:Signature '));

        // document changed after signing -> signature no longer covers it
        $invoice->no = 'CHANGED-001';
        $changed = $Exporter->exportEslog20($invoice);
        $this->assertStringNotContainsString('<ds:Signature', $changed);
    }
}
