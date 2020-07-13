<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * LilCrm\Controller\ContactsController Test Case
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
        'Contacts' => 'plugin.LilCrm.Contacts',
        'ContactsEmails' => 'plugin.LilCrm.ContactsEmails',
        'ContactsPhones' => 'plugin.LilCrm.ContactsPhones',
        'ContactsAddresses' => 'plugin.LilCrm.ContactsAddresses',
        'ContactsAccounts' => 'plugin.LilCrm.ContactsAccounts',
        'InvoicesCounters' => 'plugin.LilInvoices.InvoicesCounters',
        'Invoices' => 'plugin.LilInvoices.Invoices',
        'InvoicesClients' => 'plugin.LilInvoices.InvoicesClients',
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

        $this->get('lil_crm/Contacts/index');
        $this->assertResponseOk();

        $this->get('lil_crm/Contacts/index?search=arhim');
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

        $this->get('lil_crm/Contacts/view/' . COMPANY_FIRST);
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

        $this->post('/lil_crm/Contacts/add/T', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/Contacts/add/T');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/lil_crm/Contacts/add/T', $data);
        $this->assertRedirectContains('/lil_crm/contacts/view/');
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

        $this->post('/lil_crm/Contacts/edit/49a90cfe-fda4-49ca-b7ec-ca50783b5a45', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/Contacts/edit/49a90cfe-fda4-49ca-b7ec-ca50783b5a45');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/lil_crm/Contacts/edit/49a90cfe-fda4-49ca-b7ec-ca50783b5a45', $data);
        $this->assertRedirectContains('/lil_crm/contacts/view/');
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

        $this->get('lil_crm/Contacts/delete/49a90cfe-fda4-49ca-b7ec-ca50783b5a45');
        $this->assertRedirectContains('/lil_crm/contacts');
    }
}
