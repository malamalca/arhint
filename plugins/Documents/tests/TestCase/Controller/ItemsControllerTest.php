<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Documents\Controller\ItemsController Test Case
 */
class ItemsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'Items' => 'plugin.Documents.Items',
        'Vats' => 'plugin.Documents.Vats',
        'InvoicesItems' => 'plugin.Documents.InvoicesItems',
    ];

    private function login($userId)
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/documents/items/index');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => '',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
            'descript' => 'New Item',
            'qty' => 1,
            'unit' => 'pcs',
            'price' => 10,
            'discount' => 0,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/items/edit', $data);
        $this->assertRedirect();
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => '1',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
            'descript' => 'New Title',
            'qty' => 1,
            'unit' => 'pcs',
            'price' => 10,
            'discount' => 0,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/items/edit/1', $data);
        $this->assertRedirect();
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/documents/items/delete/1');
        $this->assertRedirect();
    }

    /**
     * Test autocomplete method
     *
     * @return void
     */
    public function testAutocomplete()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->configRequest([
            'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
        ]);

        $this->get('/documents/items/autocomplete?term=test');
        $this->assertResponseOk();
    }
}
