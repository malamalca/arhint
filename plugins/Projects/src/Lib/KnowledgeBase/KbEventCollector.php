<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use Cake\Core\Plugin;
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
 * of the authority procedures, tasks, comments of tasks, milestones, and the documents, invoices and travel orders
 * of the project that no log entry links to (older ones and those assigned without a log). An event is pending until a row of
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
     * @param bool $ignoreHandled Return the events that were dealt with before as well (for a new generation).
     * @return array<int, \Projects\Lib\KnowledgeBase\KbEvent>
     */
    public function collect(
        string $projectId,
        ?DateTimeInterface $since,
        int $limit,
        bool &$more = false,
        bool $ignoreHandled = false,
    ): array {
        $since = $since === null ? null : DateTime::parse($since)->modify(self::SLACK);

        $events = array_merge(
            $this->logs($projectId, $since),
            $this->authorityEvents($projectId, $since),
            $this->tasks($projectId, $since),
            $this->taskComments($projectId, $since),
            $this->milestones($projectId, $since),
            $this->documents($projectId, $since),
        );

        $handled = $ignoreHandled ? [] : $this->handled($projectId);
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
     * Documents, invoices and travel orders of the project that no log entry of the project links to.
     *
     * Those with a log reach the model through the log (who assigned it, when, and its AI summary), so they are
     * not given twice. The rest is what the project folder holds without a log: documents assigned before the logs
     * were written, or without the project being set in a save that could write one.
     *
     * @param string $projectId Project id.
     * @param \Cake\I18n\DateTime|null $since Lower bound of the modification time.
     * @return array<int, \Projects\Lib\KnowledgeBase\KbEvent>
     */
    private function documents(string $projectId, ?DateTime $since): array
    {
        if (!Plugin::isLoaded('Documents')) {
            return [];
        }

        $logged = $this->loggedDocumentIds($projectId);
        $sources = [
            'Document' => ['Documents.Documents', []],
            'Invoice' => ['Documents.Invoices', ['DocumentsCounters']],
            'TravelOrder' => ['Documents.TravelOrders', []],
        ];

        $rows = [];
        foreach ($sources as $model => [$tableName, $contain]) {
            $table = $this->fetchTable($tableName);
            $alias = $table->getAlias();
            $query = $table->find()->contain($contain)->where([$alias . '.project_id' => $projectId]);
            if ($since !== null) {
                $query->where(['OR' => [$alias . '.modified >' => $since, $alias . '.created >' => $since]]);
            }
            foreach ($query->all() as $row) {
                if (!isset($logged[strtolower((string)$row->id)])) {
                    $rows[] = [$model, $row];
                }
            }
        }
        if ($rows === []) {
            return [];
        }

        $summaries = $this->analysisSummaries(array_map(fn(array $item): string => (string)$item[1]->id, $rows));
        $names = $this->userNames(array_map(
            fn(array $item): mixed => $item[1]->get('user_id') ?? $item[1]->get('entered_by_id'),
            $rows,
        ));

        $events = [];
        foreach ($rows as [$model, $row]) {
            $text = match ($model) {
                'Invoice' => $this->invoiceText($row),
                'TravelOrder' => $this->travelOrderText($row),
                default => $this->documentText($row),
            };
            $userId = (string)($row->get('user_id') ?? $row->get('entered_by_id'));
            if (isset($names[$userId])) {
                $text .= ', vnesel ' . $names[$userId];
            }
            $text .= '.';
            if (trim((string)$row->get('descript')) !== '') {
                $text .= ' ' . $this->limit(self::plainText((string)$row->get('descript')));
            }
            if (isset($summaries[(string)$row->id])) {
                $text .= "\nPovzetek (AI): " . $summaries[(string)$row->id];
            }

            $events[] = new KbEvent(
                $model,
                (string)$row->id,
                $this->modified($row),
                $this->time($row->created ?? $row->modified),
                $text,
            );
        }

        return $events;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $row Document.
     * @return string
     */
    private function documentText(EntityInterface $row): string
    {
        $parts = [trim(sprintf('Dokument %s »%s«', $row->get('no'), $row->get('title')))];
        $this->addDate($parts, 'datum izdaje', $row->get('dat_issue'));
        if (trim((string)$row->get('location')) !== '') {
            $parts[] = 'kraj ' . $row->get('location');
        }
        if ((int)$row->get('attachments_count') > 0) {
            $parts[] = 'priponk: ' . (int)$row->get('attachments_count');
        }

        return implode(', ', $parts);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $row Invoice.
     * @return string
     */
    private function invoiceText(EntityInterface $row): string
    {
        $direction = $row->get('documents_counter')?->get('direction');
        $kind = match ($direction) {
            'issued' => 'Izdan račun',
            'received' => 'Prejet račun',
            default => 'Račun',
        };

        $parts = [trim(sprintf('%s %s »%s«', $kind, $row->get('no'), $row->get('title')))];
        $this->addDate($parts, 'datum izdaje', $row->get('dat_issue'));
        $this->addDate($parts, 'zapade', $row->get('dat_expire'));
        if ($row->get('total') !== null) {
            $parts[] = 'znesek z davkom ' . number_format((float)$row->get('total'), 2, ',', '.');
        }

        return implode(', ', $parts);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $row Travel order.
     * @return string
     */
    private function travelOrderText(EntityInterface $row): string
    {
        $parts = [trim(sprintf('Potni nalog %s »%s«', $row->get('no'), $row->get('title')))];
        $this->addDate($parts, 'datum izdaje', $row->get('dat_issue'));
        if (trim((string)$row->get('taskee')) !== '') {
            $parts[] = 'namen ' . $row->get('taskee');
        }
        $this->addDate($parts, 'odhod', $row->get('departure'));
        $this->addDate($parts, 'prihod', $row->get('arrival'));
        if ($row->get('total') !== null) {
            $parts[] = 'znesek ' . number_format((float)$row->get('total'), 2, ',', '.');
        }
        if (trim((string)$row->get('status')) !== '') {
            $parts[] = 'stanje ' . $row->get('status');
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<int, string> $parts Parts of a text, added to.
     * @param string $label What the date is.
     * @param mixed $date Date, if any.
     * @return void
     */
    private function addDate(array &$parts, string $label, mixed $date): void
    {
        if ($date instanceof DateTimeInterface) {
            $parts[] = $label . ' ' . $date->format('d.m.Y');
        }
    }

    /**
     * Ids that the log entries of the project link to, in any form of link: `…/view/<id>`.
     *
     * @param string $projectId Project id.
     * @return array<string, true> Lower case ids.
     */
    private function loggedDocumentIds(string $projectId): array
    {
        $ids = [];
        $logs = $this->fetchTable('App.Logs')->find()
            ->select(['descript'])
            ->where(['Logs.model' => 'Project', 'Logs.foreign_id' => $projectId, 'Logs.descript LIKE' => '%/view/%']);
        foreach ($logs as $log) {
            preg_match_all('/\/view\/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', (string)$log->descript, $matches);
            foreach ($matches[1] as $id) {
                $ids[strtolower($id)] = true;
            }
        }

        return $ids;
    }

    /**
     * The latest AI summary of each document, from the analysis of the document and its attachments.
     *
     * @param array<int, string> $ids Document ids.
     * @return array<string, string> Summary by document id.
     */
    private function analysisSummaries(array $ids): array
    {
        $summaries = [];
        $rows = $this->fetchTable('LogsAnalysis')->find()
            ->select(['event_id', 'summary'])
            ->where(['event_id IN' => $ids, 'summary IS NOT' => null])
            ->orderBy(['created' => 'ASC']);
        foreach ($rows as $row) {
            $summaries[(string)$row->event_id] = (string)$row->summary;
        }

        return $summaries;
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
