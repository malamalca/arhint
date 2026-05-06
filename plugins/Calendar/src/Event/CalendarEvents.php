<?php
declare(strict_types=1);

namespace Calendar\Event;

use App\Lib\AITool;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Calendar\Lib\CalendarSidebar;

class CalendarEvents implements EventListenerInterface
{
    /**
     * List of implemented events
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'View.beforeRender' => 'addScripts',
            'App.Sidebar.beforeRender' => 'modifySidebar',
            'App.AIAssistant.registerModule' => 'aiAssistantRegisterModule',
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    /**
     * Add plugins css file to global layout.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function addScripts(Event $event): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();
        $view->append('script');
        if ($view->getRequest()->is('mobile')) {
            echo $view->Html->css('Calendar.calendar_mobile');
        } else {
            echo $view->Html->css('Calendar.calendar');
        }
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Calendar') {
            $view->set('admin_title', __d('calendar', 'Calendar'));
        }
    }

    /**
     * Add Calendar items to sidebar
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $sidebar Sidebar array;
     * @return void
     */
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        CalendarSidebar::setAdminSidebar($event, $sidebar);
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
        $modulesList['Calendar'] = 'Calendar tools for managing events and appointments.';
    }

    /**
     * Add AI assistant tools
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $toolsList List of tools
     * @return void
     */
    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        $toolsList->append(new AITool(
            name: 'Calendar.get_events',
            arguments: [
                'from' => [
                    'type' => 'string',
                    'description' => 'Optional. Start date filter in YYYY-MM-DD format. ' .
                        'Only events ending on or after this date are returned.',
                ],
                'to' => [
                    'type' => 'string',
                    'description' => 'Optional. End date filter in YYYY-MM-DD format. ' .
                        'Only events starting on or before this date are returned.',
                ],
            ],
            description: 'Retrieves a list of calendar events within an optional date range. ' .
                'When printing out the events, please format them as a list with each event on a new line, ' .
                'including the event title and date.',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.get_event',
            arguments: [
                'id' => [
                    'type' => 'string',
                    'description' => 'Event UUID of the event to retrieve.',
                ],
            ],
            description: 'Retrieves a single calendar event by its UUID.',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.save_event',
            arguments: [
                'id' => [
                    'type' => 'string',
                    'description' => 'Event UUID to update. Omit or leave empty to create a new event.',
                ],
                'title' => ['type' => 'string', 'description' => 'Event title (required for new events).'],
                'dat_start' => [
                    'type' => 'string',
                    'description' => 'Start date/time in YYYY-MM-DD HH:MM:SS format (required for new events).',
                ],
                'dat_end' => [
                    'type' => 'string',
                    'description' => 'End date/time in YYYY-MM-DD HH:MM:SS format (required for new events).',
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
                    'description' => 'Optional event description/notes.',
                ],
                'reminder' => [
                    'type' => 'integer',
                    'description' => 'Optional reminder in minutes before the event.',
                ],
            ],
            description: 'Creates a new calendar event or updates an existing one. ' .
                'Returns the saved event or a list of validation errors.',
        ));

        $toolsList->append(new AITool(
            name: 'Calendar.delete_event',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the event to delete.'],
            ],
            description: 'Deletes a calendar event by its ID. ' .
                'Only events belonging to the current user can be deleted.',
        ));
    }

    /**
     * Execute AI assistant tool
     *
     * @param \Cake\Event\Event $event Event object
     * @param string $tool Tool name
     * @param array<mixed> $arguments Tool arguments
     * @return void
     */
    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        $currentUser = $event->getData()[2] ?? null;

        switch ($tool) {
            case 'Calendar.get_events':
                /** @var \Calendar\Model\Table\EventsTable $calendarTable */
                $calendarTable = TableRegistry::getTableLocator()->get('Calendar.Events');

                $filter = ['calendar' => $currentUser->get('id')];
                if (!empty($arguments['from'])) {
                    $filter['from'] = $arguments['from'];
                }
                if (!empty($arguments['to'])) {
                    $filter['to'] = $arguments['to'];
                }
                $params = $calendarTable->filter($filter);

                $events = $currentUser->applyScope('index', $calendarTable->find())
                    ->select()
                    ->where($params['conditions'])
                    ->all()
                    ->toArray();

                $event->setResult($events);
                break;

            case 'Calendar.get_event':
                /** @var \Calendar\Model\Table\EventsTable $calendarTable */
                $calendarTable = TableRegistry::getTableLocator()->get('Calendar.Events');

                if (empty($arguments['id'])) {
                    $event->setResult(['error' => 'Event ID is required.']);
                    break;
                }

                $entity = $calendarTable->find()
                    ->where(['Events.id' => $arguments['id']])
                    ->first();

                if (!$entity) {
                    $event->setResult(['error' => 'Event not found or access denied.']);
                    break;
                }

                if (!$currentUser->can('view', $entity)) {
                    $event->setResult(['error' => 'You are not authorized to view this event.']);
                    break;
                }

                $event->setResult($entity);
                break;

            case 'Calendar.save_event':
                /** @var \Calendar\Model\Table\EventsTable $calendarTable */
                $calendarTable = TableRegistry::getTableLocator()->get('Calendar.Events');

                if (!empty($arguments['id'])) {
                    $entity = $calendarTable->find()
                        ->where([
                            'Events.id' => $arguments['id'],
                            'Events.calendar_id' => $currentUser->get('id'),
                        ])
                        ->first();
                    if (!$entity) {
                        $event->setResult(['error' => 'Event not found or access denied.']);
                        break;
                    }
                    if (!$currentUser->can('edit', $entity)) {
                        $event->setResult(['error' => 'You are not authorized to edit this event.']);
                        break;
                    }
                    $entity = $calendarTable->patchEntity($entity, $arguments);
                } else {
                    $data = $arguments;
                    $data['calendar_id'] = $currentUser->get('id');
                    $data['all_day'] = !empty($arguments['all_day']) ? 1 : 0;
                    $entity = $calendarTable->newEntity($data);
                    if (!$currentUser->can('edit', $entity)) {
                        $event->setResult(['error' => 'You are not authorized to create events.']);
                        break;
                    }
                }

                if ($entity->hasErrors()) {
                    $event->setResult(['success' => false, 'errors' => $entity->getErrors()]);
                    break;
                }

                if ($calendarTable->save($entity)) {
                    $event->setResult(['success' => true, 'id' => $entity->id, 'title' => $entity->get('title')]);
                } else {
                    $event->setResult(['success' => false, 'errors' => $entity->getErrors()]);
                }
                break;

            case 'Calendar.delete_event':
                if (empty($arguments['id'])) {
                    $event->setResult(['error' => 'Event ID is required.']);
                    break;
                }

                $calendarTable = TableRegistry::getTableLocator()->get('Calendar.Events');
                $entity = $calendarTable->find()
                    ->where(['Events.id' => $arguments['id'], 'Events.calendar_id' => $currentUser->get('id')])
                    ->first();

                if (!$entity) {
                    $event->setResult(['error' => 'Event not found or access denied.']);
                    break;
                }

                if (!$currentUser->can('delete', $entity)) {
                    $event->setResult(['error' => 'You are not authorized to delete this event.']);
                    break;
                }

                if ($calendarTable->delete($entity)) {
                    $event->setResult(['success' => true]);
                } else {
                    $event->setResult(['success' => false, 'error' => 'Failed to delete the event.']);
                }
                break;
        }
    }
}
