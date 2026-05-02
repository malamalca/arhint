<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Event;

use App\Model\Entity\User;
use ArrayObject;
use Authorization\AuthorizationServiceInterface;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Event\DocumentsAIToolsEvents;

/**
 * Documents\Event\DocumentsAIToolsEvents Test Case
 */
class DocumentsAIToolsEventsTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.Invoices',
        'plugin.Documents.InvoicesItems',
        'plugin.Documents.InvoicesTaxes',
        'plugin.Documents.Vats',
        'plugin.Documents.Documents',
        'plugin.Documents.DocumentsClients',
        'plugin.Documents.TravelOrders',
        'plugin.Documents.TravelOrdersExpenses',
    ];

    protected DocumentsAIToolsEvents $listener;
    protected User $user;

    /** Fixture IDs */
    private const COUNTER_INVOICES_RECEIVED = '1d53bc5b-de2d-4e85-b13b-81b39a97fc88';
    private const COUNTER_INVOICES_ISSUED = '1d53bc5b-de2d-4e85-b13b-81b39a97fc89';
    private const COUNTER_DOCUMENTS = '1d53bc5b-de2d-4e85-b13b-81b39a97fc90';
    private const COUNTER_TRAVEL_ORDERS = '1d53bc5b-de2d-4e85-b13b-81b39a97fc91';
    private const INVOICE_RECEIVED = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5';
    private const INVOICE_ISSUED = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6';
    private const INVOICE_ITEM_ID = 1;
    private const VAT_22 = '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c';
    private const DOCUMENT_ID = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6';
    private const TRAVEL_ORDER_WAITING = 'a1b23456-7890-4bcd-8f12-345678901234';
    private const TRAVEL_ORDER_APPROVED = 'a2b23456-7890-4bcd-8f12-345678901234';

    public function setUp(): void
    {
        parent::setUp();
        $this->listener = new DocumentsAIToolsEvents();

        $authService = $this->createMock(AuthorizationServiceInterface::class);
        $authService->method('applyScope')
            ->willReturnCallback(fn($identity, $action, $resource) => $resource);
        $authService->method('can')
            ->willReturn(true);

        $this->user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $this->user->setAuthorization($authService);
    }

    // -------------------------------------------------------------------------
    // implementedEvents
    // -------------------------------------------------------------------------

    public function testImplementedEvents(): void
    {
        $events = $this->listener->implementedEvents();

        $this->assertArrayHasKey('App.AIAssistant.tools', $events);
        $this->assertArrayHasKey('App.AIAssistant.executeTool', $events);
        $this->assertCount(2, $events);
        $this->assertEquals('aiAssistantTools', $events['App.AIAssistant.tools']);
        $this->assertEquals('aiAssistantExecuteTool', $events['App.AIAssistant.executeTool']);
    }

    // -------------------------------------------------------------------------
    // aiAssistantTools — tool registration
    // -------------------------------------------------------------------------

    public function testAiAssistantToolsRegisters16Tools(): void
    {
        $event = new Event('App.AIAssistant.tools');
        $toolsList = new ArrayObject();
        $this->listener->aiAssistantTools($event, $toolsList);

        $this->assertCount(16, $toolsList);

        $names = array_map(fn($t) => $t->name, iterator_to_array($toolsList));
        $this->assertContains('Documents.get_document_counters', $names);
        $this->assertContains('Documents.search_invoices', $names);
        $this->assertContains('Documents.get_invoice', $names);
        $this->assertContains('Documents.create_invoice', $names);
        $this->assertContains('Documents.add_invoice_item', $names);
        $this->assertContains('Documents.update_invoice_item', $names);
        $this->assertContains('Documents.delete_invoice_item', $names);
        $this->assertContains('Documents.get_invoice_report', $names);
        $this->assertContains('Documents.search_documents', $names);
        $this->assertContains('Documents.get_document', $names);
        $this->assertContains('Documents.search_travel_orders', $names);
        $this->assertContains('Documents.get_travel_order', $names);
        $this->assertContains('Documents.create_travel_order', $names);
        $this->assertContains('Documents.add_travel_expense', $names);
        $this->assertContains('Documents.send_document_email', $names);
        $this->assertContains('Documents.submit_travel_order', $names);
    }

    // -------------------------------------------------------------------------
    // get_document_counters
    // -------------------------------------------------------------------------

    public function testGetDocumentCountersReturnsAll(): void
    {
        $event = $this->makeEvent('Documents.get_document_counters', []);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_document_counters', []);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
    }

    public function testGetDocumentCountersFilteredByKind(): void
    {
        $args = ['kind' => 'Invoices'];
        $event = $this->makeEvent('Documents.get_document_counters', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_document_counters', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // search_invoices
    // -------------------------------------------------------------------------

    public function testSearchInvoicesReturnsArray(): void
    {
        $event = $this->makeEvent('Documents.search_invoices', []);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.search_invoices', []);

        $this->assertIsArray($event->getResult());
    }

    public function testSearchInvoicesFilteredByCounter(): void
    {
        $args = ['counter_id' => self::COUNTER_INVOICES_RECEIVED];
        $event = $this->makeEvent('Documents.search_invoices', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.search_invoices', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(self::INVOICE_RECEIVED, $result[0]->id);
    }

    // -------------------------------------------------------------------------
    // get_invoice
    // -------------------------------------------------------------------------

    public function testGetInvoiceFound(): void
    {
        $args = ['id' => self::INVOICE_ISSUED];
        $event = $this->makeEvent('Documents.get_invoice', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_invoice', $args);

        $result = $event->getResult();
        $this->assertNotNull($result);
        $this->assertIsNotArray($result);
        $this->assertEquals(self::INVOICE_ISSUED, $result->id);
    }

    public function testGetInvoiceNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Documents.get_invoice', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_invoice', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // create_invoice
    // -------------------------------------------------------------------------

    public function testCreateInvoice(): void
    {
        $args = [
            'counter_id' => self::COUNTER_INVOICES_RECEIVED,
            'title' => 'Test Invoice',
            'dat_issue' => '2025-01-15',
        ];
        $event = $this->makeEvent('Documents.create_invoice', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.create_invoice', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Test Invoice', $result['title']);
    }

    // -------------------------------------------------------------------------
    // add_invoice_item
    // -------------------------------------------------------------------------

    public function testAddInvoiceItemNotFound(): void
    {
        $args = ['invoice_id' => '00000000-0000-0000-0000-000000000000', 'descript' => 'x', 'qty' => 1,
            'unit' => 'pcs', 'price' => 10, 'vat_id' => self::VAT_22];
        $event = $this->makeEvent('Documents.add_invoice_item', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.add_invoice_item', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testAddInvoiceItem(): void
    {
        $args = [
            'invoice_id' => self::INVOICE_ISSUED,
            'descript' => 'Test item',
            'qty' => 2,
            'unit' => 'pcs',
            'price' => 50.0,
            'discount' => 0,
            'vat_id' => self::VAT_22,
        ];
        $event = $this->makeEvent('Documents.add_invoice_item', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.add_invoice_item', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    // -------------------------------------------------------------------------
    // update_invoice_item
    // -------------------------------------------------------------------------

    public function testUpdateInvoiceItemNotFound(): void
    {
        $args = ['id' => '999999', 'qty' => 5];
        $event = $this->makeEvent('Documents.update_invoice_item', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.update_invoice_item', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testUpdateInvoiceItem(): void
    {
        $args = ['id' => (string)self::INVOICE_ITEM_ID, 'qty' => 3, 'descript' => 'Updated item'];
        $event = $this->makeEvent('Documents.update_invoice_item', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.update_invoice_item', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    // -------------------------------------------------------------------------
    // delete_invoice_item
    // -------------------------------------------------------------------------

    public function testDeleteInvoiceItemNotFound(): void
    {
        $args = ['id' => '999999'];
        $event = $this->makeEvent('Documents.delete_invoice_item', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.delete_invoice_item', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testDeleteInvoiceItem(): void
    {
        $args = ['id' => (string)self::INVOICE_ITEM_ID];
        $event = $this->makeEvent('Documents.delete_invoice_item', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.delete_invoice_item', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    // -------------------------------------------------------------------------
    // get_invoice_report
    // -------------------------------------------------------------------------

    public function testGetInvoiceReportRequiresCounterId(): void
    {
        $event = $this->makeEvent('Documents.get_invoice_report', []);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_invoice_report', []);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetInvoiceReport(): void
    {
        $args = ['counter_id' => self::COUNTER_INVOICES_RECEIVED];
        $event = $this->makeEvent('Documents.get_invoice_report', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_invoice_report', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('sum_net_total', $result);
        $this->assertArrayHasKey('sum_total', $result);
        $this->assertEquals(self::COUNTER_INVOICES_RECEIVED, $result['counter_id']);
        $this->assertGreaterThanOrEqual(1, $result['count']);
    }

    // -------------------------------------------------------------------------
    // search_documents
    // -------------------------------------------------------------------------

    public function testSearchDocumentsReturnsArray(): void
    {
        $event = $this->makeEvent('Documents.search_documents', []);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.search_documents', []);

        $this->assertIsArray($event->getResult());
    }

    // -------------------------------------------------------------------------
    // get_document
    // -------------------------------------------------------------------------

    public function testGetDocumentFound(): void
    {
        $args = ['id' => self::DOCUMENT_ID];
        $event = $this->makeEvent('Documents.get_document', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_document', $args);

        $result = $event->getResult();
        $this->assertNotNull($result);
        $this->assertIsNotArray($result);
        $this->assertEquals(self::DOCUMENT_ID, $result->id);
    }

    public function testGetDocumentNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Documents.get_document', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_document', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // search_travel_orders
    // -------------------------------------------------------------------------

    public function testSearchTravelOrdersReturnsArray(): void
    {
        $event = $this->makeEvent('Documents.search_travel_orders', []);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.search_travel_orders', []);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testSearchTravelOrdersFilterByStatus(): void
    {
        $args = ['status' => 'approved'];
        $event = $this->makeEvent('Documents.search_travel_orders', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.search_travel_orders', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('approved', $result[0]->status);
    }

    // -------------------------------------------------------------------------
    // get_travel_order
    // -------------------------------------------------------------------------

    public function testGetTravelOrderFound(): void
    {
        $args = ['id' => self::TRAVEL_ORDER_WAITING];
        $event = $this->makeEvent('Documents.get_travel_order', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_travel_order', $args);

        $result = $event->getResult();
        $this->assertNotNull($result);
        $this->assertIsNotArray($result);
        $this->assertEquals(self::TRAVEL_ORDER_WAITING, $result->id);
    }

    public function testGetTravelOrderNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Documents.get_travel_order', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.get_travel_order', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // create_travel_order
    // -------------------------------------------------------------------------

    public function testCreateTravelOrder(): void
    {
        $args = [
            'counter_id' => self::COUNTER_TRAVEL_ORDERS,
            'title' => 'Test Trip',
            'dat_task' => '2025-03-15',
            'location' => 'Zagreb',
        ];
        $event = $this->makeEvent('Documents.create_travel_order', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.create_travel_order', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('draft', $result['status']);
    }

    // -------------------------------------------------------------------------
    // add_travel_expense
    // -------------------------------------------------------------------------

    public function testAddTravelExpenseNotFound(): void
    {
        $args = ['travel_order_id' => '00000000-0000-0000-0000-000000000000',
            'type' => 'fuel', 'quantity' => 1, 'price' => 20, 'currency' => 'EUR'];
        $event = $this->makeEvent('Documents.add_travel_expense', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.add_travel_expense', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testAddTravelExpense(): void
    {
        $args = [
            'travel_order_id' => self::TRAVEL_ORDER_APPROVED,
            'type' => 'meal',
            'quantity' => 1,
            'price' => 12.50,
            'currency' => 'EUR',
            'description' => 'Lunch',
        ];
        $event = $this->makeEvent('Documents.add_travel_expense', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.add_travel_expense', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    // -------------------------------------------------------------------------
    // submit_travel_order
    // -------------------------------------------------------------------------

    public function testSubmitTravelOrderNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000', 'action' => 'sign'];
        $event = $this->makeEvent('Documents.submit_travel_order', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.submit_travel_order', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testSubmitTravelOrderProcess(): void
    {
        // waiting_processing → completed (admin action)
        $args = ['id' => self::TRAVEL_ORDER_WAITING, 'action' => 'process'];
        $event = $this->makeEvent('Documents.submit_travel_order', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.submit_travel_order', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('completed', $result['status']);
    }

    public function testSubmitTravelOrderSubmit(): void
    {
        // approved → waiting_processing
        $args = ['id' => self::TRAVEL_ORDER_APPROVED, 'action' => 'submit'];
        $event = $this->makeEvent('Documents.submit_travel_order', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.submit_travel_order', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('waiting_processing', $result['status']);
    }

    // -------------------------------------------------------------------------
    // Unknown tool
    // -------------------------------------------------------------------------

    public function testUnknownToolDoesNothing(): void
    {
        $event = $this->makeEvent('Documents.nonexistent_tool', []);
        $this->listener->aiAssistantExecuteTool($event, 'Documents.nonexistent_tool', []);

        $this->assertNull($event->getResult());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEvent(string $tool, array $arguments): Event
    {
        return new Event('App.AIAssistant.executeTool', null, [$tool, $arguments, $this->user]);
    }
}
