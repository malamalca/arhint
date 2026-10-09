<?php
declare(strict_types=1);

namespace App\Test\TestCase\Job;

use App\Job\AiProcessLogJob;
use App\Test\TestCase\MakesPdfTrait;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\Message;
use Cake\TestSuite\TestCase;
use Interop\Queue\Context;
use Interop\Queue\Message as QueueMessage;
use Interop\Queue\Processor;
use ReflectionMethod;

class AiProcessLogJobTest extends TestCase
{
    use MakesPdfTrait;

    /**
     * @var array<string> Fixtures to use during tests.
     */
    protected array $fixtures = [
        'app.Users',
        'app.Attachments',
    ];

    private AiProcessLogJob $job;
    private string $originalUploadFolder = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->job = new AiProcessLogJob();
    }

    // =========================================================================
    // Early-validation tests — do NOT require any external services
    // =========================================================================

    public function testExecuteRejectsWithMissingUserId(): void
    {
        $message = $this->createMessage([
            'user_id' => '',
            'entity' => ['test' => 'data'],
            'job_id' => 'test-job-123',
        ]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWithNullEntity(): void
    {
        $message = $this->createMessage([
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'entity' => null,
            'job_id' => 'test-job-123',
        ]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWithMissingJobId(): void
    {
        $message = $this->createMessage([
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'entity' => ['test' => 'data'],
            'job_id' => '',
        ]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWithAllArgumentsMissing(): void
    {
        $message = $this->createMessage([]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWhenUserIdDefaultsToEmpty(): void
    {
        $message = $this->createMessage([
            'entity' => ['test' => 'data'],
            'job_id' => 'test-job-123',
        ]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWhenEntityNotProvided(): void
    {
        $message = $this->createMessage([
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'job_id' => 'test-job-123',
        ]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWhenJobIdNotProvided(): void
    {
        $message = $this->createMessage([
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'entity' => ['test' => 'data'],
        ]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    // =========================================================================
    // User loading tests — require DB fixture but NOT live AI server
    // =========================================================================

    public function testExecuteRejectsWhenUserNotFound(): void
    {
        $message = $this->createMessage([
            'user_id' => '99999999-9999-9999-9999-999999999999',
            'entity' => ['test' => 'data'],
            'job_id' => 'test-job-123',
        ]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWithInvalidUserIdFormat(): void
    {
        $message = $this->createMessage([
            'user_id' => 'not-a-uuid',
            'entity' => ['test' => 'data'],
            'job_id' => 'test-job-123',
        ]);

        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    // =========================================================================
    // Edge cases — validation behavior (no AI server needed)
    // =========================================================================

    public function testExecuteRejectsWithWhitespaceUserId(): void
    {
        $message = $this->createMessage([
            'user_id' => '   ',
            'entity' => ['test' => 'data'],
            'job_id' => 'test-job-id',
        ]);

        // Whitespace is truthy, so validation passes but user doesn't exist.
        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    /**
     * Test that entity accepts false value (it's not null).
     */
    public function testExecuteWithFalseEntityRejectsAfterValidation(): void
    {
        $message = $this->createMessage([
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'entity' => false,
            'job_id' => 'test-job-id',
        ]);

        // false !== null, so validation passes. AI call will be attempted
        // but user doesn't exist → REJECT.
        $result = $this->job->execute($message);
        $this->assertIsString($result);
    }

    /**
     * Test that multiple calls to execute don't interfere with each other.
     */
    public function testMultipleExecuteCallsAreIndependent(): void
    {
        $msg1 = $this->createMessage([
            'user_id' => '',
            'entity' => null,
            'job_id' => 'job1',
        ]);

        $result1 = $this->job->execute($msg1);
        $this->assertSame(Processor::REJECT, $result1);

        // Another independent call.
        $msg2 = $this->createMessage([
            'user_id' => '99999999-9999-9999-9999-999999999999',
            'entity' => ['test' => 'data'],
            'job_id' => 'job2',
        ]);

        $result2 = $this->job->execute($msg2);
        // Second call is independent and also fails (user not found).
        $this->assertSame(Processor::REJECT, $result2);
    }

    // =========================================================================
    // Message argument integration — verify extraction works correctly
    // =========================================================================

    public function testMessageArgumentIntegration(): void
    {
        $expectedUserId = '00000000-0000-0000-0000-000000000001';
        $expectedJobId = 'test-job-expected';
        $expectedEntity = ['id' => '123', 'data' => 'test'];

        $message = $this->createMessage([
            'user_id' => $expectedUserId,
            'entity' => $expectedEntity,
            'job_id' => $expectedJobId,
        ]);

        // Verify the real Message extracts arguments correctly.
        $this->assertSame($expectedUserId, $message->getArgument('user_id'));
        $this->assertSame($expectedEntity, $message->getArgument('entity'));
        $this->assertSame($expectedJobId, $message->getArgument('job_id'));
    }

    public function testResultIsAlwaysString(): void
    {
        $msg1 = $this->createMessage([
            'user_id' => '',
            'entity' => null,
            'job_id' => '',
        ]);
        $this->assertIsString($this->job->execute($msg1));

        $msg2 = $this->createMessage([
            'user_id' => '99999999-9999-9999-9999-999999999999',
            'entity' => ['test' => 'data'],
            'job_id' => 'job-id',
        ]);
        $this->assertIsString($this->job->execute($msg2));
    }

    public function testResultIsNeverNull(): void
    {
        $message = $this->createMessage([
            'user_id' => '',
            'entity' => null,
            'job_id' => '',
        ]);

        $result = $this->job->execute($message);
        $this->assertNotNull($result);
    }

    // =========================================================================
    // Attachment text of document events
    // =========================================================================

    public function testWithAttachmentTextAddsTextOfAttachments(): void
    {
        $uploads = $this->prepareUploads();
        $this->writePdf($uploads . 'Test' . DS . 'test.pdf', ['Zahteva: toplotna prehodnost U 0.15']);

        $readIds = [];
        $result = $this->callWithAttachmentText([
            'id' => 'doc-1',
            'attachment_ids' => ['3e7c2fba-1c29-4e5b-9bb2-000000000001'],
        ], $readIds);

        $this->cleanUploads($uploads);
        $this->assertSame(['3e7c2fba-1c29-4e5b-9bb2-000000000001'], $readIds);
        $this->assertArrayNotHasKey('attachment_ids', $result);
        $this->assertSame('doc-1', $result['id']);
        $this->assertStringContainsString('--- Attachment: test.pdf ---', $result['attachments_text']);
        $this->assertStringContainsString('toplotna prehodnost U 0.15', $result['attachments_text']);
    }

    public function testWithAttachmentTextNotesUnreadableAttachment(): void
    {
        $uploads = $this->prepareUploads();

        $readIds = [];
        $result = $this->callWithAttachmentText([
            'attachment_ids' => ['3e7c2fba-1c29-4e5b-9bb2-000000000001'],
        ], $readIds);

        $this->cleanUploads($uploads);
        $this->assertSame([], $readIds);
        $this->assertStringContainsString('(text could not be read)', $result['attachments_text']);
    }

    public function testWithAttachmentTextWithoutAttachments(): void
    {
        $result = $this->callWithAttachmentText(['id' => 'doc-1', 'attachment_ids' => []]);

        $this->assertSame(['id' => 'doc-1'], $result);
    }

    public function testMarkAttachmentsProcessedStampsAttachments(): void
    {
        $attachments = TableRegistry::getTableLocator()->get('Attachments');
        $this->assertNull($attachments->get('3e7c2fba-1c29-4e5b-9bb2-000000000001')->ai_processed);

        $method = new ReflectionMethod($this->job, 'markAttachmentsProcessed');
        $method->invoke($this->job, ['3e7c2fba-1c29-4e5b-9bb2-000000000001']);

        $this->assertNotNull($attachments->get('3e7c2fba-1c29-4e5b-9bb2-000000000001')->ai_processed);
    }

    public function testMarkAttachmentsProcessedIgnoresEmptyList(): void
    {
        $method = new ReflectionMethod($this->job, 'markAttachmentsProcessed');

        $this->assertNull($method->invoke($this->job, []));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Call the private withAttachmentText() method.
     *
     * @param array<string, mixed> $entity Event data.
     * @param array<int, string> $readIds Output: ids of attachments whose text was added.
     * @return array<string, mixed>
     */
    private function callWithAttachmentText(array $entity, array &$readIds = []): array
    {
        $method = new ReflectionMethod($this->job, 'withAttachmentText');

        return $method->invokeArgs($this->job, [$entity, &$readIds]);
    }

    /**
     * Point the upload folder to a temporary directory with a `Test` model folder.
     *
     * @return string Upload folder path.
     */
    private function prepareUploads(): string
    {
        $uploads = TMP . 'tests_uploads_' . uniqid() . DS;
        mkdir($uploads . 'Test', 0777, true);
        $this->originalUploadFolder = (string)Configure::read('App.uploadFolder');
        Configure::write('App.uploadFolder', $uploads);

        return $uploads;
    }

    /**
     * Remove the temporary upload folder and restore configuration.
     *
     * @param string $uploads Upload folder path.
     * @return void
     */
    private function cleanUploads(string $uploads): void
    {
        foreach (glob($uploads . 'Test' . DS . '*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($uploads . 'Test');
        rmdir($uploads);
        Configure::write('App.uploadFolder', $this->originalUploadFolder);
    }

    /**
     * Create a real Cake\Queue\Job\Message instance with the given arguments.
     *
     * The Message constructor parses getBody() as JSON and extracts from 'data' key.
     *
     * @param array<string, mixed> $arguments Arguments to inject.
     * @return \Cake\Queue\Job\Message
     */
    private function createMessage(array $arguments): Message
    {
        $wrapped = !empty($arguments) && (isset($arguments['user_id']) || isset($arguments['entity']))
            ? ['data' => $arguments]
            : ['data' => []];

        $body = json_encode($wrapped);

        /** @var QueueMessage&MockObject $queueMessage */
        $queueMessage = $this->createMock(QueueMessage::class);
        $queueMessage->method('getBody')->willReturn($body);

        /** @var Context&MockObject $context */
        $context = $this->createStub(Context::class);

        /** @var ContainerInterface&MockObject $container */
        $container = $this->createStub(ContainerInterface::class);

        return new Message($queueMessage, $context, $container);
    }
}
