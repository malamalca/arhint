<?php
declare(strict_types=1);

namespace Crm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Crm\Controller\LabelsController Test Case
 */
class LabelsControllerTest extends TestCase
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
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsAddresses' => 'plugin.Crm.ContactsAddresses',
        'ContactsEmails' => 'plugin.Crm.ContactsEmails',
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
