<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Projects\Lib\KnowledgeBase\KbEventCollector;

/**
 * Documents, invoices and travel orders of a project as events of the knowledge base update.
 */
class KbEventCollectorDocumentsTest extends TestCase
{
    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';
    private const OTHER_PROJECT = '4dd53305-9715-4be4-b169-20defe113d2b';
    private const DOCUMENT = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6';
    private const RECEIVED_INVOICE = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5';
    private const TRAVEL_ORDER = 'a1b23456-7890-4bcd-8f12-345678901234';

    /**
     * @var array<int, string>
     */
    public array $fixtures = [
        'app.Users',
        'app.Logs',
        'plugin.Projects.LogsAnalysis',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsKbSources',
        'plugin.Documents.Documents',
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.Invoices',
        'plugin.Documents.TravelOrders',
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
    }

    /**
     * @param string $table Table of the Documents plugin.
     * @param string $id Row id.
     * @param string $projectId Project to put the row in.
     * @return void
     */
    private function assign(string $table, string $id, string $projectId = self::PROJECT): void
    {
        $this->getTableLocator()->get('Documents.' . $table)->updateAll(['project_id' => $projectId], ['id' => $id]);
    }

    /**
     * @return array<string, \Projects\Lib\KnowledgeBase\KbEvent> Events by reference.
     */
    private function events(?DateTime $since = null): array
    {
        $events = [];
        foreach ($this->collector->collect(self::PROJECT, $since, 50) as $event) {
            $events[$event->ref()] = $event;
        }

        return $events;
    }

    /**
     * @return void
     */
    public function testDocumentsInvoicesAndTravelOrdersWithoutALogAreEvents(): void
    {
        $this->assign('Documents', self::DOCUMENT);
        $this->assign('Invoices', self::RECEIVED_INVOICE);
        $this->assign('TravelOrders', self::TRAVEL_ORDER);

        $events = $this->events();

        $this->assertCount(3, $events);
        $this->assertStringContainsString('Dokument', $events['document:' . self::DOCUMENT]->text);
        $this->assertStringContainsString('First issued document', $events['document:' . self::DOCUMENT]->text);
        $this->assertStringContainsString('Prejet račun R-cust-012', $events['invoice:' . self::RECEIVED_INVOICE]->text);
        $this->assertStringContainsString('Potni nalog TO-01', $events['travel_order:' . self::TRAVEL_ORDER]->text);
        $this->assertStringContainsString('Business trip to Zagreb', $events['travel_order:' . self::TRAVEL_ORDER]->text);
        $this->assertSame('Document', $events['document:' . self::DOCUMENT]->model);
    }

    /**
     * @return void
     */
    public function testRowsOfOtherProjectsAndWithoutProjectAreIgnored(): void
    {
        $this->assign('Documents', self::DOCUMENT, self::OTHER_PROJECT);
        $this->assign('Invoices', self::RECEIVED_INVOICE, self::OTHER_PROJECT);

        $this->assertSame([], $this->events());
    }

    /**
     * @return void
     */
    public function testDocumentThatALogLinksToIsNotGivenTwice(): void
    {
        $this->assign('Documents', self::DOCUMENT);
        $this->assign('Invoices', self::RECEIVED_INVOICE);
        $logs = $this->getTableLocator()->get('App.Logs');
        $log = $logs->newEntity([
            'id' => Text::uuid(),
            'model' => 'Project',
            'foreign_id' => self::PROJECT,
            'user_id' => USER_ADMIN,
            'action' => 'Comment',
            'descript' => 'Dodeljen dokument <a href="/documents/documents/view/' . self::DOCUMENT . '">D-1</a>',
        ]);
        $logs->saveOrFail($log, ['checkRules' => false]);

        $events = $this->events();

        $this->assertArrayHasKey('log:' . $log->id, $events);
        $this->assertArrayNotHasKey('document:' . self::DOCUMENT, $events);
        $this->assertArrayHasKey('invoice:' . self::RECEIVED_INVOICE, $events);
    }

    /**
     * @return void
     */
    public function testAiSummaryIsAddedAndChangedRowsComeBackWhenTheyChange(): void
    {
        $this->assign('Documents', self::DOCUMENT);
        $analysis = $this->getTableLocator()->get('LogsAnalysis');
        $analysis->saveOrFail($analysis->newEntity([
            'event_id' => self::DOCUMENT,
            'summary' => 'Odločba UE o gradbenem dovoljenju',
        ]));

        $first = $this->events()['document:' . self::DOCUMENT];
        $this->assertStringContainsString('Povzetek (AI): Odločba UE o gradbenem dovoljenju', $first->text);

        // Modified again: the event has a new key, so the update reads it again.
        $this->getTableLocator()->get('Documents.Documents')
            ->updateAll(['modified' => new DateTime('+1 hour')], ['id' => self::DOCUMENT]);
        $second = $this->events()['document:' . self::DOCUMENT];
        $this->assertNotSame($first->key(), $second->key());

        // Nothing changed since the last run: nothing to read.
        $this->assertSame([], $this->events(new DateTime('+2 hours')));
    }
}
