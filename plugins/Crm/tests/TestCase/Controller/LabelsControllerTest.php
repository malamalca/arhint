<?php
declare(strict_types=1);

namespace Crm\Test\TestCase\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Crm\Controller\LabelsController Test Case
 */
class LabelsControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Adremas' => 'plugin.Crm.Adremas',
        'AdremasContacts' => 'plugin.Crm.AdremasContacts',
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsAddresses' => 'plugin.Crm.ContactsAddresses',
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
     * Test adrema method
     *
     * @return void
     */
    public function testAdrema()
    {
        $this->get('/crm/labels/adrema/49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertRedirect(); // to login

        $this->login(USER_ADMIN);

        $this->get('/crm/labels/adrema/49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertNoRedirect();

        $this->get('/crm/labels/adrema/');
        $this->assertNoRedirect();

        $this->disableErrorHandlerMiddleware();
        $this->expectException(NotFoundException::class);
        $this->get('/crm/labels/adrema/49a90cfe-fda4-49ca-b7ec-nonexistant');
    }

    /**
     * Test label method
     *
     * @return void
     */
    public function testLabel()
    {
        $this->get('/crm/labels/label?adrema=49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertRedirect(); // to login

        $this->login(USER_ADMIN);

        $this->get('/crm/labels/label?adrema=49a90cfe-fda4-49ca-b7ec-ca5534465431');
        $this->assertNoRedirect();
    }
}
