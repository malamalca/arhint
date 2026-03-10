<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Documents\Controller\TravelOrdersExpensesController Test Case
 */
class TravelOrdersExpensesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'TravelOrders' => 'plugin.Documents.TravelOrders',
        'TravelOrdersExpenses' => 'plugin.Documents.TravelOrdersExpenses',
        'TravelOrdersMileages' => 'plugin.Documents.TravelOrdersMileages',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
        'DocumentsClients' => 'plugin.Documents.DocumentsClients',
        'DocumentsLinks' => 'plugin.Documents.DocumentsLinks',
        'Attachments' => 'app.Attachments',
    ];

    /**
     * TravelOrder UUID from fixture
     */
    private const TRAVEL_ORDER_ID = 'a1b23456-7890-4bcd-8f12-345678901234';

    /**
     * TravelOrdersExpense UUID from fixture
     */
    private const EXPENSE_ID = 'c1d23456-7890-4bcd-8f12-345678901234';

    public function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'environment' => [
                'SERVER_NAME' => 'localhost',
            ],
        ]);
    }

    /**
     * @return void
     */
    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test edit GET - new expense form
     *
     * @return void
     */
    public function testEditNew(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders-expenses/edit?travel_order_id=' . self::TRAVEL_ORDER_ID);
        $this->assertResponseOk();
    }

    /**
     * Test edit GET - existing expense
     *
     * @return void
     */
    public function testEditExisting(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders-expenses/edit/' . self::EXPENSE_ID);
        $this->assertResponseOk();
    }

    /**
     * Test edit POST - save new expense
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'id' => '',
            'travel_order_id' => self::TRAVEL_ORDER_ID,
            'type' => 'Parking',
            'description' => 'Parking fee Zagreb',
            'quantity' => '1',
            'price' => '5.00',
            'currency' => 'EUR',
            'redirect' => '',
        ];

        $this->post('/documents/travel-orders-expenses/edit', $data);
        $this->assertRedirect();
    }

    /**
     * Test edit POST - update existing expense
     *
     * @return void
     */
    public function testEditPostExisting(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'id' => self::EXPENSE_ID,
            'travel_order_id' => self::TRAVEL_ORDER_ID,
            'type' => 'Toll',
            'description' => 'Highway toll - updated',
            'quantity' => '1',
            'price' => '17.00',
            'currency' => 'EUR',
            'redirect' => '',
        ];

        $this->post('/documents/travel-orders-expenses/edit/' . self::EXPENSE_ID, $data);
        $this->assertRedirect();
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders-expenses/delete/' . self::EXPENSE_ID);
        $this->assertRedirect();
    }
}
