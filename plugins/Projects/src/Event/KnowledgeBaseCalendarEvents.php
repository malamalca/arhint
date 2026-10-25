<?php
declare(strict_types=1);

namespace Projects\Event;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Calendar\Model\Table\EventsTable;
use Projects\Lib\KnowledgeBase\KbCalendar;
use Projects\Lib\KnowledgeBase\KbDocument;
use Throwable;

/**
 * Keeps the calendar entries of knowledge base deadlines in line with the knowledge base and with access to it.
 */
class KnowledgeBaseCalendarEvents implements EventListenerInterface
{
    /**
     * @return array<string, string>
     */
    public function implementedEvents(): array
    {
        return [
            'Projects.KnowledgeBase.afterSave' => 'knowledgeBaseSaved',
            'Calendar.Events.beforeRead' => 'refresh',
            'Calendar.Events.editSource' => 'editSource',
            'Model.beforeSave' => 'protect',
            'Model.beforeDelete' => 'protect',
            'Model.afterSave' => 'synchronize',
            'Model.afterDelete' => 'synchronize',
        ];
    }

    /**
     * The knowledge base of a project was written: update the entries of its deadlines.
     *
     * A failure is logged and never prevents the knowledge base from being saved.
     *
     * @param \Cake\Event\Event $event Event with `project_id`.
     * @return void
     */
    public function knowledgeBaseSaved(Event $event): void
    {
        $this->safely(fn() => (new KbCalendar())->synchronize(['project_id' => $event->getData('project_id')]));
    }

    /**
     * Refresh the entries and remove those of lost access before the calendar is read.
     *
     * @param \Cake\Event\Event $event Calendar event.
     * @param string $userId Current user ID.
     * @return void
     */
    public function refresh(Event $event, string $userId): void
    {
        $this->safely(fn() => (new KbCalendar())->synchronize(['calendar_id' => $userId]));
    }

    /**
     * Editing the entry opens the knowledge base of the project.
     *
     * @param \Cake\Event\Event $event Calendar event.
     * @param string $eventId Calendar entry ID.
     * @return void
     */
    public function editSource(Event $event, string $eventId): void
    {
        $link = TableRegistry::getTableLocator()->get('Projects.ProjectsKbCalendarLinks')->find()
            ->where(['event_id' => $eventId])->first();

        // Other sources of managed entries answer the same event, so only a match gives a result.
        if ($link) {
            $event->setResult([
                'plugin' => 'Projects', 'controller' => 'Projects', 'action' => 'view', $link->project_id,
                '#' => rtrim(KbDocument::ANCHOR_PREFIX, '-') . '-roki',
            ]);
        }
    }

    /**
     * Managed entries change only through the knowledge base, including DAV and AI writes.
     *
     * @param \Cake\Event\Event $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity Calendar entry.
     * @param \ArrayObject $options Save/delete options.
     * @return void
     */
    public function protect(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$event->getSubject() instanceof EventsTable || !empty($options['kbCalendarSync'])) {
            return;
        }

        if (
            $entity->id && TableRegistry::getTableLocator()->get('Projects.ProjectsKbCalendarLinks')
                ->exists(['event_id' => $entity->id])
        ) {
            $entity->setError('title', __d('projects', 'Edit this deadline in the knowledge base of the project.'));

            $event->stopPropagation();
            $event->setResult(false);
        }
    }

    /**
     * Access to the project may have changed with the project, its members or the user.
     *
     * @param \Cake\Event\Event $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity Saved/deleted entity.
     * @param \ArrayObject $options Save/delete options.
     * @return void
     */
    public function synchronize(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        $table = $event->getSubject();
        if (!$table instanceof Table) {
            return;
        }

        $conditions = match ($table->getRegistryAlias()) {
            'Projects.Projects' => ['project_id' => $entity->id],
            'Projects.ProjectsUsers' => ['calendar_id' => $entity->user_id],
            'App.Users', 'Users' => ['calendar_id' => $entity->id],
            default => null,
        };
        if ($conditions !== null) {
            $this->safely(fn() => (new KbCalendar())->synchronize($conditions));
        }
    }

    /**
     * @param callable $work Work that must not break the caller.
     * @return void
     */
    private function safely(callable $work): void
    {
        try {
            $work();
        } catch (Throwable $e) {
            Log::error('Knowledge base calendar sync failed: ' . get_class($e) . ': ' . $e->getMessage());
        }
    }
}
