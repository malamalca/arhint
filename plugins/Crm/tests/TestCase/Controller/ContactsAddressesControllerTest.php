<?php
declare(strict_types=1);

namespace Crm\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Crm\Controller\ContactsAddressesController Test Case
 */
class ContactsAddressesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsAddresses' => 'plugin.Crm.ContactsAddresses',
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

        $this->post('/crm/ContactsAddresses/edit?contact=' . COMPANY_FIRST, $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/crm/ContactsAddresses/edit?contact=' . COMPANY_FIRST);
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'Crm', 'controller' => 'ContactsAddresses', 'action' => 'edit', '?' => ['contact' => COMPANY_FIRST]], $data);
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
            'id' => '49a90cfe-fda4-49ca-b7ed-ca50783b5a41',
            'contact_id' => COMPANY_FIRST,
            'primary' => false,
            'kind' => 'O',
            'street' => 'Slakova ulica 20',
            'zip' => '8210',
            'city' => 'Trebnje',
            'country' => 'Slovenia',
        ];

        $this->post('/crm/ContactsAddresses/edit/49a90cfe-fda4-49ca-b7ed-ca50783b5a41', $data);
        $this->assertResponseError();

        $this->login(USER_ADMIN);

        $this->get('/crm/ContactsAddresses/edit/49a90cfe-fda4-49ca-b7ed-ca50783b5a41');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(['plugin' => 'Crm', 'controller' => 'ContactsAddresses', 'action' => 'edit', '49a90cfe-fda4-49ca-b7ed-ca50783b5a41'], $data);
        $this->assertRedirectContains('/crm/contacts/view/' . COMPANY_FIRST);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->get('/crm/ContactsAddresses/delete/49a90cfe-fda4-49ca-b7ed-ca50783b5a41');
        $this->assertRedirect(); // to login

        $this->login(USER_ADMIN);

        $this->get('/crm/ContactsAddresses/delete/49a90cfe-fda4-49ca-b7ed-ca50783b5a41');
        $this->assertRedirectContains('/crm/contacts/view/' . COMPANY_FIRST); // no trailing slash = no trailing adrema id!!!
    }

    /**
     * Test autocomplete method
     *
     * @return void
     */
    public function testAutocomplete()
    {
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
        $this->login(USER_ADMIN);

        $this->get('/crm/ContactsAddresses/autocomplete?term=Slakova');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $body = (string)$this->_response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('street', $data[0]);
    }

    /**
     * Test autocompleteZipCity method
     *
     * @return void
     */
    public function testAutocompleteZipCity()
    {
        $this->login(USER_ADMIN);

        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
        $this->get('/crm/ContactsAddresses/autocomplete-zip-city/zip?term=8210');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $body = (string)$this->_response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        foreach ($data as $item) {
            $this->assertStringStartsWith('8210', (string)$item['id']);
        }

        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
        $this->get('/crm/ContactsAddresses/autocomplete-zip-city/city?term=Tr');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        foreach ($data as $item) {
            $this->assertEqualsIgnoringCase('Tr', mb_substr((string)$item['label'], 0, 2));
        }
    }
}
