<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Documents\Controller\TravelOrdersMileagesController Test Case
 */
class TravelOrdersMileagesControllerTest extends TestCase
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
     * TravelOrdersMileage UUID from fixture
     */
    private const MILEAGE_ID = 'b1c23456-7890-4bcd-8f12-345678901234';

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
     * Test edit GET - new mileage form
     *
     * @return void
     */
    public function testEditNew(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders-mileages/edit?travel_order_id=' . self::TRAVEL_ORDER_ID);
        $this->assertResponseOk();
    }

    /**
     * Test edit GET - existing mileage
     *
     * @return void
     */
    public function testEditExisting(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/travel-orders-mileages/edit/' . self::MILEAGE_ID);
        $this->assertResponseOk();
    }

    /**
     * Test edit POST - save new mileage
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
            'start_time' => '2015-02-11 08:00:00',
            'end_time' => '2015-02-11 12:00:00',
            'road_description' => 'Ljubljana - Maribor',
            'distance_km' => '130',
            'price_per_km' => '0.21',
            'redirect' => '',
        ];

        $this->post('/documents/travel-orders-mileages/edit', $data);
        $this->assertRedirect();
    }

    /**
     * Test edit POST - update existing mileage
     *
     * @return void
     */
    public function testEditPostExisting(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'id' => self::MILEAGE_ID,
            'travel_order_id' => self::TRAVEL_ORDER_ID,
            'road_description' => 'Ljubljana - Zagreb - updated',
            'distance_km' => '145',
            'price_per_km' => '0.21',
            'redirect' => '',
        ];

        $this->post('/documents/travel-orders-mileages/edit/' . self::MILEAGE_ID, $data);
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

        $this->get('/documents/travel-orders-mileages/delete/' . self::MILEAGE_ID);
        $this->assertRedirect();
    }
}
