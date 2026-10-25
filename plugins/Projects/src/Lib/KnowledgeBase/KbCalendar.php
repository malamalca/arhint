<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use App\AppPluginsEnum;
use App\Model\Entity\User;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Text;
use InvalidArgumentException;
use Projects\Model\Entity\Project;
use Projects\Model\Table\ProjectsKbCalendarLinksTable;
use Projects\Policy\ProjectPolicy;

/**
 * Personal calendar entries for the deadlines of the knowledge base, one per user and deadline.
 *
 * The user opts in for a deadline. The entry follows the deadline (date and text); when the deadline is done,
 * cancelled or gone, or when the user loses access to the project, the entry and the link are removed.
 */
class KbCalendar
{
    /**
     * @var array<string, \Projects\Lib\KnowledgeBase\KbDocument|null> Documents read during this run, by project.
     */
    private array $documents = [];

    /**
     * Add an open deadline to the personal calendar of the user.
     *
     * @param string $projectId Project id.
     * @param string $itemId Id of the deadline.
     * @param \App\Model\Entity\User $user Subscriber.
     * @return void
     * @throws \InvalidArgumentException When there is no open deadline with a date to add.
     */
    public function subscribe(string $projectId, string $itemId, User $user): void
    {
        $this->documents = [];
        $links = $this->links();
        $links->getConnection()->transactional(function () use ($links, $projectId, $itemId, $user): void {
            $item = $this->deadline($projectId, $itemId);
            if ($item === null) {
                throw new InvalidArgumentException('There is no open deadline with a date.');
            }

            $link = $links->find()
                ->where(['project_id' => $projectId, 'item_id' => $itemId, 'calendar_id' => $user->id])
                ->first();
            if (!$link) {
                $link = $links->newEmptyEntity();
                $link->id = Text::uuid();
                $link->project_id = $projectId;
                $link->item_id = $itemId;
                $link->calendar_id = $user->id;
                $links->saveOrFail($link);
            }
            $this->sync($link);
        });
    }

    /**
     * Remove the deadline from the calendar of the user, together with the managed entry.
     *
     * @param string $projectId Project id.
     * @param string $itemId Id of the deadline.
     * @param string $userId Subscriber id.
     * @return void
     */
    public function unsubscribe(string $projectId, string $itemId, string $userId): void
    {
        $links = $this->links();
        $links->getConnection()->transactional(function () use ($links, $projectId, $itemId, $userId): void {
            $link = $links->find()
                ->where(['project_id' => $projectId, 'item_id' => $itemId, 'calendar_id' => $userId])
                ->first();
            if ($link) {
                $this->removeEvent($link);
                $links->deleteOrFail($link);
            }
        });
    }

    /**
     * Bring the entries of the matching links in line with the knowledge base and the access of their users.
     *
     * @param array<string, mixed> $conditions Filter of the links.
     * @return void
     */
    public function synchronize(array $conditions = []): void
    {
        $links = $this->links();
        // Most saves concern nobody with a link: skip the transaction and the locks.
        if (!$links->exists($conditions)) {
            return;
        }

        $this->documents = [];
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
     * Ids of the deadlines of a project that are in the calendar of the user.
     *
     * @param string $projectId Project id.
     * @param string $userId User id.
     * @return array<string, true>
     */
    public function linkedItems(string $projectId, string $userId): array
    {
        $ids = $this->links()->find()
            ->select(['item_id'])
            ->where(['project_id' => $projectId, 'calendar_id' => $userId])
            ->all()
            ->extract('item_id')
            ->toList();

        return array_fill_keys($ids, true);
    }

    /**
     * What the page needs to show calendar links: the deadlines in the calendar of the user, or null when the user
     * cannot keep deadlines in a calendar.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param \App\Model\Entity\User $user User.
     * @return array<string, true>|null
     */
    public function linksFor(Project $project, User $user): ?array
    {
        return self::available($user, $project) ? $this->linkedItems((string)$project->id, (string)$user->id) : null;
    }

    /**
     * Whether the user can keep deadlines in a calendar.
     *
     * @param \App\Model\Entity\User $user User.
     * @param \Projects\Model\Entity\Project $project Project.
     * @return bool
     */
    public static function available(User $user, Project $project): bool
    {
        return $user->active
            && $user->hasAccess(AppPluginsEnum::Projects)
            && $user->hasAccess(AppPluginsEnum::Calendar)
            && (new ProjectPolicy())->canAccess($project, $user);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $link Link.
     * @return void
     */
    private function sync(EntityInterface $link): void
    {
        $tables = TableRegistry::getTableLocator();
        $user = $tables->get('App.Users')->find()->where(['id' => $link->calendar_id])->first();
        $project = $tables->get('Projects.Projects')->find()->where(['id' => $link->project_id])->first();
        if (!$user instanceof User || !$project instanceof Project || !self::available($user, $project)) {
            $this->drop($link);

            return;
        }

        $item = $this->deadline((string)$link->project_id, (string)$link->item_id);
        if ($item === null) {
            $this->drop($link);

            return;
        }

        $events = $tables->get('Calendar.Events');
        $entry = $link->event_id ? $events->find()->where([
            'id' => $link->event_id, 'calendar_id' => $link->calendar_id,
        ])->first() : null;
        $entry ??= $events->newEmptyEntity();

        $url = Router::url([
            'plugin' => 'Projects', 'controller' => 'Projects', 'action' => 'view', $project->id,
            '#' => rtrim(KbDocument::ANCHOR_PREFIX, '-') . '-roki',
        ], true);
        $data = [
            'calendar_id' => $user->id,
            'title' => mb_substr($project->getName() . ' · ' . $item->text, 0, 200),
            'body' => $item->text . "\n" . $url,
            'all_day' => true,
            'dat_start' => new DateTime($item->date . ' 00:00:00'),
            'dat_end' => new DateTime($item->date . ' 23:59:59'),
            'reminder' => 0,
        ];
        foreach ($data as $field => $value) {
            $entry->set($field, $value);
        }
        if ($entry->isNew() || $entry->isDirty()) {
            $events->saveOrFail($entry, ['kbCalendarSync' => true]);
        }

        $link->event_id = $entry->id;
        if ($link->isDirty()) {
            $this->links()->saveOrFail($link);
        }
    }

    /**
     * An open deadline with a date, or null.
     *
     * @param string $projectId Project id.
     * @param string $itemId Deadline id.
     * @return \Projects\Lib\KnowledgeBase\KbItem|null
     */
    private function deadline(string $projectId, string $itemId): ?KbItem
    {
        if (!array_key_exists($projectId, $this->documents)) {
            $project = TableRegistry::getTableLocator()->get('Projects.Projects')->find()
                ->select(['id', 'descript', 'kb_format'])->where(['id' => $projectId])->first();
            $this->documents[$projectId] = $project && (int)$project->kb_format === 1
                ? KbDocument::parse((string)$project->descript)
                : null;
        }

        $item = $this->documents[$projectId]?->findItem($itemId);

        return $item !== null && $item->section === 'deadlines' && $item->isOpen() && $item->date !== null
            ? $item
            : null;
    }

    /**
     * Remove the entry and the link.
     *
     * @param \Cake\Datasource\EntityInterface $link Link.
     * @return void
     */
    private function drop(EntityInterface $link): void
    {
        $this->removeEvent($link);
        $this->links()->deleteOrFail($link);
    }

    /**
     * Only remove the native event belonging to this subscriber.
     *
     * @param \Cake\Datasource\EntityInterface $link Link.
     * @return void
     */
    private function removeEvent(EntityInterface $link): void
    {
        if ($link->event_id) {
            $events = TableRegistry::getTableLocator()->get('Calendar.Events');
            $entry = $events->find()->where(['id' => $link->event_id, 'calendar_id' => $link->calendar_id])->first();
            if ($entry) {
                $events->deleteOrFail($entry, ['kbCalendarSync' => true]);
            }
        }
        $link->event_id = null;
    }

    /**
     * @return \Projects\Model\Table\ProjectsKbCalendarLinksTable
     */
    private function links(): ProjectsKbCalendarLinksTable
    {
        /** @var \Projects\Model\Table\ProjectsKbCalendarLinksTable */
        return TableRegistry::getTableLocator()->get('Projects.ProjectsKbCalendarLinks');
    }
}
