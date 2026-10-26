<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Projects\Model\Entity\Project;
use Projects\Model\Entity\ProjectsKbSource;
use Projects\Model\Table\ProjectsTable;

/**
 * Brings the knowledge base of projects up to date with what happened in them.
 *
 * Pending events of a project are given to the model in chunks together with the current state; the operations
 * it returns are applied through KbService as the batch source. An event is marked as dealt with only when its
 * chunk was answered with usable operations (or none), so an unusable answer is repeated by the next run, at most
 * MAX_ATTEMPTS times. An unreachable model stops the whole run and counts as no attempt.
 */
class KbUpdater
{
    use LocatorAwareTrait;

    public const MAX_ATTEMPTS = 3;

    /** Time a regeneration may take before it is given up, in seconds. */
    public const REGENERATE_SECONDS = 21600;

    /**
     * @param \Projects\Lib\KnowledgeBase\KbAiClientInterface $ai AI client.
     * @param \Projects\Lib\KnowledgeBase\KbService $service Writer of the knowledge base.
     * @param \Projects\Lib\KnowledgeBase\KbEventCollector $collector Finder of pending events.
     * @param \Projects\Lib\KnowledgeBase\KbPrompt $prompt Instructions and requests.
     * @param int $chunkChars Characters of events given to the model at once.
     */
    public function __construct(
        private KbAiClientInterface $ai,
        private KbService $service = new KbService(),
        private KbEventCollector $collector = new KbEventCollector(),
        private KbPrompt $prompt = new KbPrompt(),
        private int $chunkChars = 12000,
    ) {
    }

    /**
     * An updater configured by `Projects.KnowledgeBase`.
     *
     * @return self
     */
    public static function fromConfig(): self
    {
        $config = (array)Configure::read('Projects.KnowledgeBase');

        return new self(
            new KbAiClient(),
            new KbService(changelogEntries: (int)($config['changelogEntries'] ?? 50)),
            chunkChars: (int)($config['chunkChars'] ?? 12000),
        );
    }

    /**
     * Update projects.
     *
     * @param array{project?: string, projects?: int, maxEvents?: int, maxSeconds?: int, dryRun?: bool} $options
     *   `project` limits the run to one project (id or number), also when it is not active; `projects` is the
     *   number of projects with new events to deal with, `maxEvents` the events per project, `maxSeconds` the
     *   time after which no new project is started.
     * @return \Projects\Lib\KnowledgeBase\KbUpdateReport
     */
    public function run(array $options = []): KbUpdateReport
    {
        $report = new KbUpdateReport();
        $started = time();
        $maxProjects = (int)($options['projects'] ?? 5);
        $maxEvents = (int)($options['maxEvents'] ?? 60);
        $maxSeconds = (int)($options['maxSeconds'] ?? 2400);
        $dryRun = !empty($options['dryRun']);

        $found = 0;
        foreach ($this->candidates($options['project'] ?? null) as $project) {
            $found++;
            if ($report->projects >= $maxProjects) {
                break;
            }
            if (time() - $started >= $maxSeconds) {
                $report->stopped = 'time limit';
                break;
            }

            try {
                $this->updateProject($project, $maxEvents, $dryRun, $report, $started + $maxSeconds);
            } catch (KbAiException $e) {
                $report->stopped = 'AI not available: ' . $e->getMessage();
                break;
            } catch (StaleKnowledgeBaseException) {
                $report->note(sprintf('%s: the knowledge base is being changed, left for the next run.', $project->no));
            }
        }

        if ($found === 0 && isset($options['project'])) {
            $report->note('No such project, or the AI features are not on for its company.');
        }

        if ($report->messages !== [] || $report->stopped !== null) {
            $stopped = $report->stopped ? ' | stopped: ' . $report->stopped : '';
            Log::info('Knowledge base update: ' . implode(' | ', $report->messages) . $stopped, ['scope' => 'ai']);
        }

        return $report;
    }

    /**
     * Update one project.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param int $maxEvents Events to read in this run.
     * @param bool $dryRun Show the operations, change nothing.
     * @param \Projects\Lib\KnowledgeBase\KbUpdateReport $report Report to add to.
     * @param int $deadline Unix time after which no further chunk is started.
     * @return void
     * @throws \Projects\Lib\KnowledgeBase\KbAiException When the model cannot be reached.
     * @throws \Projects\Lib\KnowledgeBase\StaleKnowledgeBaseException When people keep changing the knowledge base.
     */
    public function updateProject(
        Project $project,
        int $maxEvents,
        bool $dryRun,
        KbUpdateReport $report,
        int $deadline = PHP_INT_MAX,
    ): void {
        $runStart = DateTime::now();
        $legacy = (int)$project->kb_format !== 1;
        $more = false;
        $events = $this->collector->collect(
            (string)$project->id,
            $legacy ? null : $project->kb_synced,
            $maxEvents,
            $more,
        );

        if ($events === []) {
            if (!$legacy && !$dryRun) {
                $this->markSynced($project, $runStart);
            }

            return;
        }

        $report->projects++;
        if ($legacy && !$dryRun) {
            $this->service->migrateLegacy((string)$project->id);
            $project = $this->table()->get($project->id);
        }

        $failures = 0;
        $doc = $this->service->document($project);
        foreach ($this->chunks($events) as $chunk) {
            if (time() >= $deadline) {
                $more = true;
                break;
            }

            $answer = $this->ask($doc, $chunk);
            if ($answer === null) {
                $failures += $this->fail($project, $chunk, $report, 'no usable answer');
                continue;
            }
            $operations = array_values($answer['operations']);
            $summary = (string)($answer['changes_summary'] ?? '');

            if ($dryRun) {
                $result = (new KbOperationApplier())->apply($doc, $operations, KbOperationApplier::BATCH);
                $report->events += count($chunk);
                $report->note(sprintf(
                    '%s: %d events → %d operations applied, %d rejected%s',
                    $project->no,
                    count($chunk),
                    $result->applied,
                    count($result->rejected),
                    $result->descriptions === [] ? '' : ' (' . implode('; ', $result->descriptions) . ')',
                ));
                foreach ($result->rejected as $reason) {
                    $report->note('  rejected ' . $reason);
                }
                continue;
            }

            $write = $this->write($project, $operations, $summary);
            if ($operations !== [] && $write->revision === null) {
                $reason = 'all operations rejected: ' . implode('; ', $write->result->rejected);
                $failures += $this->fail($project, $chunk, $report, $reason);
                continue;
            }

            $this->mark($project, $chunk, ProjectsKbSource::APPLIED, $write->revision?->id);
            $report->events += count($chunk);
            $report->revisions += $write->revision === null ? 0 : 1;
            $report->note(sprintf(
                '%s: %d events → %s',
                $project->no,
                count($chunk),
                $write->revision === null ? 'no change' : (string)$write->revision->summary,
            ));
            $doc = $this->service->document($this->table()->get($project->id));
        }

        if (!$dryRun && !$more && $failures === 0) {
            $this->markSynced($project, $runStart);
        }
    }

    /**
     * Make the knowledge base of a project again from all of its events.
     *
     * Everything is made in memory, starting from an empty knowledge base and giving the events to the model oldest
     * first. Only when every chunk was answered is the result written, in one revision, in place of what the AI
     * maintains (summary, warnings, deadlines, parties, procedures, decisions, questions, documents). The description,
     * the notes and the changelog stay. An unreachable model or an unusable answer therefore changes nothing.
     * Afterwards all the events count as dealt with, so the regular update only looks at what happens next.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param \Projects\Lib\KnowledgeBase\KbUpdateReport $report Report to add to.
     * @param bool $dryRun Make the knowledge base and report on it, write nothing.
     * @param string|null $userId User who asked for it.
     * @param int $deadline Unix time after which no further chunk is started; the run is then given up.
     * @return bool Whether a knowledge base was made (and written, unless dry run).
     * @throws \Projects\Lib\KnowledgeBase\KbAiException When the model cannot be reached.
     * @throws \Projects\Lib\KnowledgeBase\StaleKnowledgeBaseException When people keep changing the knowledge base.
     */
    public function regenerate(
        Project $project,
        KbUpdateReport $report,
        bool $dryRun = false,
        ?string $userId = null,
        int $deadline = PHP_INT_MAX,
    ): bool {
        if (!KbSettings::companyAllowed((string)$project->owner_id)) {
            $report->note(sprintf('%s: the AI features are not on for the company of the project.', $project->no));

            return false;
        }

        $runStart = DateTime::now();
        $more = false;
        $events = $this->collector->collect((string)$project->id, null, PHP_INT_MAX, $more, true);
        if ($events === []) {
            $report->note(sprintf('%s: no events, nothing to make the knowledge base from.', $project->no));

            return false;
        }

        $report->projects++;
        $generated = KbDocument::empty();
        $applier = new KbOperationApplier();
        $chunks = $this->chunks($events);
        foreach ($chunks as $index => $chunk) {
            if (time() >= $deadline) {
                $report->stopped = 'time limit, nothing was changed';

                return false;
            }

            // The answer is asked for once more when it is unusable; one bad chunk would spoil the whole result.
            $answer = $this->ask($generated, $chunk) ?? $this->ask($generated, $chunk);
            if ($answer === null) {
                $report->failed += count($chunk);
                $report->stopped = sprintf(
                    'no usable answer for part %d of %d, nothing was changed',
                    $index + 1,
                    count($chunks),
                );

                return false;
            }

            $result = $applier->apply($generated, array_values($answer['operations']), KbOperationApplier::BATCH);
            $report->events += count($chunk);
            $report->note(sprintf(
                '%s: part %d of %d, %d events → %d operations applied, %d rejected',
                $project->no,
                $index + 1,
                count($chunks),
                count($chunk),
                $result->applied,
                count($result->rejected),
            ));
        }

        if ($dryRun) {
            $open = $generated->openCounts();
            $report->note(sprintf(
                '%s: would be written: %d open warnings, %d open deadlines, %d open questions.',
                $project->no,
                $open['warnings'],
                $open['deadlines'],
                $open['open_questions'],
            ));

            return true;
        }

        $summary = sprintf('ponovno izdelan iz %d dogodkov', count($events));
        $attempt = 0;
        while (true) {
            try {
                $write = function () use ($project, $generated, $userId, $summary, $events, $runStart): void {
                    $revision = $this->service->replaceGenerated((string)$project->id, $generated, $userId, $summary);

                    $this->fetchTable('Projects.ProjectsKbSources')->deleteAll(['project_id' => $project->id]);
                    $this->mark($project, $events, ProjectsKbSource::APPLIED, (string)$revision->id);
                    $this->markSynced($project, $runStart);
                };
                $this->table()->getConnection()->transactional($write);
                break;
            } catch (StaleKnowledgeBaseException $e) {
                if (++$attempt >= 2) {
                    throw $e;
                }
            }
        }
        $report->revisions++;

        return true;
    }

    /**
     * Ask the model about a chunk of events.
     *
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Current state.
     * @param array<int, \Projects\Lib\KnowledgeBase\KbEvent> $chunk Events.
     * @return array<string, mixed>|null The answer; null when it has no list of operations.
     */
    private function ask(KbDocument $doc, array $chunk): ?array
    {
        $answer = $this->ai->json($this->prompt->system(), $this->prompt->request($doc, $chunk, DateTime::now()));

        return $answer !== null && isset($answer['operations']) && is_array($answer['operations']) ? $answer : null;
    }

    /**
     * Apply the operations, once again when the knowledge base was changed in the meantime.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param array<int, mixed> $operations Operations.
     * @param string $summary Summary of the changes.
     * @return \Projects\Lib\KnowledgeBase\KbWriteResult
     */
    private function write(Project $project, array $operations, string $summary): KbWriteResult
    {
        $attempt = 0;
        while (true) {
            $current = $this->table()->get($project->id);
            try {
                return $this->service->apply(
                    (string)$project->id,
                    $operations,
                    KbOperationApplier::BATCH,
                    null,
                    (int)$current->kb_version,
                    $summary,
                );
            } catch (StaleKnowledgeBaseException $e) {
                if (++$attempt >= 2) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Split events into chunks that fit the model.
     *
     * @param array<int, \Projects\Lib\KnowledgeBase\KbEvent> $events Events.
     * @return array<int, array<int, \Projects\Lib\KnowledgeBase\KbEvent>>
     */
    private function chunks(array $events): array
    {
        $chunks = [];
        $current = [];
        $size = 0;
        foreach ($events as $event) {
            $length = mb_strlen($event->text);
            if ($current !== [] && $size + $length > $this->chunkChars) {
                $chunks[] = $current;
                $current = [];
                $size = 0;
            }
            $current[] = $event;
            $size += $length;
        }
        if ($current !== []) {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * Count a failed attempt for the events of a chunk.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param array<int, \Projects\Lib\KnowledgeBase\KbEvent> $chunk Events.
     * @param \Projects\Lib\KnowledgeBase\KbUpdateReport $report Report.
     * @param string $reason Why it failed.
     * @return int Number of events.
     */
    private function fail(Project $project, array $chunk, KbUpdateReport $report, string $reason): int
    {
        $this->mark($project, $chunk, ProjectsKbSource::FAILED, null);
        $report->failed += count($chunk);
        $report->note(sprintf('%s: %d events failed (%s)', $project->no, count($chunk), $reason));

        return count($chunk);
    }

    /**
     * Remember what was done with events.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param array<int, \Projects\Lib\KnowledgeBase\KbEvent> $events Events.
     * @param string $status Status; a failed event becomes skipped after MAX_ATTEMPTS.
     * @param string|null $revisionId Revision that came out of the events.
     * @return void
     */
    private function mark(Project $project, array $events, string $status, ?string $revisionId): void
    {
        $sources = $this->fetchTable('Projects.ProjectsKbSources');
        foreach ($events as $event) {
            $row = $sources->find()->where([
                'source_model' => $event->model,
                'source_id' => $event->id,
                'source_modified' => $event->modified,
            ])->first() ?? $sources->newEntity([
                'project_id' => $project->id,
                'source_model' => $event->model,
                'source_id' => $event->id,
                'source_modified' => $event->modified,
                'attempts' => 0,
            ]);

            $attempts = (int)$row->attempts + ($status === ProjectsKbSource::FAILED ? 1 : 0);
            $row->patch([
                'status' => $status === ProjectsKbSource::FAILED && $attempts >= self::MAX_ATTEMPTS
                    ? ProjectsKbSource::SKIPPED
                    : $status,
                'attempts' => $attempts,
                'revision_id' => $revisionId,
            ]);
            $sources->saveOrFail($row);
        }
    }

    /**
     * All events up to now were dealt with.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param \Cake\I18n\DateTime $time Time the run started.
     * @return void
     */
    private function markSynced(Project $project, DateTime $time): void
    {
        $this->table()->updateAll(['kb_synced' => $time], ['id' => $project->id]);
    }

    /**
     * @param string|null $selector Id or number of the only project to update.
     * @return iterable<\Projects\Model\Entity\Project>
     */
    private function candidates(?string $selector = null): iterable
    {
        $query = $this->table()->find();
        $companies = KbSettings::companies();
        if ($companies !== []) {
            $query->where(['Projects.owner_id IN' => $companies]);
        }
        if ($selector !== null) {
            $query->where(['OR' => ['Projects.id' => $selector, 'Projects.no' => $selector]]);
        } else {
            // The ones waiting longest first; the never updated ones (empty kb_synced) sort before all.
            $query->where(['Projects.active' => true])
                ->orderBy(['Projects.kb_synced' => 'ASC', 'Projects.no' => 'ASC']);
        }

        foreach ($query->all() as $project) {
            if ($project instanceof Project) {
                yield $project;
            }
        }
    }

    /**
     * @return \Projects\Model\Table\ProjectsTable
     */
    private function table(): ProjectsTable
    {
        /** @var \Projects\Model\Table\ProjectsTable */
        return $this->fetchTable('Projects.Projects');
    }
}
