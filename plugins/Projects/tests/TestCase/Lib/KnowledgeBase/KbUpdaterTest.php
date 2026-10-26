<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Projects\Lib\KnowledgeBase\KbAiException;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbUpdater;

class KbUpdaterTest extends TestCase
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
     * @return string Log id.
     */
    private function log(string $descript, string $created = '2026-10-20 10:00:00'): string
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

        return (string)$logs->saveOrFail($log, ['checkRules' => false])->id;
    }

    /**
     * @return \Projects\Model\Entity\Project
     */
    private function project(): object
    {
        return $this->getTableLocator()->get('Projects.Projects')->get(self::PROJECT);
    }

    /**
     * @param string $status Status of the sources.
     * @return int
     */
    private function sources(string $status): int
    {
        return $this->getTableLocator()->get('Projects.ProjectsKbSources')->find()
            ->where(['status' => $status])
            ->count();
    }

    /**
     * @param string $text Text of the warning.
     * @return array<string, mixed>
     */
    private function warning(string $text = 'Manjka PGD'): array
    {
        return [
            'changes_summary' => 'Dodano opozorilo',
            'operations' => [['op' => 'add_item', 'section' => 'warnings', 'severity' => 'high', 'text' => $text]],
        ];
    }

    /**
     * @param string $status Status in the knowledge base, empty for none.
     * @return void
     */
    private function knowledgeBase(string $status = ''): void
    {
        $doc = KbDocument::empty();
        $doc->setText('summary', 'Gradnja poteka po načrtu.');
        $doc->addItem('warnings', 'Manjka PGD', ['severity' => 'high']);
        $doc->setText('status', $status);
        $this->getTableLocator()->get('Projects.Projects')->updateAll(
            ['descript' => $doc->toMarkdown(), 'kb_format' => 1, 'kb_version' => 1],
            ['id' => self::PROJECT],
        );
    }

    /**
     * @return void
     */
    public function testStatusesWritesStatusFromKnowledgeBase(): void
    {
        $this->knowledgeBase();
        $ai = new FakeKbAiClient([['status' => "Čakamo na PGD.\n"]]);

        $report = (new KbUpdater($ai))->statuses(['project' => '2022-01']);

        $this->assertSame(1, $report->projects);
        $this->assertSame(1, $report->revisions);
        $this->assertSame(0, $report->failed);
        $doc = KbDocument::parse($this->project()->descript);
        $this->assertSame('Čakamo na PGD.', $doc->text('status'));
        $this->assertSame('Manjka PGD', $doc->items('warnings')[0]->text);
        $this->assertStringContainsString('Manjka PGD', $ai->requests[0]['user']);
        $this->assertStringContainsString('180', $ai->requests[0]['system']);
    }

    /**
     * @return void
     */
    public function testStatusesLeavesExistingStatusUnlessAll(): void
    {
        $this->knowledgeBase('Staro stanje.');
        $ai = new FakeKbAiClient([['status' => 'Novo stanje.']]);

        $report = (new KbUpdater($ai))->statuses(['project' => '2022-01']);
        $this->assertSame(0, $report->projects);
        $this->assertSame([], $ai->requests);
        $this->assertSame('Staro stanje.', KbDocument::parse($this->project()->descript)->text('status'));

        $report = (new KbUpdater($ai))->statuses(['project' => '2022-01', 'all' => true]);
        $this->assertSame(1, $report->revisions);
        $this->assertSame('Novo stanje.', KbDocument::parse($this->project()->descript)->text('status'));
    }

    /**
     * @return void
     */
    public function testStatusesAsksAgainWhenTooLongAndGivesUp(): void
    {
        $this->knowledgeBase();
        $ai = new FakeKbAiClient([['status' => str_repeat('a', 200)], ['status' => 'Kratko stanje.']]);
        $report = (new KbUpdater($ai))->statuses(['project' => '2022-01']);
        $this->assertSame(1, $report->revisions);
        $this->assertCount(2, $ai->requests);
        $this->assertStringContainsString('predolgo', $ai->requests[1]['user']);

        $this->knowledgeBase();
        $ai = new FakeKbAiClient([['status' => str_repeat('a', 200)]]);
        $report = (new KbUpdater($ai))->statuses(['project' => '2022-01']);
        $this->assertSame(1, $report->failed);
        $this->assertSame(0, $report->revisions);
        $this->assertSame('', KbDocument::parse($this->project()->descript)->text('status'));
    }

    /**
     * @return void
     */
    public function testStatusesDryRunAndEmptyKnowledgeBaseWriteNothing(): void
    {
        $this->knowledgeBase();
        $ai = new FakeKbAiClient([['status' => 'Predlog.']]);
        $report = (new KbUpdater($ai))->statuses(['project' => '2022-01', 'dryRun' => true]);
        $this->assertSame(0, $report->revisions);
        $this->assertContains('2022-01: Predlog.', $report->messages);
        $this->assertSame('', KbDocument::parse($this->project()->descript)->text('status'));

        $this->getTableLocator()->get('Projects.Projects')->updateAll(
            ['descript' => KbDocument::empty()->toMarkdown(), 'kb_format' => 1],
            ['id' => self::PROJECT],
        );
        $ai = new FakeKbAiClient([['status' => 'Ne bi smelo biti.']]);
        $report = (new KbUpdater($ai))->statuses(['project' => '2022-01']);
        $this->assertSame([], $ai->requests);
        $this->assertSame(0, $report->projects);
    }

    /**
     * @return void
     */
    public function testBatchUpdateMaySetStatus(): void
    {
        $this->knowledgeBase();
        $this->log('Dovoljenje je izdano');
        $ai = new FakeKbAiClient([[
            'changes_summary' => 'Stanje',
            'operations' => [['op' => 'set_section', 'section' => 'status', 'text' => 'Dovoljenje je izdano.']],
        ]]);

        (new KbUpdater($ai))->run(['project' => '2022-01']);

        $this->assertSame('Dovoljenje je izdano.', KbDocument::parse($this->project()->descript)->text('status'));
        $this->assertStringContainsString('status', $ai->requests[0]['system']);
    }

    /**
     * @return void
     */
    public function testConvertsProjectAndAppliesOperations(): void
    {
        $this->getTableLocator()->get('Projects.Projects')
            ->updateAll(['descript' => 'Stara hiša'], ['id' => self::PROJECT]);
        $logId = $this->log('Investitor je poslal načrte');
        $ai = new FakeKbAiClient([$this->warning()]);

        $report = (new KbUpdater($ai))->run(['project' => '2022-01']);

        $this->assertSame(1, $report->projects);
        $this->assertSame(1, $report->events);
        $this->assertSame(1, $report->revisions);
        $project = $this->project();
        $this->assertSame(1, $project->kb_format);
        $this->assertNotNull($project->kb_synced);
        $doc = KbDocument::parse($project->descript);
        $this->assertSame('Stara hiša', $doc->text('description'));
        $this->assertSame('Manjka PGD', $doc->items('warnings')[0]->text);
        $this->assertStringContainsString('AI (paket): Dodano opozorilo', $doc->changelog()[0]);
        $this->assertSame(1, $this->sources('applied'));

        // The request carries the events with their references.
        $this->assertStringContainsString('[log:' . $logId . ']', $ai->requests[0]['user']);
        $this->assertStringContainsString('Investitor je poslal načrte', $ai->requests[0]['user']);
        $this->assertStringContainsString('Stara hiša', $ai->requests[0]['user']);
    }

    /**
     * @return void
     */
    public function testNoNewEventsMeansNoRequest(): void
    {
        $this->log('Vnos');
        $ai = new FakeKbAiClient([['operations' => []]]);
        $updater = new KbUpdater($ai);

        $updater->run(['project' => '2022-01']);
        $this->assertCount(1, $ai->requests);
        $this->assertSame(1, $this->sources('applied'));
        // Nothing was written, but the project is now a knowledge base.
        $this->assertSame(1, $this->project()->kb_format);

        $report = $updater->run(['project' => '2022-01']);

        $this->assertCount(1, $ai->requests);
        $this->assertSame(0, $report->projects);
    }

    /**
     * @return void
     */
    public function testSecondRoundSendsExistingItemsWithIds(): void
    {
        $this->log('Prvi vnos', '2026-10-20 10:00:00');
        $ai = new FakeKbAiClient([$this->warning()]);
        $updater = new KbUpdater($ai);
        $updater->run(['project' => '2022-01']);
        $id = KbDocument::parse($this->project()->descript)->items('warnings')[0]->id;

        // Events after the previous run, as they are in practice.
        $this->log('Drugi vnos: PGD prejet', DateTime::now()->modify('+1 day')->format('Y-m-d H:i:s'));
        $updater->run(['project' => '2022-01']);

        $this->assertStringContainsString('[' . $id . '] high · Manjka PGD', $ai->requests[1]['user']);
        $this->assertStringNotContainsString('Prvi vnos', $ai->requests[1]['user']);
    }

    /**
     * @return void
     */
    public function testUnusableAnswerIsRepeatedThreeTimesThenSkipped(): void
    {
        $this->log('Vnos');
        $updater = new KbUpdater(new FakeKbAiClient([null]));

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $report = $updater->run(['project' => '2022-01']);
            $this->assertSame(1, $report->failed);
            $this->assertSame(1, $this->sources('failed'));
            $this->assertSame(0, $this->sources('skipped'));
        }

        $updater->run(['project' => '2022-01']);

        $this->assertSame(0, $this->sources('failed'));
        $this->assertSame(1, $this->sources('skipped'));
        $this->assertSame(0, $updater->run(['project' => '2022-01'])->projects);
    }

    /**
     * @return void
     */
    public function testAnswerWithOnlyRejectedOperationsCountsAsFailure(): void
    {
        $this->log('Vnos');
        $ai = new FakeKbAiClient([['operations' => [['op' => 'remove_item', 'id' => 'w-0000']]]]);

        $report = (new KbUpdater($ai))->run(['project' => '2022-01']);

        $this->assertSame(1, $report->failed);
        $this->assertSame(1, $this->sources('failed'));
        $this->assertSame(0, $report->revisions);
    }

    /**
     * @return void
     */
    public function testUnreachableAiStopsTheRunWithoutCountingAnAttempt(): void
    {
        $this->log('Vnos');

        $report = (new KbUpdater(new FakeKbAiClient([new KbAiException('connection refused')])))
            ->run(['project' => '2022-01']);

        $this->assertStringContainsString('connection refused', (string)$report->stopped);
        $this->assertSame(0, $this->sources('failed'));
        $this->assertSame(0, $this->sources('applied'));
        $this->assertNull($this->project()->kb_synced);
    }

    /**
     * @return void
     */
    public function testDryRunChangesNothing(): void
    {
        $this->log('Vnos');
        $ai = new FakeKbAiClient([$this->warning()]);

        $report = (new KbUpdater($ai))->run(['project' => '2022-01', 'dryRun' => true]);

        $this->assertCount(1, $ai->requests);
        $this->assertStringContainsString('1 operations applied', implode("\n", $report->messages));
        $project = $this->project();
        $this->assertSame(0, $project->kb_format);
        $this->assertSame(0, $project->kb_version);
        $this->assertNull($project->kb_synced);
        $this->assertSame(0, $this->sources('applied'));
        $this->assertSame(0, $this->getTableLocator()->get('Projects.ProjectsKbRevisions')->find()->count());
    }

    /**
     * @return void
     */
    public function testMaxEventsSpreadsWorkOverRuns(): void
    {
        foreach ([1, 2, 3] as $day) {
            $this->log('Vnos ' . $day, '2026-10-2' . $day . ' 10:00:00');
        }
        $ai = new FakeKbAiClient([['operations' => []]]);
        $updater = new KbUpdater($ai);

        $updater->run(['project' => '2022-01', 'maxEvents' => 2]);
        $this->assertSame(2, $this->sources('applied'));
        $this->assertNull($this->project()->kb_synced);

        $updater->run(['project' => '2022-01', 'maxEvents' => 2]);
        $this->assertSame(3, $this->sources('applied'));
        $this->assertNotNull($this->project()->kb_synced);
        $this->assertStringContainsString('Vnos 3', $ai->requests[1]['user']);
        $this->assertStringNotContainsString('Vnos 1', $ai->requests[1]['user']);
    }

    /**
     * @return void
     */
    public function testEventsAreSplitIntoChunks(): void
    {
        $this->log(str_repeat('a', 60), '2026-10-20 10:00:00');
        $this->log(str_repeat('b', 60), '2026-10-21 10:00:00');
        $ai = new FakeKbAiClient([$this->warning('Prvo'), $this->warning('Drugo')]);
        $updater = new KbUpdater($ai, chunkChars: 100);

        $report = $updater->run(['project' => '2022-01']);

        $this->assertCount(2, $ai->requests);
        $this->assertSame(2, $report->revisions);
        $this->assertCount(2, KbDocument::parse($this->project()->descript)->items('warnings'));
    }

    /**
     * @return void
     */
    public function testRunWithoutProjectTakesActiveProjectsWithEvents(): void
    {
        $this->log('Vnos');
        $ai = new FakeKbAiClient([$this->warning()]);

        $report = (new KbUpdater($ai))->run(['projects' => 5]);

        $this->assertSame(1, $report->projects);
        $this->assertCount(1, $ai->requests);
    }
}
