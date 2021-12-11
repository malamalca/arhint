<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * LilCrm\Controller\ContactsAddressesController Test Case
 */
class ContactsAddressesControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.LilCrm.Contacts',
        'ContactsAddresses' => 'plugin.LilCrm.ContactsAddresses',
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
            'contact_id' => COMPANY_FIRST,
            'primary' => false,
            'kind' => 'O',
            'street' => 'Slakova ulica 20',
            'zip' => '8210',
            'city' => 'Trebnje',
            'country' => 'Slovenia',
        ];

        $this->post('/lil_crm/ContactsAddresses/edit?contact=' . COMPANY_FIRST, $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/ContactsAddresses/edit?contact=' . COMPANY_FIRST);
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'LilCrm', 'controller' => 'ContactsAddresses', 'action' => 'edit', '?' => ['contact' => COMPANY_FIRST]], $data);
        $this->assertRedirectContains('/lil_crm/contacts/view/' . COMPANY_FIRST);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        $data = [
            'id' => '49a90cfe-fda4-49ca-b7ed-ca50783b5a41',
            'contact_id' => COMPANY_FIRST,
            'primary' => false,
            'kind' => 'O',
            'street' => 'Slakova ulica 20',
            'zip' => '8210',
            'city' => 'Trebnje',
            'country' => 'Slovenia',
        ];

        $this->post('/lil_crm/ContactsAddresses/edit/49a90cfe-fda4-49ca-b7ed-ca50783b5a41', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/ContactsAddresses/edit/49a90cfe-fda4-49ca-b7ed-ca50783b5a41');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'LilCrm', 'controller' => 'ContactsAddresses', 'action' => 'edit', '49a90cfe-fda4-49ca-b7ed-ca50783b5a41'], $data);
        $this->assertRedirectContains('/lil_crm/contacts/view/' . COMPANY_FIRST);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->get('/lil_crm/ContactsAddresses/delete/49a90cfe-fda4-49ca-b7ed-ca50783b5a41');
        $this->assertRedirect(); // to login

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/ContactsAddresses/delete/49a90cfe-fda4-49ca-b7ed-ca50783b5a41');
        $this->assertRedirectContains('/lil_crm/contacts/view/' . COMPANY_FIRST); // no trailing slash = no trailing adrema id!!!
    }
}
