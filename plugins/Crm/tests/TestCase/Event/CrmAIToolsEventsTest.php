<?php
declare(strict_types=1);

namespace Crm\Test\TestCase\Event;

use App\Model\Entity\User;
use ArrayObject;
use Authorization\AuthorizationServiceInterface;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Crm\Event\CrmAIToolsEvents;

/**
 * Crm\Event\CrmAIToolsEvents Test Case
 */
class CrmAIToolsEventsTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'plugin.Crm.Contacts',
        'plugin.Crm.ContactsEmails',
        'plugin.Crm.ContactsPhones',
        'plugin.Crm.ContactsAddresses',
        'plugin.Crm.ContactsAccounts',
        'plugin.Crm.ContactsSearchIndex',
    ];

    protected CrmAIToolsEvents $listener;
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->listener = new CrmAIToolsEvents();

        $authService = $this->createMock(AuthorizationServiceInterface::class);
        $authService->method('applyScope')
            ->willReturnCallback(fn($identity, $action, $resource) => $resource);
        $authService->method('can')
            ->willReturn(true);

        $this->user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $this->user->setAuthorization($authService);
    }

    // -------------------------------------------------------------------------
    // implementedEvents
    // -------------------------------------------------------------------------

    public function testImplementedEvents(): void
    {
        $events = $this->listener->implementedEvents();

        $this->assertArrayHasKey('App.AIAssistant.registerModule', $events);
        $this->assertArrayHasKey('App.AIAssistant.tools', $events);
        $this->assertArrayHasKey('App.AIAssistant.executeTool', $events);
        $this->assertCount(3, $events);
        $this->assertEquals('aiAssistantRegisterModule', $events['App.AIAssistant.registerModule']);
        $this->assertEquals('aiAssistantTools', $events['App.AIAssistant.tools']);
        $this->assertEquals('aiAssistantExecuteTool', $events['App.AIAssistant.executeTool']);
    }

    // -------------------------------------------------------------------------
    // aiAssistantTools — tool registration
    // -------------------------------------------------------------------------

    public function testAiAssistantToolsRegisters20Tools(): void
    {
        $event = new Event('App.AIAssistant.tools');
        $toolsList = new ArrayObject();
        $this->listener->aiAssistantTools($event, $toolsList);

        $this->assertCount(20, $toolsList);

        $names = array_map(fn($t) => $t->name, iterator_to_array($toolsList));
        $this->assertContains('Crm.navigate_to_contact', $names);
        $this->assertContains('Crm.search_contacts', $names);
        $this->assertContains('Crm.get_contact', $names);
        $this->assertContains('Crm.get_contact_logs', $names);
        $this->assertContains('Crm.add_contact_log', $names);
        $this->assertContains('Crm.lookup_company', $names);
        $this->assertContains('Crm.create_contact', $names);
        $this->assertContains('Crm.update_contact', $names);
        $this->assertContains('Crm.add_contact_phone', $names);
        $this->assertContains('Crm.edit_contact_phone', $names);
        $this->assertContains('Crm.delete_contact_phone', $names);
        $this->assertContains('Crm.add_contact_email', $names);
        $this->assertContains('Crm.edit_contact_email', $names);
        $this->assertContains('Crm.delete_contact_email', $names);
        $this->assertContains('Crm.add_contact_address', $names);
        $this->assertContains('Crm.edit_contact_address', $names);
        $this->assertContains('Crm.delete_contact_address', $names);
        $this->assertContains('Crm.add_contact_account', $names);
        $this->assertContains('Crm.edit_contact_account', $names);
        $this->assertContains('Crm.delete_contact_account', $names);
    }

    // -------------------------------------------------------------------------
    // navigate_to_contact
    // -------------------------------------------------------------------------

    public function testNavigateToContactRequiresSearch(): void
    {
        $event = $this->makeEvent('Crm.navigate_to_contact', []);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.navigate_to_contact', []);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('search', $result['error']);
    }

    public function testNavigateToContactNotFound(): void
    {
        $args = ['search' => 'xxxxxxxxxnotexistent', 'kind' => 'C'];
        $event = $this->makeEvent('Crm.navigate_to_contact', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.navigate_to_contact', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // search_contacts
    // -------------------------------------------------------------------------

    public function testSearchContactsReturnsArray(): void
    {
        $event = $this->makeEvent('Crm.search_contacts', []);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.search_contacts', []);

        $this->assertIsArray($event->getResult());
    }

    public function testSearchContactsWithSearchTerm(): void
    {
        $args = ['search' => 'Arhim', 'kind' => 'C'];
        $event = $this->makeEvent('Crm.search_contacts', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.search_contacts', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(COMPANY_FIRST, $result[0]->id);
    }

    // -------------------------------------------------------------------------
    // get_contact
    // -------------------------------------------------------------------------

    public function testGetContactFound(): void
    {
        $args = ['id' => COMPANY_FIRST];
        $event = $this->makeEvent('Crm.get_contact', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.get_contact', $args);

        $contact = $event->getResult();
        $this->assertNotNull($contact);
        $this->assertEquals(COMPANY_FIRST, $contact->id);
    }

    public function testGetContactNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Crm.get_contact', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.get_contact', $args);

        $this->assertNull($event->getResult());
    }

    // -------------------------------------------------------------------------
    // get_contact_logs
    // -------------------------------------------------------------------------

    public function testGetContactLogsRequiresAccessibleContact(): void
    {
        $args = ['contact_id' => COMPANY_FIRST];
        $event = $this->makeEvent('Crm.get_contact_logs', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.get_contact_logs', $args);

        $this->assertIsArray($event->getResult());
    }

    public function testGetContactLogsNotFound(): void
    {
        $args = ['contact_id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Crm.get_contact_logs', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.get_contact_logs', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // add_contact_log
    // -------------------------------------------------------------------------

    public function testAddContactLogCreatesEntry(): void
    {
        $args = ['contact_id' => COMPANY_FIRST, 'descript' => 'Test log entry', 'kind' => 'N'];
        $event = $this->makeEvent('Crm.add_contact_log', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.add_contact_log', $args);

        $log = $event->getResult();
        $this->assertNotNull($log);
        $this->assertIsNotArray($log);
        $this->assertEquals(COMPANY_FIRST, $log->contact_id);
        $this->assertEquals('Test log entry', $log->descript);
    }

    public function testAddContactLogContactNotFound(): void
    {
        $args = ['contact_id' => '00000000-0000-0000-0000-000000000000', 'descript' => 'x'];
        $event = $this->makeEvent('Crm.add_contact_log', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.add_contact_log', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // create_contact
    // -------------------------------------------------------------------------

    public function testCreateContactCompany(): void
    {
        $args = ['kind' => 'C', 'title' => 'New Test Company', 'tax_no' => 'SI12345678'];
        $event = $this->makeEvent('Crm.create_contact', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.create_contact', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('New Test Company', $result['title']);
    }

    public function testCreateContactPerson(): void
    {
        $args = ['kind' => 'T', 'name' => 'Jane', 'surname' => 'Doe', 'email' => 'jane@example.com'];
        $event = $this->makeEvent('Crm.create_contact', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.create_contact', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertStringContainsString('Doe', $result['title']);
    }

    // -------------------------------------------------------------------------
    // update_contact
    // -------------------------------------------------------------------------

    public function testUpdateContactNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000', 'descript' => 'x'];
        $event = $this->makeEvent('Crm.update_contact', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.update_contact', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testUpdateContactUpdatesFields(): void
    {
        $args = ['id' => COMPANY_FIRST, 'descript' => 'Updated description'];
        $event = $this->makeEvent('Crm.update_contact', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.update_contact', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(COMPANY_FIRST, $result['id']);
    }

    // -------------------------------------------------------------------------
    // Phone operations
    // -------------------------------------------------------------------------

    public function testAddContactPhone(): void
    {
        $args = ['contact_id' => COMPANY_FIRST, 'no' => '040 111 222', 'kind' => 'M'];
        $event = $this->makeEvent('Crm.add_contact_phone', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.add_contact_phone', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('040 111 222', $result['no']);
    }

    public function testEditContactPhone(): void
    {
        $args = ['id' => '1', 'no' => '041 000 000'];
        $event = $this->makeEvent('Crm.edit_contact_phone', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.edit_contact_phone', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testDeleteContactPhone(): void
    {
        $args = ['id' => '1'];
        $event = $this->makeEvent('Crm.delete_contact_phone', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.delete_contact_phone', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function testDeleteContactPhoneNotFound(): void
    {
        $args = ['id' => '999999'];
        $event = $this->makeEvent('Crm.delete_contact_phone', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.delete_contact_phone', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // Email operations
    // -------------------------------------------------------------------------

    public function testAddContactEmail(): void
    {
        $args = ['contact_id' => COMPANY_FIRST, 'email' => 'new@example.com', 'kind' => 'W'];
        $event = $this->makeEvent('Crm.add_contact_email', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.add_contact_email', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('new@example.com', $result['email']);
    }

    public function testEditContactEmail(): void
    {
        $args = ['id' => '1', 'email' => 'updated@example.com'];
        $event = $this->makeEvent('Crm.edit_contact_email', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.edit_contact_email', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testDeleteContactEmail(): void
    {
        $args = ['id' => '1'];
        $event = $this->makeEvent('Crm.delete_contact_email', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.delete_contact_email', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    // -------------------------------------------------------------------------
    // Address operations
    // -------------------------------------------------------------------------

    public function testAddContactAddress(): void
    {
        $args = ['contact_id' => COMPANY_FIRST, 'street' => 'Test St 1', 'city' => 'Ljubljana', 'zip' => '1000'];
        $event = $this->makeEvent('Crm.add_contact_address', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.add_contact_address', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testEditContactAddress(): void
    {
        $args = ['id' => '49a90cfe-fda4-49ca-b7ed-ca50783b5a41', 'city' => 'Maribor'];
        $event = $this->makeEvent('Crm.edit_contact_address', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.edit_contact_address', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testDeleteContactAddress(): void
    {
        $args = ['id' => '49a90cfe-fda4-49ca-b7ed-ca50783b5a41'];
        $event = $this->makeEvent('Crm.delete_contact_address', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.delete_contact_address', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    // -------------------------------------------------------------------------
    // Account operations
    // -------------------------------------------------------------------------

    public function testAddContactAccount(): void
    {
        $args = ['contact_id' => COMPANY_FIRST, 'iban' => 'SI56 1234 5678 9012 345', 'bic' => 'BACXSI22'];
        $event = $this->makeEvent('Crm.add_contact_account', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.add_contact_account', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testEditContactAccount(): void
    {
        $args = ['id' => '1', 'bic' => 'UPDTSI22'];
        $event = $this->makeEvent('Crm.edit_contact_account', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.edit_contact_account', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testDeleteContactAccount(): void
    {
        $args = ['id' => '1'];
        $event = $this->makeEvent('Crm.delete_contact_account', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.delete_contact_account', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    // -------------------------------------------------------------------------
    // Unknown tool
    // -------------------------------------------------------------------------

    public function testUnknownToolDoesNothing(): void
    {
        $event = $this->makeEvent('Crm.nonexistent_tool', []);
        $this->listener->aiAssistantExecuteTool($event, 'Crm.nonexistent_tool', []);

        $this->assertNull($event->getResult());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEvent(string $tool, array $arguments): Event
    {
        return new Event('App.AIAssistant.executeTool', null, [$tool, $arguments, $this->user]);
    }
}
