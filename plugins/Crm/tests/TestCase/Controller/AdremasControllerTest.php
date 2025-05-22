<?php
declare(strict_types=1);

namespace Crm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Crm\Controller\AdremasController Test Case
 */
class AdremasControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Adremas' => 'plugin.Crm.Adremas',
        'AdremasContacts' => 'plugin.Crm.AdremasContacts',
        'Users' => 'app.Users',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
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
            'owner_id' => COMPANY_FIRST,
            'title' => 'Test Add Adrema',
        ];

        $this->post('/crm/adremas/edit', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/crm/adremas/edit');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'Crm', 'controller' => 'Adremas', 'action' => 'edit'], $data);
        $this->assertRedirectContains('/crm/labels/adrema/');
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

        $this->post('/crm/adremas/edit/49a90cfe-fda4-49ca-b7ec-ca5534465431', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/crm/adremas/edit/49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'Crm', 'controller' => 'Adremas', 'action' => 'edit', '49a90cfe-fda4-49ca-b7ec-ca5534465431'], $data);
        $this->assertRedirectContains('/crm/labels/adrema/');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->get('/crm/adremas/delete/49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertRedirect(); // to login

        $this->login(USER_ADMIN);

        $this->get('/crm/adremas/delete/49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertRedirectContains('/crm/labels/adrema'); // no trailing slash = no trailing adrema id!!!
    }
}
