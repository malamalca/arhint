<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Documents\Controller\VatsController Test Case
 */
class VatsControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Vats' => 'plugin.Documents.Vats',
        'Items' => 'plugin.Documents.Items',
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

        $this->get('documents/vats/index');
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
            'descript' => '99 %',
            'percent' => 99,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('documents/vats/edit', $data);
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
            'id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'descript' => '99 %',
            'percent' => 99,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('documents/vats/edit/3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c', $data);
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

        $this->get('documents/vats/delete/3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c');
        $this->assertRedirect();
    }
}
