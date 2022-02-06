<?php
declare(strict_types=1);

namespace Crm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Crm\Controller\ContactsController Test Case
 */
class ContactsControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsEmails' => 'plugin.Crm.ContactsEmails',
        'ContactsPhones' => 'plugin.Crm.ContactsPhones',
        'ContactsAddresses' => 'plugin.Crm.ContactsAddresses',
        'ContactsAccounts' => 'plugin.Crm.ContactsAccounts',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
        'Invoices' => 'plugin.Documents.Invoices',
        'DocumentsClients' => 'plugin.Documents.DocumentsClients',
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

        $this->get('crm/Contacts/index');
        $this->assertResponseOk();

        $this->get('crm/Contacts/index?search=arhim');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('crm/Contacts/view/' . COMPANY_FIRST);
        $this->assertResponseOk();
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
            'kind' => 'T',
            'name' => 'Another',
            'surname' => 'User',
            'descript' => '',
            'company_id' => '',
            'job' => '',
            'primary_email' => [
                'id' => '',
                'contact_id' => '',
                'kind' => 'P',
                'email' => 'another.user@test.com',
            ],
        ];

        $this->post('/crm/Contacts/edit?kind=T', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/crm/Contacts/edit?kind=T');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/crm/Contacts/edit?kind=T', $data);
        $this->assertRedirectContains('/crm/contacts/view/');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        $data = [
            'id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a45',
            'owner_id' => COMPANY_FIRST,
            'kind' => 'T',
            'name' => 'Renamed',
            'surname' => 'Contact',
            'descript' => '',
            'company_id' => '',
            'job' => '',
            'primary_email' => [
                'id' => '',
                'contact_id' => '',
                'kind' => '',
                'email' => '',
            ],
        ];

        $this->post('/crm/Contacts/edit/49a90cfe-fda4-49ca-b7ec-ca50783b5a45', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/crm/Contacts/edit/49a90cfe-fda4-49ca-b7ec-ca50783b5a45');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/crm/Contacts/edit/49a90cfe-fda4-49ca-b7ec-ca50783b5a45', $data);
        $this->assertRedirectContains('/crm/contacts/view/');
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

        $this->get('crm/Contacts/delete/49a90cfe-fda4-49ca-b7ec-ca50783b5a45');
        $this->assertRedirectContains('/crm/contacts');
    }
}
