<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * LilCrm\Controller\ContactsAccountsController Test Case
 */
class ContactsAccountsControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.LilCrm.Contacts',
        'ContactsAccounts' => 'plugin.LilCrm.ContactsAccounts',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'environment' => [
                'SERVER_NAME' => 'localhost',
            ]
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
            'primary' => 0,
            'kind' => 'O',
            'bban' => 'SI56 2420 3901 0691 882',
            'bic' => 'KREKSI22',
        ];

        $this->post('/lil_crm/ContactsAccounts/add/' . COMPANY_FIRST, $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/ContactsAccounts/add/' . COMPANY_FIRST);
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'LilCrm', 'controller' => 'ContactsAccounts', 'action' => 'add', COMPANY_FIRST], $data);
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
            'id' => '1',
            'contact_id' => COMPANY_FIRST,
            'primary' => 1,
            'kind' => 'W',
            'bban' => 'SI56 2420 3901 0691 886',
            'bic' => 'KREKSI22',
        ];

        $this->post('/lil_crm/ContactsAccounts/edit/1', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/ContactsAccounts/edit/1');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'LilCrm', 'controller' => 'ContactsAccounts', 'action' => 'edit', '1'], $data);
        $this->assertRedirectContains('/lil_crm/contacts/view/' . COMPANY_FIRST);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->get('/lil_crm/ContactsAccounts/delete/1');
        $this->assertRedirect(); // to login

        $this->login(USER_ADMIN);

        $this->get('/lil_crm/ContactsAccounts/delete/1');
        $this->assertRedirectContains('/lil_crm/contacts/view/' . COMPANY_FIRST); // no trailing slash = no trailing adrema id!!!
    }
}
