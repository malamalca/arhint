<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use InvalidArgumentException;
use Projects\Model\Entity\Project;
use Projects\Model\Entity\ProjectsKbRevision;
use Projects\Model\Table\ProjectsKbRevisionsTable;
use Projects\Model\Table\ProjectsTable;

/**
 * The only way to write the knowledge base of a project.
 *
 * Every write is one transaction that checks the version of the knowledge base, changes the description,
 * adds a changelog line and stores a revision. Authorization is up to the caller.
 */
class KbService
{
    private const MAX_SUMMARY = 300;

    /**
     * @var array<string, string> Names of users by id.
     */
    private array $userNames = [];

    /**
     * @param \Projects\Lib\KnowledgeBase\KbOperationApplier $applier Applier of operations.
     * @param int $changelogEntries Number of changelog lines to keep in the document.
     */
    public function __construct(
        private KbOperationApplier $applier = new KbOperationApplier(),
        private int $changelogEntries = 50,
    ) {
    }

    /**
     * The knowledge base of a project; a free-form description is shown as its description section.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @return \Projects\Lib\KnowledgeBase\KbDocument
     */
    public function document(Project $project): KbDocument
    {
        $text = (string)$project->descript;

        return (int)$project->kb_format === 1 ? KbDocument::parse($text) : KbDocument::fromLegacy($text);
    }

    /**
     * Apply operations.
     *
     * @param string $projectId Project id.
     * @param array<int, mixed> $operations Operations, see KbOperationApplier.
     * @param string $source One of the KbOperationApplier source constants.
     * @param string|null $userId User who made the change, empty for the batch run.
     * @param int|null $expectedVersion Version the caller worked with; a newer one is refused.
     * @param string|null $summary One-line summary for the changelog, generated when empty.
     * @return \Projects\Lib\KnowledgeBase\KbWriteResult
     * @throws \Projects\Lib\KnowledgeBase\StaleKnowledgeBaseException When the knowledge base was changed meanwhile.
     */
    public function apply(
        string $projectId,
        array $operations,
        string $source,
        ?string $userId = null,
        ?int $expectedVersion = null,
        ?string $summary = null,
    ): KbWriteResult {
        $result = new KbApplyResult();
        $written = $this->projects()->getConnection()->transactional(
            function () use ($projectId, $operations, $source, $userId, $expectedVersion, $summary, &$result) {
                $project = $this->freshProject($projectId, $expectedVersion);
                [$doc, $version] = $this->ensureFormat($project, $userId);

                $result = $this->applier->apply($doc, $operations, $source);
                if (!$result->changed()) {
                    // Rolls back the conversion of a free-form description too.
                    return false;
                }

                $text = trim((string)$summary) !== '' ? trim((string)$summary) : implode('; ', $result->descriptions);
                $revision = $this->write(
                    $project,
                    $doc,
                    $version,
                    $source,
                    $userId,
                    $text,
                    (string)json_encode($operations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                );

                return $revision;
            },
        );

        return new KbWriteResult($written ?: null, $result);
    }

    /**
     * Save a hand-edited document.
     *
     * Sections of the old document are replaced by what was submitted; the table of contents and the
     * changelog cannot be edited by hand.
     *
     * @param string $projectId Project id.
     * @param string $markdown The edited markdown.
     * @param int|null $expectedVersion Version the edit started from.
     * @param string|null $userId User who edited.
     * @return \Projects\Model\Entity\ProjectsKbRevision|null The new revision, null when nothing changed.
     * @throws \Projects\Lib\KnowledgeBase\StaleKnowledgeBaseException When the knowledge base was changed meanwhile.
     */
    public function saveManual(
        string $projectId,
        string $markdown,
        ?int $expectedVersion = null,
        ?string $userId = null,
    ): ?ProjectsKbRevision {
        if (trim($markdown) === '') {
            throw new InvalidArgumentException('The knowledge base cannot be emptied.');
        }

        $revision = $this->projects()->getConnection()->transactional(
            function () use ($projectId, $markdown, $expectedVersion, $userId) {
                $project = $this->freshProject($projectId, $expectedVersion);
                $wasLegacy = (int)$project->kb_format !== 1;
                [$old, $version] = $this->ensureFormat($project, $userId);

                // Free text sent for a project that was never converted is its new description.
                $new = $wasLegacy && !KbDocument::isKnowledgeBase($markdown)
                    ? KbDocument::fromLegacy($markdown)
                    : KbDocument::parse($markdown);
                $new->setChangelog($old->changelog());

                $changed = array_keys(array_filter(
                    $new->renderedSections(),
                    fn(string $rendered, string $id): bool => $rendered !== ($old->renderedSections()[$id] ?? null),
                    ARRAY_FILTER_USE_BOTH,
                ));
                if ($changed === []) {
                    // Rolls back the conversion of a free-form description too.
                    return false;
                }

                $titles = array_map(fn(string $id): string => KbDocument::SECTIONS[$id], $changed);

                return $this->write(
                    $project,
                    $new,
                    $version,
                    KbOperationApplier::MANUAL,
                    $userId,
                    'ročna sprememba' . ($titles === [] ? '' : ': ' . implode(', ', $titles)),
                    null,
                );
            },
        );

        return $revision ?: null;
    }

    /**
     * Replace everything the AI maintains with a freshly generated knowledge base.
     *
     * What belongs to the users stays as it is at this moment: the description, the notes and the changelog.
     * Summary, warnings, deadlines, parties, procedures, decisions, open questions and documents come from
     * `$generated`. The previous state remains in the history and can be returned to. The events that the
     * generation was made from are marked as dealt with by the caller.
     *
     * @param string $projectId Project id.
     * @param \Projects\Lib\KnowledgeBase\KbDocument $generated Knowledge base made from all the events of the project.
     * @param string|null $userId User who asked for it, empty for a run without one.
     * @param string $summary What the changelog says.
     * @return \Projects\Model\Entity\ProjectsKbRevision
     * @throws \Projects\Lib\KnowledgeBase\StaleKnowledgeBaseException When it was changed while this was written.
     */
    public function replaceGenerated(
        string $projectId,
        KbDocument $generated,
        ?string $userId,
        string $summary,
    ): ProjectsKbRevision {
        return $this->projects()->getConnection()->transactional(
            function () use ($projectId, $generated, $userId, $summary): ProjectsKbRevision {
                $project = $this->freshProject($projectId, null);
                [$current, $version] = $this->ensureFormat($project, $userId);

                $new = $generated;
                foreach (['description', 'notes'] as $section) {
                    $new->setText($section, $current->text($section));
                }
                $new->setChangelog($current->changelog());

                return $this->write(
                    $project,
                    $new,
                    $version,
                    KbOperationApplier::REGENERATE,
                    $userId,
                    $summary,
                    null,
                );
            },
        );
    }

    /**
     * Restore the content of an earlier revision as a new revision. The changelog is kept.
     *
     * @param string $projectId Project id.
     * @param string $revisionId Revision to return to.
     * @param string|null $userId User who reverts.
     * @return \Projects\Model\Entity\ProjectsKbRevision
     * @throws \InvalidArgumentException When the revision does not belong to the project or is not a knowledge base.
     */
    public function revert(string $projectId, string $revisionId, ?string $userId = null): ProjectsKbRevision
    {
        return $this->projects()->getConnection()->transactional(
            function () use ($projectId, $revisionId, $userId): ProjectsKbRevision {
                $project = $this->freshProject($projectId, null);
                if ((int)$project->kb_format !== 1) {
                    throw new InvalidArgumentException('The project has no knowledge base to return to.');
                }

                /** @var \Projects\Model\Entity\ProjectsKbRevision|null $target */
                $target = $this->revisions()->find()
                    ->where(['id' => $revisionId, 'project_id' => $projectId])
                    ->first();
                if ($target === null || !KbDocument::isKnowledgeBase($target->descript)) {
                    throw new InvalidArgumentException('The revision cannot be restored.');
                }

                $doc = KbDocument::parse((string)$target->descript);
                $doc->setChangelog(KbDocument::parse((string)$project->descript)->changelog());

                return $this->write(
                    $project,
                    $doc,
                    (int)$project->kb_version,
                    KbOperationApplier::REVERT,
                    $userId,
                    sprintf('vrnjeno stanje verzije %d', $target->version),
                    null,
                );
            },
        );
    }

    /**
     * Convert a free-form description to a knowledge base without any other change.
     *
     * @param string $projectId Project id.
     * @return bool False when the project already had a knowledge base.
     */
    public function migrateLegacy(string $projectId): bool
    {
        return $this->projects()->getConnection()->transactional(function () use ($projectId): bool {
            $project = $this->freshProject($projectId, null);
            if ((int)$project->kb_format === 1) {
                return false;
            }
            $this->ensureFormat($project, null);

            return true;
        });
    }

    /**
     * Load the project and refuse when it is not at the version the caller worked with.
     *
     * @param string $projectId Project id.
     * @param int|null $expectedVersion Expected version.
     * @return \Projects\Model\Entity\Project
     */
    private function freshProject(string $projectId, ?int $expectedVersion): Project
    {
        /** @var \Projects\Model\Entity\Project $project */
        $project = $this->projects()->get($projectId);
        if ($expectedVersion !== null && $expectedVersion !== (int)$project->kb_version) {
            throw new StaleKnowledgeBaseException('The knowledge base was changed in the meantime.');
        }

        return $project;
    }

    /**
     * Parse the knowledge base; a free-form description is first converted and the original is kept as a revision.
     *
     * @param \Projects\Model\Entity\Project $project Project, as loaded.
     * @param string|null $userId User on whose action the conversion happens.
     * @return array{0: \Projects\Lib\KnowledgeBase\KbDocument, 1: int} Document and the version it is at.
     */
    private function ensureFormat(Project $project, ?string $userId): array
    {
        $version = (int)$project->kb_version;
        if ((int)$project->kb_format === 1) {
            return [KbDocument::parse((string)$project->descript), $version];
        }

        $original = (string)$project->descript;
        $exists = $this->revisions()->exists(['project_id' => $project->id, 'version' => $version]);
        if (trim($original) !== '' && !$exists) {
            $this->revisions()->saveOrFail($this->revisions()->newEntity([
                'project_id' => $project->id,
                'user_id' => $userId,
                'version' => $version,
                'source' => KbOperationApplier::BACKFILL,
                'summary' => 'Izvirni opis pred pretvorbo v knowledge base',
                'descript' => $original,
            ]));
        }

        $doc = KbDocument::fromLegacy($original);
        $this->write(
            $project,
            $doc,
            $version,
            KbOperationApplier::BACKFILL,
            $userId,
            'opis projekta pretvorjen v knowledge base',
            null,
        );

        return [$doc, $version + 1];
    }

    /**
     * Write the document as the next version.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document, the changelog line is added to it.
     * @param int $version Version the project is at.
     * @param string $source Source of the change.
     * @param string|null $userId User.
     * @param string $summary What changed.
     * @param string|null $operations JSON of the applied operations.
     * @return \Projects\Model\Entity\ProjectsKbRevision
     */
    private function write(
        Project $project,
        KbDocument $doc,
        int $version,
        string $source,
        ?string $userId,
        string $summary,
        ?string $operations,
    ): ProjectsKbRevision {
        $summary = mb_substr($summary, 0, self::MAX_SUMMARY);
        $doc->addChangelog(
            sprintf('%s · %s: %s', DateTime::now()->format('d.m.Y H:i'), $this->actor($source, $userId), $summary),
            $this->changelogEntries,
        );
        $markdown = $doc->toMarkdown();

        // The condition on the version makes a concurrent writer fail instead of overwriting.
        $updated = $this->projects()->updateAll(
            ['descript' => $markdown, 'kb_version' => $version + 1, 'kb_format' => 1, 'modified' => DateTime::now()],
            ['id' => $project->id, 'kb_version' => $version],
        );
        if ($updated !== 1) {
            throw new StaleKnowledgeBaseException('The knowledge base was changed in the meantime.');
        }

        /** @var \Projects\Model\Entity\ProjectsKbRevision $revision */
        $revision = $this->revisions()->saveOrFail($this->revisions()->newEntity([
            'project_id' => $project->id,
            'user_id' => $userId,
            'version' => $version + 1,
            'source' => $source,
            'summary' => $summary,
            'operations' => $operations,
            'descript' => $markdown,
        ]));

        // In the same transaction, so the calendar entries and the knowledge base stand or fall together.
        EventManager::instance()->dispatch(
            new Event('Projects.KnowledgeBase.afterSave', $this, ['project_id' => (string)$project->id]),
        );

        return $revision;
    }

    /**
     * Who is named in the changelog.
     *
     * @param string $source Source of the change.
     * @param string|null $userId User.
     * @return string
     */
    private function actor(string $source, ?string $userId): string
    {
        if ($userId === null || $userId === '') {
            return match ($source) {
                KbOperationApplier::BATCH => 'AI (paket)',
                KbOperationApplier::REGENERATE => 'AI (regeneracija)',
                default => 'AI',
            };
        }

        if (!isset($this->userNames[$userId])) {
            $user = TableRegistry::getTableLocator()->get('Users')->find()
                ->select(['name'])
                ->where(['id' => $userId])
                ->first();
            $this->userNames[$userId] = trim((string)$user?->get('name')) ?: 'Uporabnik';
        }

        return $this->userNames[$userId] . match ($source) {
            KbOperationApplier::CHAT, KbOperationApplier::TOOL => ' (pogovor)',
            KbOperationApplier::REVERT => ' (razveljavitev)',
            KbOperationApplier::REGENERATE => ' (regeneracija)',
            default => '',
        };
    }

    /**
     * @return \Projects\Model\Table\ProjectsTable
     */
    private function projects(): ProjectsTable
    {
        /** @var \Projects\Model\Table\ProjectsTable */
        return TableRegistry::getTableLocator()->get('Projects.Projects');
    }

    /**
     * @return \Projects\Model\Table\ProjectsKbRevisionsTable
     */
    private function revisions(): ProjectsKbRevisionsTable
    {
        /** @var \Projects\Model\Table\ProjectsKbRevisionsTable */
        return TableRegistry::getTableLocator()->get('Projects.ProjectsKbRevisions');
    }
}
