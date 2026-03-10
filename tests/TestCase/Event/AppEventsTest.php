<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\AppEvents;
use App\View\Helper\LilHelper;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use stdClass;

/**
 * App\Event\AppEvents Test Case
 *
 * Only the methods that can be exercised without imap/email transport are tested:
 *   - implementedEvents()
 *   - marshalDurationAndAttachments() (duration marshalling portion)
 *   - addAttachmentFormLines()
 */
class AppEventsTest extends TestCase
{
    protected AppEvents $appEvents;

    public function setUp(): void
    {
        parent::setUp();
        $this->appEvents = new AppEvents();
    }

    // -------------------------------------------------------------------------
    // implementedEvents
    // -------------------------------------------------------------------------

    /**
     * implementedEvents() maps exactly the expected 5 events.
     */
    public function testImplementedEvents(): void
    {
        $events = $this->appEvents->implementedEvents();

        $this->assertIsArray($events);
        $this->assertArrayHasKey('App.dashboard', $events);
        $this->assertArrayHasKey('Model.beforeMarshal', $events);
        $this->assertArrayHasKey('Model.afterSave', $events);
        $this->assertArrayHasKey('App.Form.Documents.Invoices.edit', $events);
        $this->assertArrayHasKey('App.Form.Documents.Documents.edit', $events);
        $this->assertCount(5, $events);
    }

    /**
     * Each event maps to the correct handler method name.
     */
    public function testImplementedEventsHandlers(): void
    {
        $events = $this->appEvents->implementedEvents();

        $this->assertEquals('dashboardPanels', $events['App.dashboard']);
        $this->assertEquals('marshalDurationAndAttachments', $events['Model.beforeMarshal']);
        $this->assertEquals('updateModelAttachments', $events['Model.afterSave']);
        $this->assertEquals('addAttachmentFormLines', $events['App.Form.Documents.Invoices.edit']);
        $this->assertEquals('addAttachmentFormLines', $events['App.Form.Documents.Documents.edit']);
    }

    // -------------------------------------------------------------------------
    // marshalDurationAndAttachments — duration portion
    // -------------------------------------------------------------------------

    /**
     * Duration fields are marshalled to integer seconds even when the event
     * subject is an ordinary Table (not DocumentsTable / InvoicesTable).
     */
    public function testMarshalDurationFieldsViaEvent(): void
    {
        $data = new ArrayObject([
            'work_duration' => [
                'hours' => '1',
                'minutes' => '30',
                'duration' => true,
            ],
            'title' => 'My task',
        ]);

        $options = new ArrayObject([]);

        // Use a generic Table as the event subject so the Documents/Invoices
        // branch is NOT entered — we are only testing the duration conversion.
        $genericTable = new Table();

        $event = new Event('Model.beforeMarshal', $genericTable);
        $this->appEvents->marshalDurationAndAttachments($event, $data, $options);

        // 1h 30m = 5400 seconds
        $this->assertSame(5400, $data['work_duration']);
        // Other fields must not be modified
        $this->assertSame('My task', $data['title']);
    }

    /**
     * Fields without the duration flag are not touched.
     */
    public function testMarshalNonDurationFieldsUntouched(): void
    {
        $data = new ArrayObject([
            'title' => 'Report',
            'amount' => 99,
        ]);
        $options = new ArrayObject([]);

        $event = new Event('Model.beforeMarshal', new Table());
        $this->appEvents->marshalDurationAndAttachments($event, $data, $options);

        $this->assertSame('Report', $data['title']);
        $this->assertSame(99, $data['amount']);
    }

    // -------------------------------------------------------------------------
    // addAttachmentFormLines
    // -------------------------------------------------------------------------

    /**
     * addAttachmentFormLines inserts attachment fields before 'submit' in the
     * form lines array.
     */
    public function testAddAttachmentFormLinesInsertsBeforeSubmit(): void
    {
        // We need a View-like object that has a $Lil helper with insertIntoArray().
        // Use an actual View + LilHelper to avoid complex mocking.
        $view = new View();
        $view->loadHelper('Lil', ['className' => LilHelper::class]);

        // addAttachmentFormLines uses property access: $formLines->form['lines']
        $formLines = new stdClass();
        $formLines->form = [
            'lines' => [
                'title' => ['method' => 'control', 'parameters' => ['field' => 'title', 'options' => []]],
                'submit' => '<button type="submit">Save</button>',
            ],
        ];

        // The event name must match 'App.Form.Documents.Invoices.edit' so that
        // the Invoices branch is entered (modelName = 'Invoices').
        $event = new Event('App.Form.Documents.Invoices.edit', $view, [$formLines]);
        $this->appEvents->addAttachmentFormLines($event, $formLines);

        $lines = $formLines->form['lines'];
        $keys = array_keys($lines);

        // Attachment fieldset keys should be present
        $this->assertContains('fs_attachments_start', $keys);
        $this->assertContains('fs_attachments_end', $keys);
        $this->assertContains('file.name.0', $keys);

        // submit must still be the last key
        $this->assertSame('submit', end($keys));

        // attachment keys must appear before submit
        $submitPos = array_search('submit', $keys);
        $attachStartPos = array_search('fs_attachments_start', $keys);
        $this->assertLessThan($submitPos, $attachStartPos);
    }

    /**
     * addAttachmentFormLines is a no-op for unknown model names.
     */
    public function testAddAttachmentFormLinesIgnoresUnknownModel(): void
    {
        $view = new View();
        $view->loadHelper('Lil', ['className' => LilHelper::class]);

        $lines = [
            'title' => 'field',
            'submit' => 'button',
        ];

        // Construct the object the same way the real code accesses it
        $formLines = new stdClass();
        $formLines->form = ['lines' => $lines];

        // Unknown model segment
        $event = new Event('App.Form.Documents.Unknown.edit', $view, [$formLines]);
        $this->appEvents->addAttachmentFormLines($event, $formLines);

        // Lines must be unchanged
        $this->assertSame($lines, $formLines->form['lines']);
    }
}
