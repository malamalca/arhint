<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Lib\TravelOrdersExport;
use Documents\Model\Entity\TravelOrder;
use Documents\Model\Entity\TravelOrdersExpense;
use Documents\Model\Entity\TravelOrdersMileage;
use DOMDocument;

/**
 * Documents\Lib\TravelOrdersExport Test Case
 */
class TravelOrdersExportTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.TravelOrders',
        'plugin.Documents.TravelOrdersMileages',
        'plugin.Documents.TravelOrdersExpenses',
        'app.Attachments',
    ];

    /**
     * @var \Documents\Lib\TravelOrdersExport
     */
    private TravelOrdersExport $export;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->export = new TravelOrdersExport();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->clear();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Helper: build a TravelOrder entity without hitting the DB
    // ---------------------------------------------------------------

    /**
     * Build a minimal TravelOrder entity.
     *
     * @param string $status One of the TravelOrder::STATUS_* constants.
     * @return \Documents\Model\Entity\TravelOrder
     */
    private function makeOrder(string $status = TravelOrder::STATUS_APPROVED): TravelOrder
    {
        return new TravelOrder([
            'id' => 'test-1111-2222-3333-444444444444',
            'no' => 'TO-001',
            'title' => 'Business trip to Zagreb',
            'status' => $status,
            'dat_issue' => new Date('2024-01-15'),
            'dat_task' => new Date('2024-01-16'),
            'location' => 'Ljubljana',
            'taskee' => 'John Doe',
            'departure' => new DateTime('2024-01-16 07:00:00'),
            'arrival' => new DateTime('2024-01-16 20:00:00'),
            'descript' => 'Business trip description',
            'vehicle_title' => null,
            'vehicle_registration' => 'LJ AB-001',
            'vehicle_owner' => 'Company',
            'advance' => null,
            'dat_advance' => null,
            'net_total' => null,
            'total' => null,
            'entered_by_id' => null,
            'entered_at' => null,
            'approved_by_id' => null,
            'approved_at' => null,
            'processed_by_id' => null,
            'processed_at' => null,
            'employee' => null,
            'entered_by' => null,
            'approved_by' => null,
            'processed_by' => null,
            'tpl_header' => null,
            'tpl_body' => null,
            'tpl_footer' => null,
            'travel_orders_mileages' => [],
            'travel_orders_expenses' => [],
        ]);
    }

    /**
     * Build a sample TravelOrdersMileage entity.
     *
     * @return \Documents\Model\Entity\TravelOrdersMileage
     */
    private function makeMileage(): TravelOrdersMileage
    {
        return new TravelOrdersMileage([
            'start_time' => new DateTime('2024-01-16 07:00:00'),
            'end_time' => new DateTime('2024-01-16 10:00:00'),
            'road_description' => 'Ljubljana - Zagreb',
            'distance_km' => 140.0,
            'price_per_km' => 0.21,
            'total' => 29.40,
        ]);
    }

    /**
     * Build a sample TravelOrdersExpense entity.
     *
     * @return \Documents\Model\Entity\TravelOrdersExpense
     */
    private function makeExpense(): TravelOrdersExpense
    {
        return new TravelOrdersExpense([
            'start_time' => new DateTime('2024-01-16 07:00:00'),
            'end_time' => new DateTime('2024-01-16 10:00:00'),
            'type' => 'Toll',
            'description' => 'Highway toll Ljubljana-Zagreb',
            'quantity' => 1.0,
            'price' => 16.10,
            'currency' => 'EUR',
            'total' => 16.10,
            'approved_total' => 16.10,
        ]);
    }

    // ---------------------------------------------------------------
    // find()
    // ---------------------------------------------------------------

    /**
     * find() returns a SelectQuery instance.
     *
     * @return void
     */
    public function testFindReturnsSelectQuery(): void
    {
        $query = $this->export->find([]);
        $this->assertInstanceOf(SelectQuery::class, $query);
    }

    /**
     * find() with an id filter restricts results to a single record.
     *
     * @return void
     */
    public function testFindWithIdFilter(): void
    {
        $query = $this->export->find(['id' => 'a1b23456-7890-4bcd-8f12-345678901234']);
        $results = $query->toArray();
        $this->assertCount(1, $results);
        $this->assertEquals('TO-01', $results[0]->no);
    }

    /**
     * find() with no filter returns all fixture records.
     *
     * @return void
     */
    public function testFindAllReturnsMultipleRecords(): void
    {
        $query = $this->export->find([]);
        $this->assertGreaterThanOrEqual(2, $query->count());
    }

    // ---------------------------------------------------------------
    // export('xml', …) – non-completed order
    // ---------------------------------------------------------------

    /**
     * XML export of a non-completed order contains header fields.
     *
     * @return void
     */
    public function testXmlExportNonCompletedContainsHeader(): void
    {
        $order = $this->makeOrder(TravelOrder::STATUS_APPROVED);
        $xml = $this->export->export('xml', [$order]);

        $this->assertIsString($xml);
        $this->assertStringContainsString('<No>TO-001</No>', $xml);
        $this->assertStringContainsString('<Title>Business trip to Zagreb</Title>', $xml);
        $this->assertStringContainsString('<Status>approved</Status>', $xml);
        $this->assertStringContainsString('<Location>Ljubljana</Location>', $xml);
        $this->assertStringContainsString('<Taskee>John Doe</Taskee>', $xml);
        $this->assertStringContainsString('<DatIssue>2024-01-15</DatIssue>', $xml);
    }

    /**
     * XML export of a non-completed order does NOT contain the full dataset.
     * ApprovedBy is always exported (visible on approved stage); ProcessedBy only for completed.
     *
     * @return void
     */
    public function testXmlExportNonCompletedExcludesFullDataset(): void
    {
        $order = $this->makeOrder(TravelOrder::STATUS_APPROVED);
        $xml = $this->export->export('xml', [$order]);

        $this->assertIsString($xml);
        $this->assertStringNotContainsString('<Mileages>', $xml);
        $this->assertStringNotContainsString('<Expenses>', $xml);
        $this->assertStringNotContainsString('<ProcessedBy>', $xml);
        // NetTotal/Total are always emitted (empty for non-completed) so PDF popups can display them
        $this->assertStringContainsString('<NetTotal></NetTotal>', $xml);
        $this->assertStringContainsString('<Total></Total>', $xml);
        // ApprovedBy is always included (shown on approved-stage PDF)
        $this->assertStringContainsString('<ApprovedBy>', $xml);
    }

    /**
     * Non-completed status 'waiting_approval' also excludes the full dataset.
     *
     * @return void
     */
    public function testXmlExportWaitingApprovalExcludesFullDataset(): void
    {
        $order = $this->makeOrder(TravelOrder::STATUS_WAITING_APPROVAL);
        $xml = $this->export->export('xml', [$order]);

        $this->assertIsString($xml);
        $this->assertStringNotContainsString('<Mileages>', $xml);
        $this->assertStringNotContainsString('<Expenses>', $xml);
    }

    // ---------------------------------------------------------------
    // export('xml', …) – completed order
    // ---------------------------------------------------------------

    /**
     * XML export of a completed order contains mileages and expenses.
     *
     * @return void
     */
    public function testXmlExportCompletedContainsMileagesAndExpenses(): void
    {
        $order = $this->makeOrder(TravelOrder::STATUS_COMPLETED);
        $order->travel_orders_mileages = [$this->makeMileage()];
        $order->travel_orders_expenses = [$this->makeExpense()];
        $order->net_total = 45.50;
        $order->total = 45.50;

        $xml = $this->export->export('xml', [$order]);

        $this->assertIsString($xml);
        $this->assertStringContainsString('<Mileages>', $xml);
        $this->assertStringContainsString('<RoadDescription>Ljubljana - Zagreb</RoadDescription>', $xml);
        $this->assertStringContainsString('<DistanceKm>140.00</DistanceKm>', $xml);
        $this->assertStringContainsString('<Expenses>', $xml);
        $this->assertStringContainsString('<Type>Toll</Type>', $xml);
        $this->assertStringContainsString('<Description>Highway toll Ljubljana-Zagreb</Description>', $xml);
        $this->assertStringContainsString('<Currency>EUR</Currency>', $xml);
    }

    /**
     * XML export of a completed order contains workflow and totals fields.
     *
     * @return void
     */
    public function testXmlExportCompletedContainsWorkflowAndTotals(): void
    {
        $order = $this->makeOrder(TravelOrder::STATUS_COMPLETED);
        $order->net_total = 29.40;
        $order->total = 45.50;

        $xml = $this->export->export('xml', [$order]);

        $this->assertIsString($xml);
        $this->assertStringContainsString('<ApprovedBy>', $xml);
        $this->assertStringContainsString('<ProcessedBy>', $xml);
        $this->assertStringContainsString('<NetTotal>29.40</NetTotal>', $xml);
        $this->assertStringContainsString('<Total>45.50</Total>', $xml);
    }

    /**
     * Completed order XML also still contains the header.
     *
     * @return void
     */
    public function testXmlExportCompletedContainsHeader(): void
    {
        $order = $this->makeOrder(TravelOrder::STATUS_COMPLETED);
        $xml = $this->export->export('xml', [$order]);

        $this->assertIsString($xml);
        $this->assertStringContainsString('<No>TO-001</No>', $xml);
        $this->assertStringContainsString('<Status>completed</Status>', $xml);
    }

    // ---------------------------------------------------------------
    // export('xml', …) – structure
    // ---------------------------------------------------------------

    /**
     * XML export produces a valid XML document with root element <TravelOrders>.
     *
     * @return void
     */
    public function testXmlExportRootElement(): void
    {
        $order = $this->makeOrder();
        $xml = $this->export->export('xml', [$order]);

        $this->assertIsString($xml);
        $dom = new DOMDocument();
        $loaded = $dom->loadXML($xml);
        $this->assertTrue($loaded, 'XML output must be valid XML');
        $this->assertEquals('TravelOrders', $dom->documentElement->tagName);
    }

    /**
     * Exporting multiple orders produces one <TravelOrder> node per entity.
     *
     * @return void
     */
    public function testXmlExportMultipleOrdersProducesMultipleNodes(): void
    {
        $order1 = $this->makeOrder(TravelOrder::STATUS_APPROVED);
        $order2 = $this->makeOrder(TravelOrder::STATUS_COMPLETED);
        $order2->no = 'TO-002';
        $order2->title = 'Second trip';

        $xml = $this->export->export('xml', [$order1, $order2]);

        $this->assertIsString($xml);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $nodes = $dom->getElementsByTagName('TravelOrder');
        $this->assertCount(2, $nodes);
    }

    /**
     * Exporting an empty array returns a valid XML document with no TravelOrder nodes.
     *
     * @return void
     */
    public function testXmlExportEmptyArray(): void
    {
        $xml = $this->export->export('xml', []);

        $this->assertIsString($xml);
        $dom = new DOMDocument();
        $loaded = $dom->loadXML($xml);
        $this->assertTrue($loaded);
        $this->assertCount(0, $dom->getElementsByTagName('TravelOrder'));
    }

    // ---------------------------------------------------------------
    // export('pdf', …)
    // ---------------------------------------------------------------

    /**
     * PDF export of a non-completed order returns a binary PDF string (TCPDF engine).
     *
     * @return void
     */
    public function testPdfExportNonCompletedReturnsBinaryPdf(): void
    {
        Configure::write('Pdf.pdfEngine', 'TCPDF');
        Configure::write('Pdf.TCPDF', []);

        $order = $this->makeOrder(TravelOrder::STATUS_APPROVED);
        $result = $this->export->export('pdf', [$order]);

        $this->assertIsString($result);
        $this->assertStringStartsWith('%PDF', $result);
    }

    /**
     * PDF export of a completed order (with mileages + expenses) returns a PDF.
     *
     * @return void
     */
    public function testPdfExportCompletedReturnsBinaryPdf(): void
    {
        Configure::write('Pdf.pdfEngine', 'TCPDF');
        Configure::write('Pdf.TCPDF', []);

        $order = $this->makeOrder(TravelOrder::STATUS_COMPLETED);
        $order->travel_orders_mileages = [$this->makeMileage()];
        $order->travel_orders_expenses = [$this->makeExpense()];
        $order->net_total = 45.50;
        $order->total = 45.50;

        $result = $this->export->export('pdf', [$order]);

        $this->assertIsString($result);
        $this->assertStringStartsWith('%PDF', $result);
    }

    /**
     * PDF export of multiple orders returns a single merged PDF string.
     *
     * @return void
     */
    public function testPdfExportMultipleOrdersReturnsSinglePdf(): void
    {
        Configure::write('Pdf.pdfEngine', 'TCPDF');
        Configure::write('Pdf.TCPDF', []);

        $order1 = $this->makeOrder(TravelOrder::STATUS_APPROVED);
        $order2 = $this->makeOrder(TravelOrder::STATUS_COMPLETED);
        $order2->no = 'TO-002';

        $result = $this->export->export('pdf', [$order1, $order2]);

        $this->assertIsString($result);
        $this->assertStringStartsWith('%PDF', $result);
    }

    // ---------------------------------------------------------------
    // response()
    // ---------------------------------------------------------------

    /**
     * response('xml', …) returns a response with XML content type.
     *
     * @return void
     */
    public function testResponseXmlContentType(): void
    {
        $response = $this->export->response('xml', '<?xml version="1.0"?><TravelOrders/>');
        $this->assertStringContainsString('xml', $response->getHeaderLine('Content-Type'));
    }

    /**
     * response('pdf', …) returns a response with PDF content type.
     *
     * @return void
     */
    public function testResponsePdfContentType(): void
    {
        $response = $this->export->response('pdf', '%PDF-1.4 binary');
        $this->assertStringContainsString('pdf', $response->getHeaderLine('Content-Type'));
    }

    /**
     * response('html', …) returns a response with HTML content type.
     *
     * @return void
     */
    public function testResponseHtmlContentType(): void
    {
        $response = $this->export->response('html', '<html><body></body></html>');
        $this->assertStringContainsString('html', $response->getHeaderLine('Content-Type'));
    }

    /**
     * response() with download=true adds a Content-Disposition attachment header.
     *
     * @return void
     */
    public function testResponseWithDownloadAddsDispositionHeader(): void
    {
        $response = $this->export->response('xml', '<TravelOrders/>', [
            'download' => true,
            'filename' => 'my-trip',
        ]);
        $disposition = $response->getHeaderLine('Content-Disposition');
        $this->assertNotEmpty($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('my-trip.xml', $disposition);
    }

    /**
     * response() uses the default filename 'travel-orders' when none is given.
     *
     * @return void
     */
    public function testResponseDefaultFilenameInDispositionHeader(): void
    {
        $response = $this->export->response('pdf', '%PDF', ['download' => true]);
        $this->assertStringContainsString('travel-orders.pdf', $response->getHeaderLine('Content-Disposition'));
    }

    /**
     * response() without download option does not set Content-Disposition.
     *
     * @return void
     */
    public function testResponseWithoutDownloadHasNoDispositionHeader(): void
    {
        $response = $this->export->response('xml', '<TravelOrders/>');
        $this->assertEmpty($response->getHeaderLine('Content-Disposition'));
    }

    /**
     * response() body matches the data string passed in.
     *
     * @return void
     */
    public function testResponseBodyMatchesData(): void
    {
        $data = '<?xml version="1.0"?><TravelOrders/>';
        $response = $this->export->response('xml', $data);
        $this->assertEquals($data, (string)$response->getBody());
    }
}
