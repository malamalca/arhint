<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * LilCrm\Controller\AdremasController Test Case
 */
class AdremasControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Adremas' => 'plugin.LilCrm.Adremas',
        'AdremasContacts' => 'plugin.LilCrm.AdremasContacts',
        'Users' => 'app.Users',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'environment' => [
                'SERVER_NAME' => 'localhost',
            ],
        ]);
    }

    protected function login($userId)
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd()
    {
        $data = [
            'id' => '',
            'owner_id' => COMPANY_FIRST,
            'title' => 'Test Add Adrema',
        ];

        $this->post('/lil_crm/adremas/add', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/adremas/add');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'LilCrm', 'controller' => 'Adremas', 'action' => 'add'], $data);
        $this->assertRedirectContains('/lil_crm/labels/adrema/');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        $data = [
            'id' => '49a90cfe-fda4-49ca-b7ec-ca5534465431',
            'owner_id' => COMPANY_FIRST,
            'title' => 'Test Edit Adrema',
        ];

        $this->post('/lil_crm/adremas/edit/49a90cfe-fda4-49ca-b7ec-ca5534465431', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/adremas/edit/49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'LilCrm', 'controller' => 'Adremas', 'action' => 'edit', '49a90cfe-fda4-49ca-b7ec-ca5534465431'], $data);
        $this->assertRedirectContains('/lil_crm/labels/adrema/');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->get('/lil_crm/adremas/delete/49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertRedirect(); // to login

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/adremas/delete/49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertRedirectContains('/lil_crm/labels/adrema'); // no trailing slash = no trailing adrema id!!!
    }
}
