<?php
declare(strict_types=1);

namespace Calendar\Event;

use App\Lib\AITool;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;
use Exception;

class CalendarAIToolsEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'App.AIAssistant.registerModule' => 'aiAssistantRegisterModule',
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    /**
     * Register the Calendar module for AI assistant module detection.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $modulesList Modules list to append to.
     * @return void
     */
    public function aiAssistantRegisterModule(Event $event, ArrayObject $modulesList): void
    {
        $modulesList['Calendar'] = 'Calendar tools for searching, creating, updating, and deleting calendar events.';
    }

    /**
     * Add AI assistant tools.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $toolsList List of tools.
     * @return void
     */
    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        $toolsList->append(new AITool(
            name: 'Calendar.search_events',
            arguments: [
                'from' => [
                    'type' => 'string',
                    'description' => 'Start date filter in YYYY-MM-DD format. Only events ending on or after
                        this date are returned.',
                ],
                'to' => [
                    'type' => 'string',
                    'description' => 'End date filter in YYYY-MM-DD format. Only events starting on or before
                        this date are returned.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Search term to filter events by title (partial match).',
                ],
                'location' => [
                    'type' => 'string',
                    'description' => 'Search term to filter events by location (partial match).',
                ],
                'all_day' => [
                    'type' => 'boolean',
                    'description' => 'Filter by all-day events. True for all-day only, false for timed events only.',
                ],
            ],
            description: 'Searches or lists calendar events for the current user within an optional date range. '
                . 'Returns id, title, dat_start, dat_end, all_day, location, and reminder. '
                . 'Each result includes view_url; render title as [title](view_url).',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.get_event',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the calendar event to retrieve.'],
            ],
            description: 'Fetches full details of a single calendar event including title, date range, '
                . 'location, body, and reminder settings. Includes a view_url field; '
                . 'always render title as a markdown link: [title](view_url).',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.create_event',
            arguments: [
                'title' => ['type' => 'string', 'description' => 'Event title. Required.'],
                'dat_start' => [
                    'type' => 'string',
                    'description' => 'Start date/time in YYYY-MM-DD HH:MM:SS format or YYYY-MM-DD
                        for all-day events. Required.',
                ],
                'dat_end' => [
                    'type' => 'string',
                    'description' => 'End date/time in YYYY-MM-DD HH:MM:SS format or YYYY-MM-DD
                        for all-day events. Required.',
                ],
                'all_day' => [
                    'type' => 'boolean',
                    'description' => 'Whether the event is an all-day event. Defaults to false.',
                ],
                'location' => [
                    'type' => 'string',
                    'description' => 'Optional event location.',
                ],
                'body' => [
                    'type' => 'string',
                    'description' => 'Optional event description or notes (HTML allowed).',
                ],
                'reminder' => [
                    'type' => 'integer',
                    'description' => 'Optional reminder in minutes before the event.',
                ],
            ],
            description: 'Creates a new calendar event for the current user. Returns the created event id and title.',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.update_event',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the event to update. Required.'],
                'title' => ['type' => 'string', 'description' => 'Event title.'],
                'dat_start' => [
                    'type' => 'string',
                    'description' => 'Start date/time in YYYY-MM-DD HH:MM:SS format or YYYY-MM-DD for all-day events.',
                ],
                'dat_end' => [
                    'type' => 'string',
                    'description' => 'End date/time in YYYY-MM-DD HH:MM:SS format or YYYY-MM-DD for all-day events.',
                ],
                'all_day' => [
                    'type' => 'boolean',
                    'description' => 'Whether the event is an all-day event.',
                ],
                'location' => [
                    'type' => 'string',
                    'description' => 'Event location.',
                ],
                'body' => [
                    'type' => 'string',
                    'description' => 'Event description or notes (HTML allowed).',
                ],
                'reminder' => [
                    'type' => 'integer',
                    'description' => 'Reminder in minutes before the event.',
                ],
            ],
            description: 'Updates an existing calendar event. Only events belonging to the current
                user can be updated. Returns the updated event id and title.',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.delete_event',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the event to delete. Required.'],
            ],
            description: 'Deletes a calendar event by its UUID.
                Only events belonging to the current user can be deleted.',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.get_upcoming_events',
            arguments: [
                'days' => [
                    'type' => 'integer',
                    'description' => 'Number of days from today to look ahead. Defaults to 7.',
                ],
            ],
            description: 'Lists upcoming events for the current user within the specified number of days from today. '
                . 'Returns id, title, dat_start, dat_end, all_day, location, and reminder. '
                . 'Each result includes view_url; render title as [title](view_url).',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.get_events_for_date',
            arguments: [
                'date' => [
                    'type' => 'string',
                    'description' => 'Date in YYYY-MM-DD format to retrieve events for.',
                ],
            ],
            description: 'Lists all calendar events occurring on a specific date for the current user. '
                . 'Returns id, title, dat_start, dat_end, all_day, location, and reminder. '
                . 'Each result includes view_url; render title as [title](view_url).',
        ));
    }

    /**
     * Execute AI assistant tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param string $tool Tool name.
     * @param array<mixed> $arguments Tool arguments.
     * @return void
     */
    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        $currentUser = $event->getData()[2] ?? null;

        Log::debug(
            'Calendar tool executing: ' . $tool,
            [
                'scope' => ['ai'],
                'tool' => $tool,
                'arguments' => $arguments,
            ],
        );

        try {
            match ($tool) {
                'Calendar.search_events' => $this->executeSearchEvents($event, $arguments, $currentUser),
                'Calendar.get_event' => $this->executeGetEvent($event, $arguments, $currentUser),
                'Calendar.create_event' => $this->executeCreateEvent($event, $arguments, $currentUser),
                'Calendar.update_event' => $this->executeUpdateEvent($event, $arguments, $currentUser),
                'Calendar.delete_event' => $this->executeDeleteEvent($event, $arguments, $currentUser),
                'Calendar.get_upcoming_events' => $this->executeGetUpcomingEvents($event, $arguments, $currentUser),
                'Calendar.get_events_for_date' => $this->executeGetEventsForDate($event, $arguments, $currentUser),
                default => null,
            };
        } catch (Exception $e) {
            Log::error(
                'Calendar AI tool error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine(),
                [
                    'scope' => ['ai'],
                    'tool' => $tool,
                    'arguments' => $arguments,
                    'trace' => $e->getTraceAsString(),
                ],
            );

            $event->setResult(['error' => $e->getMessage()]);
        }
    }

    /**
     * Execute Calendar.search_events tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeSearchEvents(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Calendar\Model\Table\EventsTable $eventsTable */
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');

        $filter = ['calendar' => $currentUser->get('id')];
        if (!empty($arguments['from'])) {
            $filter['from'] = $arguments['from'];
        }
        if (!empty($arguments['to'])) {
            $filter['to'] = $arguments['to'];
        }

        $params = $eventsTable->filter($filter);

        // Add title and location filters
        if (!empty($arguments['title'])) {
            $params['conditions']['AND'][] = ['Events.title LIKE' => '%' . $arguments['title'] . '%'];
        }
        if (!empty($arguments['location'])) {
            $params['conditions']['AND'][] = ['Events.location LIKE' => '%' . $arguments['location'] . '%'];
        }

        // Add all_day filter
        if (array_key_exists('all_day', $arguments)) {
            $params['conditions']['AND'][] = ['Events.all_day' => (bool)$arguments['all_day']];
        }

        $events = $currentUser->applyScope('index', $eventsTable->find())
            ->select([
                'Events.id',
                'Events.title',
                'Events.dat_start',
                'Events.dat_end',
                'Events.all_day',
                'Events.location',
                'Events.reminder',
            ])
            ->where($params['conditions'])
            ->orderBy(['Events.dat_start' => 'ASC'])
            ->limit(50)
            ->all()
            ->toArray();

        foreach ($events as $ev) {
            $ev->view_url = $this->eventViewUrl((string)$ev->id);
        }

        $event->setResult($events);
    }

    /**
     * Execute Calendar.get_event tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetEvent(Event $event, array $arguments, mixed $currentUser): void
    {
        if (empty($arguments['id'])) {
            $event->setResult(['error' => 'Event ID is required.']);

            return;
        }

        /** @var \Calendar\Model\Table\EventsTable $eventsTable */
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');

        /** @var \Calendar\Model\Entity\Event|null $entity */
        $entity = $currentUser->applyScope('view', $eventsTable->find())
            ->where(['Events.id' => $arguments['id']])
            ->first();

        if (!$entity) {
            $event->setResult(['error' => 'Event not found.']);

            return;
        }

        $entity->view_url = $this->eventViewUrl((string)$entity->id);

        $event->setResult($entity);
    }

    /**
     * Execute Calendar.create_event tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeCreateEvent(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Calendar\Model\Table\EventsTable $eventsTable */
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');

        $data = [
            'calendar_id' => $currentUser->get('id'),
            'title' => $arguments['title'] ?? '',
            'dat_start' => $arguments['dat_start'] ?? null,
            'dat_end' => $arguments['dat_end'] ?? null,
            'all_day' => !empty($arguments['all_day']) ? 1 : 0,
            'location' => $arguments['location'] ?? null,
            'body' => $arguments['body'] ?? null,
            'reminder' => $arguments['reminder'] ?? null,
        ];

        /** @var \Calendar\Model\Entity\Event $entity */
        $entity = $eventsTable->newEntity($data);

        if (!$currentUser->can('edit', $entity)) {
            $event->setResult(['error' => 'You are not authorized to create events.']);

            return;
        }

        if ($entity->hasErrors()) {
            $event->setResult(['error' => 'Failed to create event.', 'errors' => $entity->getErrors()]);

            return;
        }

        if ($eventsTable->save($entity)) {
            $result = [
                'id' => $entity->id,
                'title' => $entity->title,
                'dat_start' => $entity->dat_start ? (string)$entity->dat_start : null,
                'dat_end' => $entity->dat_end ? (string)$entity->dat_end : null,
            ];
            $result['view_url'] = $this->eventViewUrl((string)$entity->id);
            $event->setResult($result);
        } else {
            $event->setResult(['error' => 'Failed to save event.']);
        }
    }

    /**
     * Execute Calendar.update_event tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeUpdateEvent(Event $event, array $arguments, mixed $currentUser): void
    {
        if (empty($arguments['id'])) {
            $event->setResult(['error' => 'Event ID is required.']);

            return;
        }

        /** @var \Calendar\Model\Table\EventsTable $eventsTable */
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');

        /** @var \Calendar\Model\Entity\Event|null $entity */
        $entity = $currentUser->applyScope('edit', $eventsTable->find())
            ->where(['Events.id' => $arguments['id']])
            ->first();

        if (!$entity) {
            $event->setResult(['error' => 'Event not found or access denied.']);

            return;
        }

        $updateData = array_intersect_key(
            $arguments,
            array_flip(['title', 'dat_start', 'dat_end', 'all_day', 'location', 'body', 'reminder']),
        );

        // Normalize all_day to integer for the entity
        if (array_key_exists('all_day', $updateData)) {
            $updateData['all_day'] = !empty($arguments['all_day']) ? 1 : 0;
        }

        $eventsTable->patchEntity($entity, $updateData);

        if ($entity->hasErrors()) {
            $event->setResult(['error' => 'Failed to update event.', 'errors' => $entity->getErrors()]);

            return;
        }

        if ($eventsTable->save($entity)) {
            $result = [
                'id' => $entity->id,
                'title' => $entity->title,
            ];
            $result['view_url'] = $this->eventViewUrl((string)$entity->id);
            $event->setResult($result);
        } else {
            $event->setResult(['error' => 'Failed to save event.']);
        }
    }

    /**
     * Execute Calendar.delete_event tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeDeleteEvent(Event $event, array $arguments, mixed $currentUser): void
    {
        if (empty($arguments['id'])) {
            $event->setResult(['error' => 'Event ID is required.']);

            return;
        }

        /** @var \Calendar\Model\Table\EventsTable $eventsTable */
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');

        /** @var \Calendar\Model\Entity\Event|null $entity */
        $entity = $currentUser->applyScope('delete', $eventsTable->find())
            ->where(['Events.id' => $arguments['id']])
            ->first();

        if (!$entity) {
            $event->setResult(['error' => 'Event not found or access denied.']);

            return;
        }

        if ($eventsTable->delete($entity)) {
            $event->setResult(['success' => true, 'message' => 'Event deleted successfully.']);
        } else {
            $event->setResult(['error' => 'Failed to delete the event.']);
        }
    }

    /**
     * Execute Calendar.get_upcoming_events tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetUpcomingEvents(Event $event, array $arguments, mixed $currentUser): void
    {
        $days = (int)($arguments['days'] ?? 7);
        if ($days < 1) {
            $days = 7;
        }

        /** @var \Calendar\Model\Table\EventsTable $eventsTable */
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');

        $today = Date::now();
        $endDate = $today->addDays($days);

        $filter = [
            'calendar' => $currentUser->get('id'),
            'from' => (string)$today,
            'to' => (string)$endDate,
        ];
        $params = $eventsTable->filter($filter);

        $events = $currentUser->applyScope('index', $eventsTable->find())
            ->select([
                'Events.id',
                'Events.title',
                'Events.dat_start',
                'Events.dat_end',
                'Events.all_day',
                'Events.location',
                'Events.reminder',
            ])
            ->where($params['conditions'])
            ->orderBy(['Events.dat_start' => 'ASC'])
            ->limit(50)
            ->all()
            ->toArray();

        foreach ($events as $ev) {
            $ev->view_url = $this->eventViewUrl((string)$ev->id);
        }

        $event->setResult($events);
    }

    /**
     * Execute Calendar.get_events_for_date tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetEventsForDate(Event $event, array $arguments, mixed $currentUser): void
    {
        if (empty($arguments['date'])) {
            $event->setResult(['error' => 'Date is required in YYYY-MM-DD format.']);

            return;
        }

        /** @var \Calendar\Model\Table\EventsTable $eventsTable */
        $eventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');

        $filter = [
            'calendar' => $currentUser->get('id'),
            'from' => $arguments['date'],
            'to' => $arguments['date'],
        ];
        $params = $eventsTable->filter($filter);

        $events = $currentUser->applyScope('index', $eventsTable->find())
            ->select([
                'Events.id',
                'Events.title',
                'Events.dat_start',
                'Events.dat_end',
                'Events.all_day',
                'Events.location',
                'Events.reminder',
            ])
            ->where($params['conditions'])
            ->orderBy(['Events.dat_start' => 'ASC'])
            ->limit(50)
            ->all()
            ->toArray();

        foreach ($events as $ev) {
            $ev->view_url = $this->eventViewUrl((string)$ev->id);
        }

        $event->setResult($events);
    }

    /**
     * Build an event view URL for AI responses.
     *
     * @param string $eventId Event UUID.
     * @return string
     */
    private function eventViewUrl(string $eventId): string
    {
        try {
            return Router::url([
                'plugin' => 'Calendar', 'controller' => 'Events', 'action' => 'view', $eventId,
            ], true);
        } catch (MissingRouteException) {
            return '/calendar/events/view/' . $eventId;
        }
    }
}
