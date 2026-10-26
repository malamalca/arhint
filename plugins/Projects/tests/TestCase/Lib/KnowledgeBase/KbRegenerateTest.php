<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Projects\Lib\KnowledgeBase\KbAiException;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbOperationApplier;
use Projects\Lib\KnowledgeBase\KbService;
use Projects\Lib\KnowledgeBase\KbUpdater;
use Projects\Lib\KnowledgeBase\KbUpdateReport;

/**
 * Making the knowledge base again from all the events of a project.
 */
class KbRegenerateTest extends TestCase
{
    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';

    /**
     * @var array<int, string>
     */
    public array $fixtures = [
        'app.Users',
        'app.Logs',
        'plugin.Projects.LogsAnalysis',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsMilestones',
        'plugin.Projects.ProjectsTasks',
        'plugin.Projects.ProjectsTasksComments',
        'plugin.Crm.Contacts',
        'plugin.Projects.ProjectsAuthorityCases',
        'plugin.Projects.ProjectsAuthorityEvents',
        'plugin.Projects.ProjectsKbSources',
        'plugin.Projects.ProjectsKbRevisions',
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->get('App.Logs')->deleteAll([]);
        $this->getTableLocator()->get('Projects.ProjectsMilestones')->deleteAll([]);
    }

    /**
     * @param string $descript Text of the log.
     * @param string $created Time.
     * @return void
     */
    private function log(string $descript, string $created): void
    {
        $logs = $this->getTableLocator()->get('App.Logs');
        $log = $logs->newEntity([
            'id' => Text::uuid(),
            'model' => 'Project',
            'foreign_id' => self::PROJECT,
            'user_id' => USER_ADMIN,
            'action' => 'Comment',
            'descript' => $descript,
        ]);
        $log->created = new DateTime($created);
        $log->modified = new DateTime($created);
        $logs->saveOrFail($log, ['checkRules' => false]);
    }

    /**
     * @return \Projects\Model\Entity\Project
     */
    private function project(): object
    {
        return $this->getTableLocator()->get('Projects.Projects')->get(self::PROJECT);
    }

    /**
     * @param string $text Text of the warning.
     * @return array<string, mixed>
     */
    private function warning(string $text): array
    {
        return [
            'changes_summary' => 'Dodano opozorilo',
            'operations' => [['op' => 'add_item', 'section' => 'warnings', 'severity' => 'high', 'text' => $text]],
        ];
    }

    /**
     * A project that has been through the regular update and was then changed by people.
     *
     * @return void
     */
    private function existingKnowledgeBase(): void
    {
        $this->getTableLocator()->get('Projects.Projects')
            ->updateAll(['descript' => 'Stara hiša'], ['id' => self::PROJECT]);
        $this->log('Investitor je poslal načrte', '2026-10-20 10:00:00');
        (new KbUpdater(new FakeKbAiClient([$this->warning('Staro opozorilo AI')])))->run(['project' => '2022-01']);
        (new KbService())->apply(self::PROJECT, [
            ['op' => 'add_item', 'section' => 'warnings', 'text' => 'Ročno opozorilo'],
            ['op' => 'set_section', 'section' => 'notes', 'text' => 'Moje opombe'],
        ], KbOperationApplier::CHAT, USER_ADMIN);
    }

    /**
     * @return void
     */
    public function testRegenerationReplacesWhatTheAiMaintainsAndKeepsWhatIsTheUsers(): void
    {
        $this->existingKnowledgeBase();
        $this->log('Prišlo je gradbeno dovoljenje', '2026-10-25 10:00:00');
        $before = (int)$this->project()->kb_version;
        $ai = new FakeKbAiClient([$this->warning('Novo opozorilo')]);
        $report = new KbUpdateReport();

        $made = (new KbUpdater($ai))->regenerate($this->project(), $report, false, USER_ADMIN);

        $this->assertTrue($made);
        $this->assertSame(2, $report->events);
        $this->assertSame(1, $report->revisions);
        $project = $this->project();
        $this->assertSame($before + 1, (int)$project->kb_version);

        $doc = KbDocument::parse((string)$project->descript);
        $this->assertSame(['Novo opozorilo'], array_map(fn($item) => $item->text, $doc->items('warnings')));
        $this->assertSame('Stara hiša', $doc->text('description'));
        $this->assertSame('Moje opombe', $doc->text('notes'));
        $this->assertStringContainsString('Admin User (regeneracija): ponovno izdelan iz 2 dogodkov', $doc->changelog()[0]);
        $this->assertGreaterThan(1, count($doc->changelog()));

        $revision = $this->getTableLocator()->get('Projects.ProjectsKbRevisions')->find()
            ->orderBy(['version' => 'DESC'])->firstOrFail();
        $this->assertSame('regenerate', $revision->source);
        $this->assertSame(USER_ADMIN, $revision->user_id);
    }

    /**
     * @return void
     */
    public function testEventsThatWereDealtWithBeforeAreReadAgainAndNothingIsLeftPending(): void
    {
        $this->existingKnowledgeBase();
        $ai = new FakeKbAiClient([$this->warning('Novo opozorilo')]);

        (new KbUpdater($ai))->regenerate($this->project(), new KbUpdateReport());

        $this->assertNotEmpty($ai->requests);
        $this->assertStringContainsString('Investitor je poslal načrte', $ai->requests[0]['user']);
        $sources = $this->getTableLocator()->get('Projects.ProjectsKbSources');
        $this->assertSame(0, $sources->find()->where(['status !=' => 'applied'])->count());
        $this->assertNotNull($this->project()->kb_synced);

        // The regular update has nothing left to do.
        $next = new FakeKbAiClient();
        $report = (new KbUpdater($next))->run(['project' => '2022-01']);
        $this->assertSame([], $next->requests);
        $this->assertSame(0, $report->events);
    }

    /**
     * @return void
     */
    public function testChunksAreGivenOldestFirstEachWithTheStateSoFar(): void
    {
        $this->existingKnowledgeBase();
        $this->log('Drugi dogodek', '2026-10-21 10:00:00');
        $this->log('Tretji dogodek', '2026-10-22 10:00:00');
        $ai = new FakeKbAiClient([$this->warning('Prvo'), $this->warning('Drugo'), $this->warning('Tretje')]);

        // Tiny chunks: one event each.
        (new KbUpdater($ai, chunkChars: 1))->regenerate($this->project(), new KbUpdateReport());

        $this->assertCount(3, $ai->requests);
        $this->assertStringContainsString('(empty)', $ai->requests[0]['user']);
        $this->assertStringContainsString('Investitor je poslal načrte', $ai->requests[0]['user']);
        $this->assertStringContainsString('Prvo', $ai->requests[1]['user']);
        $this->assertStringContainsString('Drugi dogodek', $ai->requests[1]['user']);
        $this->assertStringContainsString('Drugo', $ai->requests[2]['user']);
        $doc = KbDocument::parse((string)$this->project()->descript);
        $this->assertSame(['Prvo', 'Drugo', 'Tretje'], array_map(fn($item) => $item->text, $doc->items('warnings')));
    }

    /**
     * @return void
     */
    public function testUnusableAnswerChangesNothing(): void
    {
        $this->existingKnowledgeBase();
        $project = $this->project();
        $report = new KbUpdateReport();

        $made = (new KbUpdater(new FakeKbAiClient([null])))->regenerate($project, $report);

        $this->assertFalse($made);
        $this->assertNotNull($report->stopped);
        $this->assertSame(1, $report->failed);
        $after = $this->project();
        $this->assertSame($project->kb_version, $after->kb_version);
        $this->assertSame($project->descript, $after->descript);
        $this->assertSame(0, $this->getTableLocator()->get('Projects.ProjectsKbSources')
            ->find()->where(['status !=' => 'applied'])->count());
    }

    /**
     * @return void
     */
    public function testAnswerThatIsUnusableOnlyOnceIsAskedAgain(): void
    {
        $this->existingKnowledgeBase();
        $ai = new FakeKbAiClient([null, $this->warning('Po drugem poskusu')]);

        $made = (new KbUpdater($ai))->regenerate($this->project(), new KbUpdateReport());

        $this->assertTrue($made);
        $this->assertCount(2, $ai->requests);
    }

    /**
     * @return void
     */
    public function testUnreachableModelChangesNothing(): void
    {
        $this->existingKnowledgeBase();
        $project = $this->project();

        try {
            (new KbUpdater(new FakeKbAiClient([new KbAiException('down')])))
                ->regenerate($project, new KbUpdateReport());
            $this->fail('Expected the exception.');
        } catch (KbAiException) {
            $this->assertSame($project->descript, $this->project()->descript);
        }
    }

    /**
     * @return void
     */
    public function testDryRunWritesNothing(): void
    {
        $this->existingKnowledgeBase();
        $project = $this->project();
        $report = new KbUpdateReport();

        $made = (new KbUpdater(new FakeKbAiClient([$this->warning('Poskus')])))->regenerate($project, $report, true);

        $this->assertTrue($made);
        $this->assertSame($project->descript, $this->project()->descript);
        $this->assertStringContainsString('would be written: 1 open warnings', implode("\n", $report->messages));
    }

    /**
     * @return void
     */
    public function testProjectWithoutEventsIsLeftAlone(): void
    {
        $project = $this->project();
        $report = new KbUpdateReport();
        $ai = new FakeKbAiClient();

        $made = (new KbUpdater($ai))->regenerate($project, $report);

        $this->assertFalse($made);
        $this->assertSame([], $ai->requests);
        $this->assertSame($project->kb_version, $this->project()->kb_version);
    }
}
