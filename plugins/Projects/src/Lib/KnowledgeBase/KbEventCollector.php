<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use DateTimeInterface;
use Projects\Lib\AuthorityWorkflow;
use Projects\Model\Entity\ProjectsKbSource;

/**
 * Finds what happened in a project since the knowledge base was last brought up to date.
 *
 * Sources: log entries (manual entries, saved e-mails, documents and invoices assigned to the project), events
 * of the authority procedures, tasks, comments of tasks and milestones. An event is pending until a row of
 * `projects_kb_sources` with the same source, id and modification time says it was applied or skipped.
 */
class KbEventCollector
{
    use LocatorAwareTrait;

    /** Characters of one event that are given to the model. */
    private const MAX_EVENT_CHARS = 4000;

    /** The modification times are compared with this much slack, for rows saved while the last run was going. */
    private const SLACK = '-5 minutes';

    /**
     * Pending events, oldest first.
     *
     * @param string $projectId Project id.
     * @param \DateTimeInterface|null $since Events modified before this are not looked at (minus a slack); all when empty.
     * @param int $limit Number of events to return.
     * @param bool $more Output: whether more pending events remain.
     * @return array<int, \Projects\Lib\KnowledgeBase\KbEvent>
     */
    public function collect(string $projectId, ?DateTimeInterface $since, int $limit, bool &$more = false): array
    {
        $since = $since === null ? null : DateTime::parse($since)->modify(self::SLACK);

        $events = array_merge(
            $this->logs($projectId, $since),
            $this->authorityEvents($projectId, $since),
            $this->tasks($projectId, $since),
            $this->taskComments($projectId, $since),
            $this->milestones($projectId, $since),
        );

        $handled = $this->handled($projectId);
        $events = array_values(array_filter($events, fn(KbEvent $event): bool => !isset($handled[$event->key()])));
        usort($events, fn(KbEvent $a, KbEvent $b): int => [$a->time, $a->id] <=> [$b->time, $b->id]);

        $more = count($events) > $limit;

        return array_slice($events, 0, $limit);
    }

    /**
     * Plain text of an HTML fragment; links become markdown links.
     *
     * @param string $html HTML.
     * @return string
     */
    public static function plainText(string $html): string
    {
        $html = (string)preg_replace_callback(
            '/<a\s[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is',
            fn(array $m): string => '[' . trim(strip_tags($m[2])) . '](' . html_entity_decode($m[1]) . ')',
            $html,
        );
        $html = (string)preg_replace('/<(br|\/p|\/div|\/tr|\/li)[^>]*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string)preg_replace('/[ \t]+/u', ' ', $text);

        return trim((string)preg_replace('/\n\s*\n+/', "\n", $text));
    }

    /**
     * @param string $projectId Project id.
     * @return array<string, true> Keys of the events that were dealt with.
     */
    private function handled(string $projectId): array
    {
        $rows = $this->fetchTable('Projects.ProjectsKbSources')->find()
            ->select(['source_model', 'source_id', 'source_modified'])
            ->where([
                'project_id' => $projectId,
                'status IN' => [ProjectsKbSource::APPLIED, ProjectsKbSource::SKIPPED],
            ])
            ->all();

        $keys = [];
        foreach ($rows as $row) {
            $keys[$row->source_model . '|' . $row->source_id . '|' . $this->time($row->source_modified)] = true;
        }

        return $keys;
    }

    /**
     * @param \DateTimeInterface|null $time Time.
     * @return string
     */
    private function time(?DateTimeInterface $time): string
    {
        return $time === null ? '' : $time->format('Y-m-d H:i:s');
    }

    /**
     * @param string $text Text of an event.
     * @return string
     */
    private function limit(string $text): string
    {
        return mb_strlen($text) > self::MAX_EVENT_CHARS
            ? mb_substr($text, 0, self::MAX_EVENT_CHARS) . ' […]'
            : $text;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $row Row.
     * @param string $field Field with a time.
     * @return string Time of the field, or the creation time when it is empty.
     */
    private function modified(EntityInterface $row, string $field = 'modified'): string
    {
        return $this->time($row->get($field) ?? $row->get('created'));
    }

    /**
     * Names of users.
     *
     * @param array<int, mixed> $ids User ids.
     * @return array<string, string>
     */
    private function userNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
        if ($ids === []) {
            return [];
        }

        $names = [];
        foreach ($this->fetchTable('Users')->find()->select(['id', 'name'])->where(['id IN' => $ids])->all() as $user) {
            $names[(string)$user->get('id')] = (string)$user->get('name');
        }

        return $names;
    }

    /**
     * @param string $projectId Project id.
     * @param \Cake\I18n\DateTime|null $since Lower bound of the modification time.
     * @return array<int, \Projects\Lib\KnowledgeBase\KbEvent>
     */
    private function logs(string $projectId, ?DateTime $since): array
    {
        $query = $this->fetchTable('App.Logs')->find()
            ->where(['Logs.model' => 'Project', 'Logs.foreign_id' => $projectId]);
        if ($since !== null) {
            $query->where(['OR' => ['Logs.modified >' => $since, 'Logs.created >' => $since]]);
        }
        $logs = $query->all()->toList();
        if ($logs === []) {
            return [];
        }

        $names = $this->userNames(array_map(fn($log) => $log->user_id, $logs));
        $summaries = $this->summaries($logs);

        $events = [];
        foreach ($logs as $log) {
            $text = self::plainText((string)$log->descript);
            // The history of a procedure arrives with the structured events of the procedure.
            if ($text === '' || str_contains((string)$log->descript, '/projects-authorities/view/')) {
                continue;
            }

            $text = $this->limit($text);
            foreach ($summaries[$log->id] ?? [] as $summary) {
                $text .= "\nPovzetek (AI): " . $summary;
            }
            $events[] = new KbEvent(
                'Log',
                (string)$log->id,
                $this->modified($log),
                $this->time($log->created ?? $log->modified),
                sprintf(
                    'Vnos v dnevnik projekta%s%s: %s',
                    $log->user_id && isset($names[$log->user_id]) ? ' (' . $names[$log->user_id] . ')' : '',
                    $log->action && $log->action !== 'Comment' ? ' [' . $log->action . ']' : '',
                    $text,
                ),
            );
        }

        return $events;
    }

    /**
     * AI summaries of the logs and of the documents that the logs link to.
     *
     * @param array<int, \Cake\Datasource\EntityInterface> $logs Logs.
     * @return array<string, array<int, string>> Summaries by log id.
     */
    private function summaries(array $logs): array
    {
        $related = [];
        foreach ($logs as $log) {
            $related[(string)$log->id] = [(string)$log->id];
            preg_match_all(
                '/href="[^"]*\/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})[^"]*"/i',
                (string)$log->descript,
                $matches,
            );
            foreach ($matches[1] as $documentId) {
                $related[(string)$log->id][] = $documentId;
            }
        }

        $ids = array_values(array_unique(array_merge(...array_values($related))));
        $rows = $this->fetchTable('LogsAnalysis')->find()
            ->select(['event_id', 'summary'])
            ->where(['event_id IN' => $ids, 'summary IS NOT' => null])
            ->orderBy(['created' => 'ASC'])
            ->all();
        $byEvent = [];
        foreach ($rows as $row) {
            $byEvent[$row->event_id] = (string)$row->summary;
        }

        $summaries = [];
        foreach ($related as $logId => $eventIds) {
            foreach ($eventIds as $eventId) {
                if (isset($byEvent[$eventId])) {
                    $summaries[$logId][] = $byEvent[$eventId];
                }
            }
        }

        return $summaries;
    }

    /**
     * @param string $projectId Project id.
     * @param \Cake\I18n\DateTime|null $since Lower bound of the modification time.
     * @return array<int, \Projects\Lib\KnowledgeBase\KbEvent>
     */
    private function authorityEvents(string $projectId, ?DateTime $since): array
    {
        $query = $this->fetchTable('Projects.ProjectsAuthorityEvents')->find()
            ->contain(['Cases' => ['Authorities']])
            ->where(['Cases.project_id' => $projectId]);
        if ($since !== null) {
            $query->where(['OR' => [
                'ProjectsAuthorityEvents.modified >' => $since,
                'ProjectsAuthorityEvents.created >' => $since,
            ]]);
        }
        $rows = $query->all()->toList();
        if ($rows === []) {
            return [];
        }

        $names = $this->userNames(array_map(fn($row) => $row->user_id, $rows));
        $kinds = AuthorityWorkflow::kinds();

        $events = [];
        foreach ($rows as $row) {
            $parts = [sprintf(
                'Postopek pri soglasodajalcu %s (%s): %s, datum dogodka %s',
                $row->case->authority->name ?? '?',
                $row->case->title,
                $kinds[$row->kind] ?? $row->kind,
                $row->event_date?->format('d.m.Y'),
            )];
            if ($row->date_due) {
                $parts[] = 'rok ' . $row->date_due->format('d.m.Y');
            }
            if ($row->date_valid) {
                $parts[] = 'velja do ' . $row->date_valid->format('d.m.Y');
            }
            if (isset($names[(string)$row->user_id])) {
                $parts[] = 'vnesel ' . $names[(string)$row->user_id];
            }
            $text = implode(', ', $parts) . '.';
            if (trim((string)$row->descript) !== '') {
                $text .= ' ' . $this->limit(self::plainText((string)$row->descript));
            }

            $events[] = new KbEvent(
                'AuthorityEvent',
                (string)$row->id,
                $this->modified($row),
                $this->time($row->created ?? $row->modified),
                $text,
            );
        }

        return $events;
    }

    /**
     * @param string $projectId Project id.
     * @param \Cake\I18n\DateTime|null $since Lower bound of the modification time.
     * @return array<int, \Projects\Lib\KnowledgeBase\KbEvent>
     */
    private function tasks(string $projectId, ?DateTime $since): array
    {
        $query = $this->fetchTable('Projects.ProjectsTasks')->find()
            ->contain(['Milestones'])
            ->where(['ProjectsTasks.project_id' => $projectId]);
        if ($since !== null) {
            $query->where(['OR' => ['ProjectsTasks.modified >' => $since, 'ProjectsTasks.created >' => $since]]);
        }
        $rows = $query->all()->toList();
        if ($rows === []) {
            return [];
        }

        $names = $this->userNames(array_map(fn($row) => $row->assigned_user_id, $rows));

        $events = [];
        foreach ($rows as $row) {
            $parts = [sprintf(
                'Naloga #%s »%s«: %s',
                $row->no,
                $row->title,
                $row->closed ? 'zaprta ' . $row->closed->format('d.m.Y') : 'odprta',
            )];
            if (isset($names[(string)$row->assigned_user_id])) {
                $parts[] = 'odgovorna oseba ' . $names[(string)$row->assigned_user_id];
            }
            if ($row->milestone) {
                $parts[] = 'mejnik »' . $row->milestone->title . '«';
            }
            $text = implode(', ', $parts) . '.';
            if (trim((string)$row->descript) !== '') {
                $text .= ' ' . $this->limit(self::plainText((string)$row->descript));
            }

            $events[] = new KbEvent(
                'Task',
                (string)$row->id,
                $this->modified($row),
                $this->time($row->modified ?? $row->created),
                $text,
            );
        }

        return $events;
    }

    /**
     * @param string $projectId Project id.
     * @param \Cake\I18n\DateTime|null $since Lower bound of the modification time.
     * @return array<int, \Projects\Lib\KnowledgeBase\KbEvent>
     */
    private function taskComments(string $projectId, ?DateTime $since): array
    {
        $query = $this->fetchTable('Projects.ProjectsTasksComments')->find()
            ->contain(['Tasks'])
            ->where(['Tasks.project_id' => $projectId]);
        if ($since !== null) {
            $query->where(['OR' => [
                'ProjectsTasksComments.modified >' => $since,
                'ProjectsTasksComments.created >' => $since,
            ]]);
        }
        $rows = $query->all()->toList();
        if ($rows === []) {
            return [];
        }

        $names = $this->userNames(array_map(fn($row) => $row->user_id, $rows));

        $events = [];
        foreach ($rows as $row) {
            $text = trim(self::plainText((string)$row->descript));
            if ($text === '') {
                continue;
            }
            $events[] = new KbEvent(
                'TaskComment',
                (string)$row->id,
                $this->modified($row),
                $this->time($row->created ?? $row->modified),
                sprintf(
                    'Komentar k nalogi #%s »%s«%s: %s',
                    $row->task->no,
                    $row->task->title,
                    isset($names[(string)$row->user_id]) ? ' (' . $names[(string)$row->user_id] . ')' : '',
                    $this->limit($text),
                ),
            );
        }

        return $events;
    }

    /**
     * @param string $projectId Project id.
     * @param \Cake\I18n\DateTime|null $since Lower bound of the modification time.
     * @return array<int, \Projects\Lib\KnowledgeBase\KbEvent>
     */
    private function milestones(string $projectId, ?DateTime $since): array
    {
        $query = $this->fetchTable('Projects.ProjectsMilestones')->find()
            ->where(['ProjectsMilestones.project_id' => $projectId]);
        if ($since !== null) {
            $query->where(['OR' => [
                'ProjectsMilestones.modified >' => $since,
                'ProjectsMilestones.created >' => $since,
            ]]);
        }

        $events = [];
        foreach ($query->all() as $row) {
            $parts = [sprintf('Mejnik »%s«', $row->title)];
            if ($row->date_due) {
                $parts[] = 'rok ' . $row->date_due->format('d.m.Y');
            }
            $parts[] = $row->date_complete ? 'zaključen ' . $row->date_complete->format('d.m.Y') : 'odprt';
            $parts[] = sprintf('odprtih nalog %d, zaključenih %d', $row->tasks_open, $row->tasks_done);

            $events[] = new KbEvent(
                'Milestone',
                (string)$row->id,
                $this->modified($row),
                $this->time($row->modified ?? $row->created),
                implode(', ', $parts) . '.',
            );
        }

        return $events;
    }
}
