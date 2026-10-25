<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Projects\Lib\KnowledgeBase\KbEventCollector;

class KbEventCollectorTest extends TestCase
{
    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';
    private const OTHER_PROJECT = '4dd53305-9715-4be4-b169-20defe113d2b';

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
    ];

    private KbEventCollector $collector;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->collector = new KbEventCollector();
        $this->getTableLocator()->get('App.Logs')->deleteAll([]);
        $this->getTableLocator()->get('Projects.ProjectsMilestones')->deleteAll([]);
    }

    /**
     * @param string $descript Text.
     * @param string $created Time.
     * @param string $projectId Project.
     * @return \Cake\Datasource\EntityInterface
     */
    private function log(string $descript, string $created, string $projectId = self::PROJECT)
    {
        $logs = $this->getTableLocator()->get('App.Logs');
        $log = $logs->newEntity([
            'id' => Text::uuid(),
            'model' => 'Project',
            'foreign_id' => $projectId,
            'user_id' => USER_ADMIN,
            'action' => 'Comment',
            'descript' => $descript,
        ]);
        $log->created = new DateTime($created);
        $log->modified = new DateTime($created);

        return $logs->saveOrFail($log, ['checkRules' => false]);
    }

    /**
     * @return void
     */
    public function testHtmlOfLogsBecomesPlainTextWithMarkdownLinks(): void
    {
        $log = $this->log(
            'Dodeljen <strong>račun</strong>: <a href="/documents/invoices/view/d0d59a31-6de7-4eb4-8230-ca09113a7fe6">R-1</a><br>Znesek &amp; DDV',
            '2026-10-20 10:00:00',
        );

        $events = $this->collector->collect(self::PROJECT, null, 10);

        $this->assertCount(1, $events);
        $this->assertSame('log:' . $log->id, $events[0]->ref());
        $this->assertStringContainsString('(Admin User)', $events[0]->text);
        $this->assertStringContainsString('račun: [R-1](/documents/invoices/view/d0d59a31-6de7-4eb4-8230-ca09113a7fe6)', $events[0]->text);
        $this->assertStringContainsString("\nZnesek & DDV", $events[0]->text);
        $this->assertStringNotContainsString('<', $events[0]->text);
    }

    /**
     * @return void
     */
    public function testEventsAreOrderedAndLimitedAndOtherProjectsAreIgnored(): void
    {
        $this->log('Tretji', '2026-10-22 10:00:00');
        $this->log('Prvi', '2026-10-20 10:00:00');
        $this->log('Drugi', '2026-10-21 10:00:00');
        $this->log('Tuj', '2026-10-21 11:00:00', self::OTHER_PROJECT);

        $more = false;
        $events = $this->collector->collect(self::PROJECT, null, 2, $more);

        $this->assertTrue($more);
        $this->assertCount(2, $events);
        $this->assertStringContainsString('Prvi', $events[0]->text);
        $this->assertStringContainsString('Drugi', $events[1]->text);
    }

    /**
     * @return void
     */
    public function testHandledEventsAreLeftOutUntilTheyAreModified(): void
    {
        $log = $this->log('Prvi', '2026-10-20 10:00:00');
        $sources = $this->getTableLocator()->get('Projects.ProjectsKbSources');
        $sources->saveOrFail($sources->newEntity([
            'project_id' => self::PROJECT,
            'source_model' => 'Log',
            'source_id' => $log->id,
            'source_modified' => '2026-10-20 10:00:00',
            'status' => 'applied',
            'attempts' => 0,
        ]));

        $this->assertSame([], $this->collector->collect(self::PROJECT, null, 10));

        $logs = $this->getTableLocator()->get('App.Logs');
        $logs->updateAll(['modified' => '2026-10-23 08:00:00'], ['id' => $log->id]);
        $events = $this->collector->collect(self::PROJECT, null, 10);
        $this->assertCount(1, $events);
        $this->assertSame('2026-10-23 08:00:00', $events[0]->modified);

        // A failed event stays pending.
        $sources->updateAll(['status' => 'failed', 'source_modified' => '2026-10-23 08:00:00'], ['source_id' => $log->id]);
        $this->assertCount(1, $this->collector->collect(self::PROJECT, null, 10));
        $sources->updateAll(['status' => 'skipped'], ['source_id' => $log->id]);
        $this->assertSame([], $this->collector->collect(self::PROJECT, null, 10));
    }

    /**
     * @return void
     */
    public function testSinceSkipsOldRowsWithASlack(): void
    {
        $this->log('Staro', '2026-10-20 10:00:00');
        $this->log('Skoraj', '2026-10-24 09:57:00');
        $this->log('Novo', '2026-10-24 11:00:00');

        $events = $this->collector->collect(self::PROJECT, new DateTime('2026-10-24 10:00:00'), 10);

        $this->assertSame(['Skoraj', 'Novo'], array_map(
            fn($event) => preg_replace('/^.*: /', '', $event->text),
            $events,
        ));
    }

    /**
     * @return void
     */
    public function testLogsOfProceduresAreLeftToTheirStructuredEvents(): void
    {
        $this->log(
            'Dodana zadeva Elektro <a href="/projects/projects-authorities/view/d0d59a31-6de7-4eb4-8230-ca09113a7fe6">Zgodovina</a>',
            '2026-10-20 10:00:00',
        );
        $this->log('Navaden vnos', '2026-10-20 11:00:00');

        $events = $this->collector->collect(self::PROJECT, null, 10);

        $this->assertCount(1, $events);
        $this->assertStringContainsString('Navaden vnos', $events[0]->text);
    }

    /**
     * @return void
     */
    public function testSummaryOfLinkedDocumentIsAdded(): void
    {
        $documentId = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6';
        $analysis = $this->getTableLocator()->get('LogsAnalysis');
        $analysis->saveOrFail($analysis->newEntity([
            'event_id' => $documentId,
            'summary' => 'Odločba UE o gradbenem dovoljenju',
        ]));
        $this->log('Dokument <a href="/documents/documents/view/' . $documentId . '">Odločba</a>', '2026-10-20 10:00:00');

        $events = $this->collector->collect(self::PROJECT, null, 10);

        $this->assertStringContainsString('Povzetek (AI): Odločba UE o gradbenem dovoljenju', $events[0]->text);
    }

    /**
     * @return void
     */
    public function testLongTextIsCut(): void
    {
        $this->log(str_repeat('x', 6000), '2026-10-20 10:00:00');

        $events = $this->collector->collect(self::PROJECT, null, 10);

        $this->assertLessThan(4300, mb_strlen($events[0]->text));
        $this->assertStringContainsString('[…]', $events[0]->text);
    }

    /**
     * @return void
     */
    public function testTasksCommentsMilestonesAndAuthorityEvents(): void
    {
        $milestones = $this->getTableLocator()->get('Projects.ProjectsMilestones');
        $milestone = $milestones->newEntity(['project_id' => self::PROJECT, 'title' => 'Gradbeno dovoljenje']);
        $milestone->date_due = new DateTime('2026-12-01');
        $milestones->saveOrFail($milestone);

        $tasks = $this->getTableLocator()->get('Projects.ProjectsTasks');
        $task = $tasks->newEntity([
            'project_id' => self::PROJECT,
            'milestone_id' => $milestone->id,
            'title' => 'Pridobiti mnenje elektro',
            'descript' => '<p>Poklicati distributerja</p>',
        ]);
        $tasks->saveOrFail($task, ['checkRules' => false]);

        $comments = $this->getTableLocator()->get('Projects.ProjectsTasksComments');
        $comment = $comments->newEntity(['task_id' => $task->id, 'user_id' => USER_ADMIN, 'descript' => 'Poklical sem']);
        $comments->saveOrFail($comment, ['checkRules' => false]);

        $cases = $this->getTableLocator()->get('Projects.ProjectsAuthorityCases');
        $case = $cases->newEmptyEntity();
        $case->project_id = self::PROJECT;
        $case->contact_id = COMPANY_FIRST;
        $case->title = 'Elektro';
        $case->revision = Text::uuid();
        $cases->saveOrFail($case, ['checkRules' => false]);

        $authorityEvents = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents');
        $authorityEvent = $authorityEvents->newEmptyEntity();
        $authorityEvent->case_id = $case->id;
        $authorityEvent->user_id = USER_ADMIN;
        $authorityEvent->kind = 'request';
        $authorityEvent->event_date = new DateTime('2026-10-20');
        $authorityEvent->date_due = new DateTime('2026-11-15');
        $authorityEvent->descript = 'Manjka PGD';
        $authorityEvent->sequence = 1;
        $authorityEvents->saveOrFail($authorityEvent, ['checkRules' => false]);

        $events = $this->collector->collect(self::PROJECT, null, 20);

        $byModel = [];
        foreach ($events as $event) {
            $byModel[$event->model] = $event->text;
        }
        $this->assertEqualsCanonicalizing(
            ['AuthorityEvent', 'Milestone', 'Task', 'TaskComment'],
            array_keys($byModel),
        );
        $this->assertStringContainsString(sprintf('Naloga #%d »Pridobiti mnenje elektro«: odprta', $task->no), $byModel['Task']);
        $this->assertStringContainsString('mejnik »Gradbeno dovoljenje«', $byModel['Task']);
        $this->assertStringContainsString('Poklicati distributerja', $byModel['Task']);
        $this->assertStringContainsString(sprintf('Komentar k nalogi #%d', $task->no), $byModel['TaskComment']);
        $this->assertStringContainsString('Poklical sem', $byModel['TaskComment']);
        $this->assertStringContainsString('Mejnik »Gradbeno dovoljenje«, rok 01.12.2026', $byModel['Milestone']);
        $this->assertStringContainsString('(Elektro)', $byModel['AuthorityEvent']);
        $this->assertStringContainsString('rok 15.11.2026', $byModel['AuthorityEvent']);
        $this->assertStringContainsString('Manjka PGD', $byModel['AuthorityEvent']);
        $this->assertSame([], $this->collector->collect(self::OTHER_PROJECT, null, 20));
    }
}
