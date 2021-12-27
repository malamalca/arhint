<?php
declare(strict_types=1);

namespace Crm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Crm\Controller\ContactsPhonesController Test Case
 */
class ContactsPhonesControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsPhones' => 'plugin.Crm.ContactsPhones',
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
            'no' => '041 891 825',
            'kind' => 'O',
            'primary' => 0,
        ];

        $this->post('/crm/ContactsPhones/edit?contact=' . COMPANY_FIRST, $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/crm/contacts-phones/edit?contact=' . COMPANY_FIRST);
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'Crm', 'controller' => 'ContactsPhones', 'action' => 'edit', '?' => ['contact' => COMPANY_FIRST]], $data);
        $this->assertRedirectContains('/crm/contacts/view/' . COMPANY_FIRST);
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
            'no' => '041 891 825',
            'kind' => 'W',
            'primary' => 1,
        ];

        $this->post('/crm/ContactsPhones/edit/1', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/crm/ContactsPhones/edit/1');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'Crm', 'controller' => 'ContactsPhones', 'action' => 'edit', '1'], $data);
        $this->assertRedirectContains('/crm/contacts/view/' . COMPANY_FIRST);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->get('/crm/ContactsPhones/delete/1');
        $this->assertRedirect(); // to login

        $this->login(USER_ADMIN);

        $this->get('/crm/ContactsPhones/delete/1');
        $this->assertRedirectContains('/crm/contacts/view/' . COMPANY_FIRST); // no trailing slash = no trailing adrema id!!!
    }
}
