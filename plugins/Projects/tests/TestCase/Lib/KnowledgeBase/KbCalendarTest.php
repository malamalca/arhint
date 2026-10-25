<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Projects\Event\KnowledgeBaseCalendarEvents;
use Projects\Lib\KnowledgeBase\KbCalendar;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbOperationApplier;
use Projects\Lib\KnowledgeBase\KbRenderer;
use Projects\Lib\KnowledgeBase\KbService;

/**
 * Deadlines of the knowledge base in personal calendars.
 */
class KbCalendarTest extends TestCase
{
    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';

    /**
     * @var array<int, string>
     */
    protected array $fixtures = [
        'app.Users',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsUsers',
        'plugin.Projects.ProjectsKbRevisions',
        'plugin.Projects.ProjectsKbCalendarLinks',
        'plugin.Calendar.Events',
    ];

    private KnowledgeBaseCalendarEvents $listener;

    private KbService $service;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Router::reload();
        Router::createRouteBuilder('/')->plugin('Projects', ['path' => '/projects'], function (RouteBuilder $builder): void {
            $builder->fallbacks();
        });
        // As in the application, the listener follows every write of the knowledge base.
        $this->listener = new KnowledgeBaseCalendarEvents();
        EventManager::instance()->on($this->listener);
        $this->service = new KbService();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        EventManager::instance()->off($this->listener);
        parent::tearDown();
    }

    /**
     * @param string $date Date of the deadline.
     * @param string $text Text of the deadline.
     * @return string Id of the deadline.
     */
    private function addDeadline(string $date = '2026-11-15', string $text = 'Dopolnitev vloge'): string
    {
        $this->service->apply(self::PROJECT, [
            ['op' => 'add_item', 'section' => 'deadlines', 'date' => $date, 'text' => $text],
        ], KbOperationApplier::BATCH);
        $doc = KbDocument::parse((string)$this->getTableLocator()->get('Projects.Projects')->get(self::PROJECT)->descript);

        return $doc->items('deadlines')[0]->id;
    }

    /**
     * @return \App\Model\Entity\User
     */
    private function admin(): object
    {
        return $this->getTableLocator()->get('Users')->get(USER_ADMIN);
    }

    /**
     * @return \Cake\ORM\Table
     */
    private function links(): object
    {
        return $this->getTableLocator()->get('Projects.ProjectsKbCalendarLinks');
    }

    /**
     * @return \Cake\ORM\Table
     */
    private function events(): object
    {
        return $this->getTableLocator()->get('Calendar.Events');
    }

    /**
     * @return void
     */
    public function testSubscribeCreatesOneManagedAllDayEntry(): void
    {
        $item = $this->addDeadline();
        $calendar = new KbCalendar();

        $calendar->subscribe(self::PROJECT, $item, $this->admin());
        $calendar->subscribe(self::PROJECT, $item, $this->admin());

        $this->assertSame(1, $this->links()->find()->count());
        $link = $this->links()->find()->firstOrFail();
        $entry = $this->events()->get($link->event_id);
        $this->assertSame(USER_ADMIN, $entry->calendar_id);
        $this->assertTrue($entry->all_day);
        $this->assertSame('2026-11-15', $entry->dat_start->format('Y-m-d'));
        $this->assertStringContainsString('Dopolnitev vloge', $entry->title);
        $this->assertStringContainsString('#kb-roki', $entry->body);
        $this->assertSame([$item => true], $calendar->linkedItems(self::PROJECT, USER_ADMIN));
    }

    /**
     * @return void
     */
    public function testEntryFollowsTheDeadlineAndGoesWhenItIsClosedOrRemoved(): void
    {
        $item = $this->addDeadline();
        (new KbCalendar())->subscribe(self::PROJECT, $item, $this->admin());
        $link = $this->links()->find()->firstOrFail();

        $this->service->apply(self::PROJECT, [
            ['op' => 'update_item', 'id' => $item, 'date' => '2026-11-20', 'text' => 'Dopolnitev vloge na UE'],
        ], KbOperationApplier::CHAT, USER_ADMIN);
        $entry = $this->events()->get($link->event_id);
        $this->assertSame('2026-11-20', $entry->dat_start->format('Y-m-d'));
        $this->assertStringContainsString('Dopolnitev vloge na UE', $entry->title);

        $this->service->apply(self::PROJECT, [['op' => 'resolve_item', 'id' => $item]], KbOperationApplier::CHAT, USER_ADMIN);
        $this->assertFalse($this->events()->exists(['id' => $link->event_id]));
        $this->assertSame(0, $this->links()->find()->count());

        // Reopening does not bring the entry back; the user has to opt in again.
        $this->service->apply(self::PROJECT, [
            ['op' => 'update_item', 'id' => $item, 'status' => 'open'],
        ], KbOperationApplier::CHAT, USER_ADMIN);
        $this->assertSame(0, $this->links()->find()->count());
    }

    /**
     * @return void
     */
    public function testRemovedDeadlineTakesItsEntryAway(): void
    {
        $item = $this->addDeadline();
        (new KbCalendar())->subscribe(self::PROJECT, $item, $this->admin());
        $entryId = $this->links()->find()->firstOrFail()->event_id;

        $this->service->apply(self::PROJECT, [['op' => 'remove_item', 'id' => $item]], KbOperationApplier::CHAT, USER_ADMIN);

        $this->assertFalse($this->events()->exists(['id' => $entryId]));
        $this->assertSame(0, $this->links()->find()->count());
    }

    /**
     * @return void
     */
    public function testUnsubscribeRemovesLinkAndEntry(): void
    {
        $item = $this->addDeadline();
        $calendar = new KbCalendar();
        $calendar->subscribe(self::PROJECT, $item, $this->admin());
        $entryId = $this->links()->find()->firstOrFail()->event_id;

        $calendar->unsubscribe(self::PROJECT, $item, USER_ADMIN);
        $calendar->unsubscribe(self::PROJECT, $item, USER_ADMIN);

        $this->assertFalse($this->events()->exists(['id' => $entryId]));
        $this->assertSame([], $calendar->linkedItems(self::PROJECT, USER_ADMIN));
    }

    /**
     * @return void
     */
    public function testOnlyAnOpenDeadlineWithADateCanBeAdded(): void
    {
        $this->service->apply(self::PROJECT, [
            ['op' => 'add_item', 'section' => 'warnings', 'text' => 'Opozorilo'],
            ['op' => 'add_item', 'section' => 'deadlines', 'date' => '2026-11-15', 'text' => 'Zaprt rok'],
        ], KbOperationApplier::BATCH);
        $doc = KbDocument::parse((string)$this->getTableLocator()->get('Projects.Projects')->get(self::PROJECT)->descript);
        $warning = $doc->items('warnings')[0]->id;
        $closed = $doc->items('deadlines')[0]->id;
        $this->service->apply(self::PROJECT, [['op' => 'resolve_item', 'id' => $closed]], KbOperationApplier::CHAT, USER_ADMIN);

        foreach ([$warning, $closed, 'd-0000'] as $id) {
            try {
                (new KbCalendar())->subscribe(self::PROJECT, $id, $this->admin());
                $this->fail("Subscribed to $id.");
            } catch (InvalidArgumentException) {
                $this->assertSame(0, $this->links()->find()->count());
            }
        }
    }

    /**
     * @return void
     */
    public function testLinkIsDroppedWhenTheUserLosesAccess(): void
    {
        $item = $this->addDeadline();
        $members = $this->getTableLocator()->get('Projects.ProjectsUsers');
        $member = $members->newEntity(['project_id' => self::PROJECT, 'user_id' => USER_COMMON]);
        $members->saveOrFail($member);
        (new KbCalendar())->subscribe(self::PROJECT, $item, $this->getTableLocator()->get('Users')->get(USER_COMMON));
        $link = $this->links()->find()->firstOrFail();
        $this->assertNotNull($link->event_id);

        $members->deleteOrFail($member);

        $this->assertFalse($this->links()->exists(['id' => $link->id]));
        $this->assertFalse($this->events()->exists(['id' => $link->event_id]));
    }

    /**
     * @return void
     */
    public function testManagedEntryCanBeChangedOnlyThroughTheKnowledgeBase(): void
    {
        $item = $this->addDeadline();
        (new KbCalendar())->subscribe(self::PROJECT, $item, $this->admin());
        $entry = $this->events()->get($this->links()->find()->firstOrFail()->event_id);

        $entry->title = 'Samovoljna sprememba';
        $this->assertFalse($this->events()->save($entry));
        $this->assertFalse($this->events()->delete($entry));
        $this->assertTrue($this->events()->exists(['id' => $entry->id]));
    }

    /**
     * @return void
     */
    public function testEditingTheEntryOpensTheKnowledgeBase(): void
    {
        $item = $this->addDeadline();
        (new KbCalendar())->subscribe(self::PROJECT, $item, $this->admin());
        $entryId = $this->links()->find()->firstOrFail()->event_id;

        $event = EventManager::instance()->dispatch(new Event('Calendar.Events.editSource', $this, [
            'eventId' => $entryId,
        ]));

        $this->assertSame('Projects', $event->getResult()['plugin']);
        $this->assertSame(self::PROJECT, $event->getResult()[0]);
        $this->assertSame('kb-roki', $event->getResult()['#']);
    }

    /**
     * @return void
     */
    public function testRendererMarksOnlyOpenDatedDeadlinesAndTheLinkedOnes(): void
    {
        $doc = KbDocument::empty();
        $open = $doc->addItem('deadlines', 'Prvi', ['date' => '2026-11-01']);
        $linked = $doc->addItem('deadlines', 'Drugi', ['date' => '2026-11-02']);
        $done = $doc->addItem('deadlines', 'Tretji', ['date' => '2026-11-03', 'status' => 'done']);

        $html = (new KbRenderer())->render($doc, [$linked->id => true]);

        $this->assertSame(2, substr_count($html, 'class="kb-calendar"'));
        $this->assertStringContainsString('data-item="' . $open->id . '" data-linked="0"', $html);
        $this->assertStringContainsString('data-item="' . $linked->id . '" data-linked="1"', $html);
        $this->assertStringNotContainsString($done->id, $html);
        $this->assertStringContainsString('<h2 id="kb-roki">Roki</h2>', $html);
        $this->assertStringNotContainsString('kb-calendar', (new KbRenderer())->render($doc));
    }
}
