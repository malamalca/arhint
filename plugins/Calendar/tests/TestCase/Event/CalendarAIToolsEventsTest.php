<?php
declare(strict_types=1);

namespace Calendar\Test\TestCase\Event;

use App\Model\Entity\User;
use ArrayObject;
use Authorization\AuthorizationServiceInterface;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Calendar\Event\CalendarAIToolsEvents;

/**
 * Calendar\Event\CalendarAIToolsEvents Test Case
 */
class CalendarAIToolsEventsTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'plugin.Calendar.Events',
    ];

    protected CalendarAIToolsEvents $listener;
    protected User $user;

    /** Fixture event ID */
    private const EVENT_ID = '185383a4-38c8-4194-9516-52c9069bc3bf';

    public function setUp(): void
    {
        parent::setUp();
        Router::reload();
        Router::createRouteBuilder('/')->scope('/calendar', ['plugin' => 'Calendar'], function ($routes): void {
            $routes->fallbacks(DashedRoute::class);
        });
        $this->listener = new CalendarAIToolsEvents();

        $authService = $this->createMock(AuthorizationServiceInterface::class);
        $authService->method('applyScope')
            ->willReturnCallback(fn($identity, $action, $resource) => $resource);
        $authService->method('can')
            ->willReturn(true);

        // @phpstan-ignore-next-line
        $this->user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        // @phpstan-ignore-next-line
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
    // aiAssistantRegisterModule
    // -------------------------------------------------------------------------

    public function testAiAssistantRegisterModule(): void
    {
        $event = new Event('App.AIAssistant.registerModule');
        $modulesList = new ArrayObject();
        $this->listener->aiAssistantRegisterModule($event, $modulesList);

        $this->assertArrayHasKey('Calendar', $modulesList);
        $this->assertStringContainsString('Calendar', (string)$modulesList['Calendar']);
    }

    // -------------------------------------------------------------------------
    // aiAssistantTools — tool registration
    // -------------------------------------------------------------------------

    public function testAiAssistantToolsRegisters7Tools(): void
    {
        $event = new Event('App.AIAssistant.tools');
        $toolsList = new ArrayObject();
        $this->listener->aiAssistantTools($event, $toolsList);

        $this->assertCount(7, $toolsList);

        $names = array_map(fn($t) => $t->name, iterator_to_array($toolsList));
        $this->assertContains('Calendar.search_events', $names);
        $this->assertContains('Calendar.get_event', $names);
        $this->assertContains('Calendar.create_event', $names);
        $this->assertContains('Calendar.update_event', $names);
        $this->assertContains('Calendar.delete_event', $names);
        $this->assertContains('Calendar.get_upcoming_events', $names);
        $this->assertContains('Calendar.get_events_for_date', $names);
    }

    // -------------------------------------------------------------------------
    // search_events
    // -------------------------------------------------------------------------

    public function testSearchEventsReturnsArray(): void
    {
        $event = $this->makeEvent('Calendar.search_events', []);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.search_events', []);

        $result = $event->getResult();
        $this->assertIsArray($result);
    }

    public function testSearchEventsReturnsFixtureEvent(): void
    {
        $args = ['from' => '2022-01-01', 'to' => '2022-12-31'];
        $event = $this->makeEvent('Calendar.search_events', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.search_events', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(self::EVENT_ID, $result[0]->id);
        $this->assertEquals('Meeting About Arhint', $result[0]->title);
    }

    public function testSearchEventsFilterByTitle(): void
    {
        $args = ['from' => '2022-01-01', 'to' => '2022-12-31', 'title' => 'Meeting'];
        $event = $this->makeEvent('Calendar.search_events', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.search_events', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testSearchEventsFilterByLocation(): void
    {
        $args = ['from' => '2022-01-01', 'to' => '2022-12-31', 'location' => 'Ljubljana'];
        $event = $this->makeEvent('Calendar.search_events', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.search_events', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testSearchEventsFilterByAllDay(): void
    {
        $args = ['from' => '2022-01-01', 'to' => '2022-12-31', 'all_day' => false];
        $event = $this->makeEvent('Calendar.search_events', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.search_events', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testSearchEventsHasViewUrl(): void
    {
        $args = ['from' => '2022-01-01', 'to' => '2022-12-31'];
        $event = $this->makeEvent('Calendar.search_events', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.search_events', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(property_exists($result[0], 'view_url') || isset($result[0]->view_url));
    }

    // -------------------------------------------------------------------------
    // get_event
    // -------------------------------------------------------------------------

    public function testGetEventFound(): void
    {
        $args = ['id' => self::EVENT_ID];
        $event = $this->makeEvent('Calendar.get_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_event', $args);

        $result = $event->getResult();
        $this->assertNotNull($result);
        $this->assertIsNotArray($result);
        $this->assertEquals(self::EVENT_ID, $result->id);
        $this->assertEquals('Meeting About Arhint', $result->title);
    }

    public function testGetEventNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Calendar.get_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetEventMissingId(): void
    {
        $args = [];
        $event = $this->makeEvent('Calendar.get_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetEventHasViewUrl(): void
    {
        $args = ['id' => self::EVENT_ID];
        $event = $this->makeEvent('Calendar.get_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_event', $args);

        $result = $event->getResult();
        $this->assertTrue(property_exists($result, 'view_url') || isset($result->view_url));
    }

    // -------------------------------------------------------------------------
    // create_event
    // -------------------------------------------------------------------------

    public function testCreateEvent(): void
    {
        $args = [
            'title' => 'Test Event',
            'dat_start' => '2025-06-15 10:00:00',
            'dat_end' => '2025-06-15 11:00:00',
        ];
        $event = $this->makeEvent('Calendar.create_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.create_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Test Event', $result['title']);
        $this->assertArrayHasKey('view_url', $result);

        // Verify it was persisted
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $saved = $eventsTable->get($result['id']);
        $this->assertEquals('Test Event', $saved->title);
    }

    public function testCreateEventAllDayWithDatetime(): void
    {
        $args = [
            'title' => 'All Day Event',
            'dat_start' => '2025-06-15 09:00:00',
            'dat_end' => '2025-06-15 17:00:00',
            'all_day' => true,
        ];
        $event = $this->makeEvent('Calendar.create_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.create_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('All Day Event', $result['title']);
    }

    public function testCreateEventWithLocationAndBody(): void
    {
        $args = [
            'title' => 'Full Event',
            'dat_start' => '2025-06-15 14:00:00',
            'dat_end' => '2025-06-15 15:00:00',
            'location' => 'Conference Room A',
            'body' => '<p>Meeting notes</p>',
            'reminder' => 30,
        ];
        $event = $this->makeEvent('Calendar.create_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.create_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);

        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $saved = $eventsTable->get($result['id']);
        $this->assertEquals('Conference Room A', $saved->location);
        $this->assertEquals('<p>Meeting notes</p>', $saved->body);
        $this->assertEquals(30, $saved->reminder);
    }

    // -------------------------------------------------------------------------
    // update_event
    // -------------------------------------------------------------------------

    public function testUpdateEventFound(): void
    {
        $args = [
            'id' => self::EVENT_ID,
            'title' => 'Updated Title',
        ];
        $event = $this->makeEvent('Calendar.update_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.update_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Updated Title', $result['title']);
        $this->assertArrayHasKey('view_url', $result);

        // Verify persistence
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $saved = $eventsTable->get($result['id']);
        $this->assertEquals('Updated Title', $saved->title);
    }

    public function testUpdateEventNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000', 'title' => 'Nope'];
        $event = $this->makeEvent('Calendar.update_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.update_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testUpdateEventMissingId(): void
    {
        $args = ['title' => 'Nope'];
        $event = $this->makeEvent('Calendar.update_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.update_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testUpdateEventPartialFields(): void
    {
        $args = [
            'id' => self::EVENT_ID,
            'location' => 'New Location',
        ];
        $event = $this->makeEvent('Calendar.update_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.update_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);

        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $saved = $eventsTable->get($result['id']);
        $this->assertEquals('New Location', $saved->location);
    }

    // -------------------------------------------------------------------------
    // delete_event
    // -------------------------------------------------------------------------

    public function testDeleteEvent(): void
    {
        // Create an event first to delete
        $createArgs = [
            'title' => 'To Delete',
            'dat_start' => '2025-06-15 10:00:00',
            'dat_end' => '2025-06-15 11:00:00',
        ];
        $createEvent = $this->makeEvent('Calendar.create_event', $createArgs);
        $this->listener->aiAssistantExecuteTool($createEvent, 'Calendar.create_event', $createArgs);

        $createdResult = $createEvent->getResult();
        $eventId = $createdResult['id'];

        // Now delete it
        $args = ['id' => $eventId];
        $event = $this->makeEvent('Calendar.delete_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.delete_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        // Verify it's gone
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $this->assertFalse($eventsTable->exists(['id' => $eventId]));
    }

    public function testDeleteEventNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Calendar.delete_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.delete_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testDeleteEventMissingId(): void
    {
        $args = [];
        $event = $this->makeEvent('Calendar.delete_event', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.delete_event', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // get_upcoming_events
    // -------------------------------------------------------------------------

    public function testGetUpcomingEventsReturnsArray(): void
    {
        $event = $this->makeEvent('Calendar.get_upcoming_events', []);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_upcoming_events', []);

        $result = $event->getResult();
        $this->assertIsArray($result);
    }

    public function testGetUpcomingEventsWithDays(): void
    {
        $args = ['days' => 30];
        $event = $this->makeEvent('Calendar.get_upcoming_events', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_upcoming_events', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
    }

    public function testGetUpcomingEventsDefaultDays(): void
    {
        $event = $this->makeEvent('Calendar.get_upcoming_events', []);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_upcoming_events', []);

        // Should not throw and returns array (likely empty since fixture event is in past)
        $result = $event->getResult();
        $this->assertIsArray($result);
    }

    public function testGetUpcomingEventsHasViewUrls(): void
    {
        // Create a future event first
        $futureDate = date('Y-m-d', strtotime('+5 days'));
        $createArgs = [
            'title' => 'Future Event',
            'dat_start' => $futureDate . ' 10:00:00',
            'dat_end' => $futureDate . ' 11:00:00',
        ];
        $createEvent = $this->makeEvent('Calendar.create_event', $createArgs);
        $this->listener->aiAssistantExecuteTool($createEvent, 'Calendar.create_event', $createArgs);

        $createdResult = $createEvent->getResult();
        $this->assertArrayHasKey('id', $createdResult);

        $args = ['days' => 7];
        $event = $this->makeEvent('Calendar.get_upcoming_events', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_upcoming_events', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        // Result should contain the created event with a view_url
        foreach ($result as $ev) {
            if (isset($ev->view_url)) {
                $this->assertStringContainsString('/calendar/events/view/', (string)$ev->view_url);

                return;
            }
        }
        // If no results have view_url, at least verify the structure
        if (!empty($result)) {
            $this->assertTrue(property_exists($result[0], 'view_url') || isset($result[0]->view_url));
        }
    }

    // -------------------------------------------------------------------------
    // get_events_for_date
    // -------------------------------------------------------------------------

    public function testGetEventsForDateReturnsArray(): void
    {
        $args = ['date' => '2022-01-27'];
        $event = $this->makeEvent('Calendar.get_events_for_date', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_events_for_date', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
    }

    public function testGetEventsForDateFindsFixtureEvent(): void
    {
        $args = ['date' => '2022-01-27'];
        $event = $this->makeEvent('Calendar.get_events_for_date', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_events_for_date', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(self::EVENT_ID, $result[0]->id);
    }

    public function testGetEventsForDateEmpty(): void
    {
        $args = ['date' => '2099-01-01'];
        $event = $this->makeEvent('Calendar.get_events_for_date', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_events_for_date', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testGetEventsForDateMissingDate(): void
    {
        $args = [];
        $event = $this->makeEvent('Calendar.get_events_for_date', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_events_for_date', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetEventsForDateHasViewUrls(): void
    {
        $args = ['date' => '2022-01-27'];
        $event = $this->makeEvent('Calendar.get_events_for_date', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.get_events_for_date', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(property_exists($result[0], 'view_url') || isset($result[0]->view_url));
    }

    // -------------------------------------------------------------------------
    // Unknown tool
    // -------------------------------------------------------------------------

    public function testUnknownToolDoesNothing(): void
    {
        $event = $this->makeEvent('Calendar.nonexistent_tool', []);
        $this->listener->aiAssistantExecuteTool($event, 'Calendar.nonexistent_tool', []);

        $this->assertNull($event->getResult());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $arguments
     */
    private function makeEvent(string $tool, array $arguments): Event
    {
        return new Event('App.AIAssistant.executeTool', null, [$tool, $arguments, $this->user]);
    }
}
