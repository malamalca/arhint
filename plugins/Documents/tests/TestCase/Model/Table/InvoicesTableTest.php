<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Lib\RfReference;
use Documents\Lib\SiReference;
use Documents\Model\Entity\Invoice;
use Documents\Model\Table\DocumentsCountersTable;
use Documents\Model\Table\InvoicesTable;

class InvoicesTableTest extends TestCase
{
    private const COUNTER_ISSUED = '1d53bc5b-de2d-4e85-b13b-81b39a97fc89';
    private const COUNTER_RECEIVED = '1d53bc5b-de2d-4e85-b13b-81b39a97fc88';

    public array $fixtures = [
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.Invoices',
    ];

    private InvoicesTable $Invoices;
    private DocumentsCountersTable $Counters;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        /** @var \Documents\Model\Table\InvoicesTable $invoices */
        $invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $this->Invoices = $invoices;

        /** @var \Documents\Model\Table\DocumentsCountersTable $counters */
        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $this->Counters = $counters;
    }

    /**
     * New invoice of an issued counter: document no. ISU-02 and year 2026 give
     * the model 12 content 000000202602 + check digit.
     *
     * @return void
     */
    public function testGeneratedPaymentReference(): void
    {
        $invoice = $this->Invoices->newEmptyEntity();
        $invoice->counter_id = self::COUNTER_ISSUED;
        $invoice->dat_issue = new Date('2026-02-08');

        $invoice->getNextCounterNo();

        $this->assertSame('ISU-02', $invoice->no);
        $this->assertSame('SI', $invoice->pmt_type);
        $this->assertSame('12', $invoice->pmt_module);
        $this->assertSame('0000002026023', $invoice->pmt_ref);
        $this->assertTrue(SiReference::isValid(
            SiReference::reference((string)$invoice->pmt_module, $invoice->pmt_ref),
        ));
    }

    /**
     * @return void
     */
    public function testGeneratedPaymentReferenceKeepsEnteredOne(): void
    {
        $invoice = $this->Invoices->newEmptyEntity();
        $invoice->counter_id = self::COUNTER_ISSUED;
        $invoice->dat_issue = new Date('2026-02-08');
        $invoice->pmt_type = 'SI';
        $invoice->pmt_module = '00';
        $invoice->pmt_ref = '1234';

        $invoice->getNextCounterNo();

        $this->assertSame('00', $invoice->pmt_module);
        $this->assertSame('1234', $invoice->pmt_ref);
    }

    /**
     * Received documents keep the reference of the issuer.
     *
     * @return void
     */
    public function testGeneratedPaymentReferenceSkipsReceived(): void
    {
        $invoice = $this->Invoices->newEmptyEntity();
        $invoice->counter_id = self::COUNTER_RECEIVED;
        $invoice->dat_issue = new Date('2026-02-08');

        $invoice->getNextCounterNo();

        $this->assertNull($invoice->pmt_type);
        $this->assertNull($invoice->pmt_ref);
    }

    /**
     * Model and content template of the counter are used when set.
     *
     * @return void
     */
    public function testGeneratedPaymentReferenceFromCounterTemplate(): void
    {
        $counter = $this->Counters->get(self::COUNTER_ISSUED);
        $counter->pmt_mod = '00';
        $counter->pmt_ref = '[[year]][[no]]';
        $this->Counters->saveOrFail($counter);

        $invoice = $this->Invoices->newEmptyEntity();
        $invoice->counter_id = self::COUNTER_ISSUED;
        $invoice->dat_issue = new Date('2026-02-08');

        $invoice->getNextCounterNo();

        $this->assertSame('00', $invoice->pmt_module);
        $this->assertSame('202602', $invoice->pmt_ref);
    }

    /**
     * @return void
     */
    public function testValidationOfPaymentReference(): void
    {
        $invoice = $this->Invoices->newEntity($this->invoiceData(['pmt_ref' => '0000002026023']));
        $this->assertArrayNotHasKey('pmt_type', $invoice->getErrors());
        $this->assertArrayNotHasKey('pmt_module', $invoice->getErrors());
        $this->assertArrayNotHasKey('pmt_ref', $invoice->getErrors());

        // model 12 requires 13 digits including the check digit
        $invoice = $this->Invoices->newEntity($this->invoiceData(['pmt_ref' => '1234']));
        $this->assertArrayHasKey('pmt_ref', $invoice->getErrors());

        // wrong check digit
        $invoice = $this->Invoices->newEntity($this->invoiceData(['pmt_ref' => '0000002026020']));
        $this->assertArrayHasKey('pmt_ref', $invoice->getErrors());

        // unknown model
        $invoice = $this->Invoices->newEntity($this->invoiceData(['pmt_module' => '13']));
        $this->assertArrayHasKey('pmt_module', $invoice->getErrors());

        // payment purpose code does not belong to the reference type
        $invoice = $this->Invoices->newEntity($this->invoiceData(['pmt_type' => 'OTHR']));
        $this->assertArrayHasKey('pmt_type', $invoice->getErrors());

        // empty payment data is allowed
        $invoice = $this->Invoices->newEntity($this->invoiceData([
            'pmt_type' => '',
            'pmt_module' => '',
            'pmt_ref' => '',
        ]));
        $this->assertArrayNotHasKey('pmt_type', $invoice->getErrors());
        $this->assertArrayNotHasKey('pmt_module', $invoice->getErrors());
        $this->assertArrayNotHasKey('pmt_ref', $invoice->getErrors());

        // RF references are not checked against the SI rules
        $invoice = $this->Invoices->newEntity($this->invoiceData([
            'pmt_type' => 'RF',
            'pmt_module' => '71',
            'pmt_ref' => '2348231',
        ]));
        $this->assertArrayNotHasKey('pmt_ref', $invoice->getErrors());
    }

    /**
     * Counter with the model RF gets a creditor reference by ISO 11649, built from the
     * digits and the letters of the document no (ISU-02) and the year of issue.
     *
     * @return void
     */
    public function testGeneratedRfReference(): void
    {
        $counter = $this->Counters->get(self::COUNTER_ISSUED);
        $counter->pmt_mod = 'RF';
        $this->Counters->saveOrFail($counter);

        $invoice = $this->Invoices->newEmptyEntity();
        $invoice->counter_id = self::COUNTER_ISSUED;
        $invoice->dat_issue = new Date('2026-02-08');

        $invoice->getNextCounterNo();

        $this->assertSame('RF', $invoice->pmt_type);
        $this->assertSame('02', $invoice->pmt_module);
        $this->assertSame('2026ISU02', $invoice->pmt_ref);
        $this->assertTrue(RfReference::isValid(
            $invoice->pmt_type . $invoice->pmt_module . $invoice->pmt_ref,
        ));
    }

    /**
     * @return void
     */
    public function testValidationOfRfReference(): void
    {
        $rf = ['pmt_type' => 'RF', 'pmt_module' => '71', 'pmt_ref' => '2348231'];

        $invoice = $this->Invoices->newEntity($this->invoiceData($rf));
        $this->assertArrayNotHasKey('pmt_type', $invoice->getErrors());
        $this->assertArrayNotHasKey('pmt_module', $invoice->getErrors());
        $this->assertArrayNotHasKey('pmt_ref', $invoice->getErrors());

        // wrong check digits
        $invoice = $this->Invoices->newEntity($this->invoiceData(['pmt_module' => '70'] + $rf));
        $this->assertArrayHasKey('pmt_ref', $invoice->getErrors());

        // check digits are two digits
        $invoice = $this->Invoices->newEntity($this->invoiceData(['pmt_module' => '7'] + $rf));
        $this->assertArrayHasKey('pmt_module', $invoice->getErrors());

        // hyphens are not allowed in an RF reference
        $invoice = $this->Invoices->newEntity($this->invoiceData(['pmt_ref' => '2348-231'] + $rf));
        $this->assertArrayHasKey('pmt_ref', $invoice->getErrors());

        // a received document keeps the reference of the issuer
        $invoice = $this->Invoices->patchEntity($this->receivedInvoice(), $this->invoiceData([
            'counter_id' => self::COUNTER_RECEIVED,
            'pmt_module' => '70',
        ] + $rf));
        $this->assertArrayNotHasKey('pmt_ref', $invoice->getErrors());
    }

    /**
     * A reference of a received document is the issuer's, so a wrong check digit does not
     * keep it from being recorded, while the general rules still apply.
     *
     * @return void
     */
    public function testValidationOfReceivedPaymentReference(): void
    {
        $invoice = $this->Invoices->patchEntity($this->receivedInvoice(), $this->invoiceData([
            'counter_id' => self::COUNTER_RECEIVED,
            'pmt_ref' => '0000002026020',
        ]));
        $this->assertArrayNotHasKey('pmt_ref', $invoice->getErrors());

        $invoice = $this->Invoices->patchEntity($this->receivedInvoice(), $this->invoiceData([
            'counter_id' => self::COUNTER_RECEIVED,
            'pmt_ref' => '12A',
        ]));
        $this->assertArrayHasKey('pmt_ref', $invoice->getErrors());
    }

    /**
     * Empty invoice of a counter of received documents.
     *
     * @return \Documents\Model\Entity\Invoice
     */
    private function receivedInvoice(): Invoice
    {
        $invoice = $this->Invoices->newEmptyEntity();
        $invoice->documents_counter = $this->Counters->get(self::COUNTER_RECEIVED);

        return $invoice;
    }

    /**
     * @return void
     */
    public function testPaymentReference(): void
    {
        $invoice = $this->Invoices->newEntity([
            'pmt_type' => 'SI',
            'pmt_module' => '12',
            'pmt_ref' => '0000002026023',
        ]);
        $this->assertSame('SI120000002026023', $invoice->paymentReference());
        $this->assertSame('SI12 0000002026023', $invoice->paymentReference(true));

        $invoice = $this->Invoices->newEntity([
            'pmt_type' => 'RF',
            'pmt_module' => '71',
            'pmt_ref' => '2348231',
        ]);
        $this->assertSame('RF712348231', $invoice->paymentReference());
        $this->assertSame('RF71 2348 231', $invoice->paymentReference(true));

        $invoice = $this->Invoices->newEmptyEntity();
        $this->assertSame('', $invoice->paymentReference());
        $this->assertSame('', $invoice->paymentReference(true));
    }

    /**
     * @param array<string, mixed> $payment Payment data of the invoice.
     * @return array<string, mixed>
     */
    private function invoiceData(array $payment = []): array
    {
        return $payment + [
            'counter_id' => self::COUNTER_ISSUED,
            'title' => 'Invoice with a reference',
            'dat_issue' => '2026-02-08',
            'dat_service' => '2026-02-08',
            'dat_expire' => '2026-02-16',
            'pmt_type' => 'SI',
            'pmt_module' => '12',
            'pmt_ref' => '0000002026023',
        ];
    }
}
