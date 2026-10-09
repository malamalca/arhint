<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\AppEvents;
use App\View\Helper\LilHelper;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\Queue\QueueManager;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Documents\Model\Entity\Document;
use Documents\Model\Table\DocumentsTable;
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
    private ?string $queueDir = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->appEvents = new AppEvents();
    }

    public function tearDown(): void
    {
        if ($this->queueDir !== null) {
            QueueManager::drop('default');
            QueueManager::setConfig('default', (array)Configure::read('Queue.default'));

            $this->queueDir = null;
        }

        parent::tearDown();
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

    // -------------------------------------------------------------------------
    // updateModelAttachments — AI analysis of new documents
    // -------------------------------------------------------------------------

    /**
     * A new document queues an AiProcessLog job carrying the ids of its attachments.
     */
    public function testNewDocumentQueuesAiAnalysis(): void
    {
        $queueDir = TMP . 'tests_queue' . DS;
        $this->configureTestQueue($queueDir);

        $document = new Document([
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'user_id' => USER_ADMIN,
            'project_id' => 'p-1',
            'no' => 'P.1051',
            'title' => 'Poročilo',
            'descript' => '<p>Opis</p>',
            'dat_issue' => new Date('2026-10-01'),
        ]);

        $event = new Event('Model.afterSave', new DocumentsTable());
        $this->appEvents->updateModelAttachments($event, $document, new ArrayObject());

        $messages = $this->readQueuedMessages($queueDir);
        $this->assertCount(1, $messages);

        $data = $messages[0]['data'][0] ?? $messages[0]['data'];
        $this->assertSame(USER_ADMIN, $data['user_id']);
        $this->assertSame('Document', $data['entity']['model']);
        $this->assertSame('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', $data['entity']['foreign_id']);
        $this->assertSame('p-1', $data['entity']['project_id']);
        $this->assertSame([], $data['entity']['attachment_ids']);
        $this->assertStringContainsString('No: P.1051', $data['entity']['descript']);
        $this->assertStringContainsString('Description: Opis', $data['entity']['descript']);
    }

    /**
     * Saving an existing document does not queue another analysis.
     */
    public function testExistingDocumentDoesNotQueueAiAnalysis(): void
    {
        $queueDir = TMP . 'tests_queue' . DS;
        $this->configureTestQueue($queueDir);

        $document = new Document(['id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6', 'user_id' => USER_ADMIN]);
        $document->setNew(false);

        $event = new Event('Model.afterSave', new DocumentsTable());
        $this->appEvents->updateModelAttachments($event, $document, new ArrayObject());

        $this->assertCount(0, $this->readQueuedMessages($queueDir));
    }

    /**
     * Point the default queue to a temporary file transport.
     *
     * @param string $queueDir Directory for queue files.
     * @return void
     */
    private function configureTestQueue(string $queueDir): void
    {
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0777, true);
        }
        // The file transport keeps its lock file open until the process ends, so the directory
        // is shared and only the message files are removed.
        foreach (glob($queueDir . '*') ?: [] as $file) {
            if (!str_ends_with($file, '.lock')) {
                unlink($file);
            }
        }
        $this->queueDir = $queueDir;

        QueueManager::drop('default');
        QueueManager::setConfig('default', [
            'url' => ['transport' => ['dsn' => 'file:', 'path' => $queueDir]],
            'queue' => 'default',
        ]);
    }

    /**
     * Read messages from the test queue directory.
     *
     * @param string $queueDir Directory for queue files.
     * @return array<int, array<string, mixed>>
     */
    private function readQueuedMessages(string $queueDir): array
    {
        $messages = [];
        foreach (glob($queueDir . '*') ?: [] as $file) {
            if (str_ends_with($file, '.lock')) {
                continue;
            }
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                // Enqueue FS format: "|{...envelope...}" followed by the JSON body.
                $start = strpos($line, '{"');
                $envelope = $start === false ? null : json_decode(substr($line, $start), true);
                if (!is_array($envelope) || !isset($envelope['body'])) {
                    continue;
                }
                $body = json_decode((string)$envelope['body'], true);
                if (is_array($body)) {
                    $messages[] = $body;
                }
            }
        }

        return $messages;
    }
}
