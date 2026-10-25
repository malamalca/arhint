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
use Projects\Lib\AuthorityCalendar;
use Throwable;

class AuthorityCalendarEvents implements EventListenerInterface
{
    /**
     * @return array<string, string>
     */
    public function implementedEvents(): array
    {
        return [
            'Calendar.Events.beforeRead' => 'refresh',
            'Calendar.Events.editSource' => 'editSource',
            'Model.beforeSave' => 'protect',
            'Model.beforeDelete' => 'protect',
            'Model.afterSave' => 'synchronize',
            'Model.afterDelete' => 'synchronize',
        ];
    }

    /**
     * @param \Cake\Event\Event $event Calendar event.
     * @param string $eventId Calendar entry ID.
     * @return void
     */
    public function editSource(Event $event, string $eventId): void
    {
        $link = TableRegistry::getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks')->find()
            ->where(['event_id' => $eventId])->first();

        // Other sources of managed entries answer the same event, so only a match gives a result.
        if ($link) {
            $event->setResult([
                'plugin' => 'Projects', 'controller' => 'ProjectsAuthorities', 'action' => 'view', $link->case_id,
            ]);
        }
    }

    /**
     * Refresh titles and remove inaccessible subscriptions before calendar reads.
     * A failed refresh is logged and never prevents the calendar from opening.
     *
     * @param \Cake\Event\Event $event Calendar event.
     * @param string $userId Current user ID.
     * @return void
     */
    public function refresh(Event $event, string $userId): void
    {
        try {
            (new AuthorityCalendar())->synchronize(['calendar_id' => $userId]);
        } catch (Throwable $exception) {
            Log::error('Authority calendar refresh failed: ' . $exception->getMessage());
        }
    }

    /**
     * Managed entries can only change through their source, including DAV and AI writes.
     *
     * @param \Cake\Event\Event $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity Calendar entry.
     * @param \ArrayObject $options Save/delete options.
     * @return void
     */
    public function protect(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        $table = $event->getSubject();
        if (
            !$table instanceof EventsTable ||
            !empty($options['authorityCalendarSync'])
        ) {
            return;
        }
        if (
            $entity->id && TableRegistry::getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks')
            ->exists(['event_id' => $entity->id])
        ) {
            $entity->setError('title', __d('projects', 'Edit this deadline in the authority procedure.'));

            $event->stopPropagation();
            $event->setResult(false);
        }
    }

    /**
     * Source writes participate in the existing model transaction.
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
        $alias = $table->getRegistryAlias();
        if ($alias === 'Projects.ProjectsAuthorityEvents') {
            (new AuthorityCalendar())->synchronize(['case_id' => $entity->case_id]);
        } elseif ($alias === 'Projects.ProjectsAuthorityCases') {
            (new AuthorityCalendar())->synchronize(['case_id' => $entity->id]);
        } elseif ($alias === 'Projects.Projects') {
            // Only subscriptions to cases of the saved project can be affected.
            (new AuthorityCalendar())->synchronize([
                'case_id IN' => TableRegistry::getTableLocator()->get('Projects.ProjectsAuthorityCases')->find()
                    ->select(['id'])->where(['project_id' => $entity->id]),
            ]);
        } elseif (in_array($alias, ['Projects.ProjectsUsers', 'App.Users', 'Users'], true)) {
            (new AuthorityCalendar())->synchronize([
                'calendar_id' => $alias === 'Projects.ProjectsUsers' ? $entity->user_id : $entity->id,
            ]);
        }
    }
}
