<?php
declare(strict_types=1);

namespace Projects\Lib;

use App\AppPluginsEnum;
use App\Model\Entity\User;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Text;
use Projects\Model\Entity\Project;
use Projects\Policy\ProjectPolicy;

/**
 * One personal calendar subscription per authority case, updated from effective history.
 */
class AuthorityCalendar
{
    /**
     * @param string $caseId Case ID.
     * @param \App\Model\Entity\User $user Subscriber.
     * @return void
     */
    public function subscribe(string $caseId, User $user): void
    {
        $links = TableRegistry::getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks');
        $links->getConnection()->transactional(function () use ($links, $caseId, $user): void {
            $link = $links->find()->where(['case_id' => $caseId, 'calendar_id' => $user->id])->first();
            if (!$link) {
                $link = $links->newEmptyEntity();
                $link->id = Text::uuid();
                $link->case_id = $caseId;
                $link->calendar_id = $user->id;
                $links->saveOrFail($link);
            }
            $this->sync($link);
        });
    }

    /**
     * Removing the link also removes its managed calendar entry.
     *
     * @param string $caseId Case ID.
     * @param string $userId Subscriber ID.
     * @return void
     */
    public function unsubscribe(string $caseId, string $userId): void
    {
        $links = TableRegistry::getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks');
        $links->getConnection()->transactional(function () use ($links, $caseId, $userId): void {
            $link = $links->find()->where(['case_id' => $caseId, 'calendar_id' => $userId])->first();
            if ($link) {
                $this->removeEvent($link);
                $links->deleteOrFail($link);
            }
        });
    }

    /**
     * @param array<string, mixed> $conditions Subscription filter.
     * @return void
     */
    public function synchronize(array $conditions = []): void
    {
        $links = TableRegistry::getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks');
        // Most saves in the application concern nobody with a subscription: skip the transaction and locks.
        if (!$links->exists($conditions)) {
            return;
        }
        $links->getConnection()->transactional(function () use ($links, $conditions): void {
            $query = $links->find()->where($conditions);
            if (!$links->getConnection()->getDriver() instanceof Sqlite) {
                $query->epilog('FOR UPDATE');
            }
            foreach ($query->all() as $link) {
                $this->sync($link);
            }
        });
    }

    /**
     * @param \Cake\Datasource\EntityInterface $link Subscription.
     * @return void
     */
    private function sync(EntityInterface $link): void
    {
        $tables = TableRegistry::getTableLocator();
        $links = $tables->get('Projects.ProjectsAuthorityCalendarLinks');
        $case = $tables->get('Projects.ProjectsAuthorityCases')->find()->where(['id' => $link->case_id])->first();
        $user = $tables->get('App.Users')->find()->where(['id' => $link->calendar_id])->first();
        $project = $case ? $tables->get('Projects.Projects')->find()
            ->where(['id' => $case->project_id])->first() : null;
        if (
            !$case || !$user instanceof User || !$project instanceof Project || !$user->active ||
            !$user->hasAccess(AppPluginsEnum::Projects) || !$user->hasAccess(AppPluginsEnum::Calendar) ||
            !(new ProjectPolicy())->canAccess($project, $user)
        ) {
            $this->removeEvent($link);
            $links->deleteOrFail($link);

            return;
        }
        $history = $tables->get('Projects.ProjectsAuthorityEvents')->find()->where(['case_id' => $case->id])
            ->orderBy(['sequence' => 'ASC'])->all()->toList();
        $effective = (new AuthorityWorkflow())->effectiveEvents($history);
        $source = $effective ? $effective[array_key_last($effective)] : null;
        if (!$source || !$source->date_due) {
            $this->removeEvent($link);
            $links->saveOrFail($link);

            return;
        }
        $events = $tables->get('Calendar.Events');
        $entry = $link->event_id ? $events->find()->where([
            'id' => $link->event_id, 'calendar_id' => $link->calendar_id,
        ])->first() : null;
        $entry ??= $events->newEmptyEntity();
        $url = Router::url([
            'plugin' => 'Projects', 'controller' => 'ProjectsAuthorities', 'action' => 'view', $case->id,
        ], true);
        $date = $source->date_due->format('Y-m-d');
        $data = [
            'calendar_id' => $user->id,
            'title' => mb_substr(
                $project->getName() . ' · ' . $case->title . ' · ' . AuthorityWorkflow::kinds()[$source->kind],
                0,
                200,
            ),
            'body' => (string)$source->descript . "\n" . $url,
            'all_day' => true,
            'dat_start' => new DateTime($date . ' 00:00:00'),
            'dat_end' => new DateTime($date . ' 23:59:59'),
            'reminder' => 0,
        ];
        foreach ($data as $field => $value) {
            $entry->set($field, $value);
        }
        if ($entry->isNew() || $entry->isDirty()) {
            $events->saveOrFail($entry, ['authorityCalendarSync' => true]);
        }
        $link->event_id = $entry->id;
        $link->source_event_id = $source->id;
        if ($link->isDirty()) {
            $links->saveOrFail($link);
        }
    }

    /**
     * Only remove the native event belonging to this subscriber.
     *
     * @param \Cake\Datasource\EntityInterface $link Subscription.
     * @return void
     */
    private function removeEvent(EntityInterface $link): void
    {
        if ($link->event_id) {
            $events = TableRegistry::getTableLocator()->get('Calendar.Events');
            $entry = $events->find()->where(['id' => $link->event_id, 'calendar_id' => $link->calendar_id])->first();
            if ($entry) {
                $events->deleteOrFail($entry, ['authorityCalendarSync' => true]);
            }
        }
        $link->event_id = null;
        $link->source_event_id = null;
    }
}
