<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Documents\Model\Entity\TravelOrder;

/**
 * Documents\Controller\TravelOrdersController Test Case
 */
class TravelOrdersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'Attachments' => 'app.Attachments',
        'TravelOrders' => 'plugin.Documents.TravelOrders',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
        'DocumentsLinks' => 'plugin.Documents.DocumentsLinks',
        'DocumentsClients' => 'plugin.Documents.DocumentsClients',
        'Projects' => 'plugin.Projects.Projects',
    ];

    /**
     * Counter UUID used in fixtures
     */
    private const COUNTER_ID = '1d53bc5b-de2d-4e85-b13b-81b39a97fc91';

    /**
     * TravelOrder UUID used in fixtures (status: waiting_processing)
     */
    private const TRAVEL_ORDER_ID = 'a1b23456-7890-4bcd-8f12-345678901234';

    /**
     * TravelOrder UUID with status: draft
     */
    private const DRAFT_ORDER_ID = 'a3b23456-7890-4bcd-8f12-345678901234';

    /**
     * TravelOrder UUID with status: waiting_approval
     */
    private const WAITING_APPROVAL_ORDER_ID = 'a4b23456-7890-4bcd-8f12-345678901234';

    public function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'environment' => [
                'SERVER_NAME' => 'localhost',
            ],
        ]);
    }

    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method - redirect to default counter
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders/index');
        $this->assertResponseOk();
    }

    /**
     * Test index method with explicit counter
     *
     * @return void
     */
    public function testIndexWithCounter(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders/index?counter=' . self::COUNTER_ID);
        $this->assertResponseOk();
    }

    /**
     * Test index method with text search via q parameter
     *
     * @return void
     */
    public function testIndexSearch(): void
    {
        $this->login(USER_ADMIN);

        // 'Zagreb' appears in fixture title 'Business trip to Zagreb'
        $this->get('/documents/travel-orders/index?counter=' . self::COUNTER_ID . '&q=Zagreb');
        $this->assertResponseOk();
        $this->assertResponseContains('Business trip to Zagreb');
    }

    /**
     * Test index search with a term that matches no records
     *
     * @return void
     */
    public function testIndexSearchNoResults(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders/index?counter=' . self::COUNTER_ID . '&q=zzznomatch999');
        $this->assertResponseOk();
        $this->assertResponseNotContains('Business trip to Zagreb');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders/view/' . self::TRAVEL_ORDER_ID);
        $this->assertResponseOk();
    }

    /**
     * Test edit GET - show form for new travel order
     *
     * @return void
     */
    public function testEditNew(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders/edit?counter=' . self::COUNTER_ID);
        $this->assertResponseOk();
    }

    /**
     * Test edit GET - show form for existing travel order
     *
     * @return void
     */
    public function testEditExisting(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders/edit/' . self::TRAVEL_ORDER_ID);
        $this->assertResponseOk();
    }

    /**
     * Test add new travel order via POST
     *
     * @return void
     */
    public function testAdd(): void
    {
        $this->login(USER_ADMIN);

        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counter = $counters->get(self::COUNTER_ID);

        $data = [
            'id' => '',
            'owner_id' => COMPANY_FIRST,
            'counter_id' => self::COUNTER_ID,
            'doc_type' => 'TO',
            'title' => 'New Business Trip',
            'descript' => 'Travel to Vienna for conference',
            'dat_issue' => '2015-03-01',
            'location' => 'Ljubljana',
            'taskee' => 'John Doe',
            'dat_task' => '2015-03-05',
            'departure' => '2015-03-05 07:00:00',
            'arrival' => '2015-03-05 22:00:00',
            'vehicle_registration' => 'LJ BC-002',
            'vehicle_owner' => 'Company',
            'advance' => null,
            'dat_advance' => null,
            'total' => 80.00,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/travel-orders/edit?counter=' . self::COUNTER_ID, $data);

        $TravelOrders = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
        $newOrder = $TravelOrders
            ->find()
            ->where(['counter_id' => self::COUNTER_ID])
            ->orderBy(['created DESC'])
            ->first();

        $this->assertRedirect(['action' => 'view', $newOrder->id]);

        $this->assertEquals('New Business Trip', $newOrder->title);
        $this->assertEquals($counter->counter + 1, $newOrder->counter);
        $this->assertEquals(TravelOrder::STATUS_DRAFT, $newOrder->status);

        // verify counter incremented
        $updatedCounter = $counters->get(self::COUNTER_ID);
        $this->assertEquals($counter->counter + 1, $updatedCounter->counter);
    }

    /**
     * Test edit existing travel order via POST
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->login(USER_ADMIN);

        $data = [
            'id' => self::TRAVEL_ORDER_ID,
            'owner_id' => COMPANY_FIRST,
            'counter_id' => self::COUNTER_ID,
            'doc_type' => 'TO',
            'title' => 'Updated Business Trip',
            'descript' => 'Updated description',
            'dat_issue' => '2015-02-08',
            'location' => 'Ljubljana',
            'taskee' => 'Jane Doe',
            'dat_task' => '2015-02-10',
            'departure' => '2015-02-10 06:00:00',
            'arrival' => '2015-02-10 20:00:00',
            'vehicle_registration' => 'LJ AB-001',
            'vehicle_owner' => 'Company',
            'total' => 55.00,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post(
            '/documents/travel-orders/edit/' . self::TRAVEL_ORDER_ID . '?counter=' . self::COUNTER_ID,
            $data,
        );
        $this->assertRedirect(['action' => 'view', self::TRAVEL_ORDER_ID]);

        $TravelOrders = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
        $order = $TravelOrders->get(self::TRAVEL_ORDER_ID);

        $this->assertEquals('Updated Business Trip', $order->title);
        $this->assertEquals('Jane Doe', $order->taskee);
        $this->assertEquals(55.00, $order->total);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders/delete/' . self::TRAVEL_ORDER_ID);
        $this->assertRedirect(['action' => 'index', '?' => ['counter' => self::COUNTER_ID]]);

        $TravelOrders = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
        $this->assertFalse($TravelOrders->exists(['id' => self::TRAVEL_ORDER_ID]));
    }

    /**
     * Test sign GET - show sign confirmation page for a draft order
     *
     * @return void
     */
    public function testSignGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders/sign/' . self::DRAFT_ORDER_ID);
        $this->assertResponseOk();
    }

    /**
     * Test sign POST - moves draft travel order to waiting_approval
     *
     * @return void
     */
    public function testSignPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/travel-orders/sign/' . self::DRAFT_ORDER_ID);
        $this->assertRedirect(['action' => 'view', self::DRAFT_ORDER_ID]);

        $TravelOrders = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
        $order = $TravelOrders->get(self::DRAFT_ORDER_ID);
        $this->assertEquals(TravelOrder::STATUS_WAITING_APPROVAL, $order->status);
    }

    /**
     * Test decline POST - admin declines a waiting_approval travel order
     *
     * @return void
     */
    public function testDecline(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/travel-orders/decline/' . self::WAITING_APPROVAL_ORDER_ID);
        $this->assertRedirect(['action' => 'view', self::WAITING_APPROVAL_ORDER_ID]);

        $TravelOrders = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
        $order = $TravelOrders->get(self::WAITING_APPROVAL_ORDER_ID);
        $this->assertEquals(TravelOrder::STATUS_DECLINED, $order->status);
    }
}
