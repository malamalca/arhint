<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
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
}
