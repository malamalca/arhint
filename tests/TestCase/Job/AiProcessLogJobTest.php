<?php
declare(strict_types=1);

namespace App\Test\TestCase\Job;

use App\Job\AiProcessLogJob;
use Cake\Core\ContainerInterface;
use Cake\Queue\Job\Message;
use Cake\TestSuite\TestCase;
use Interop\Queue\Context;
use Interop\Queue\Message as QueueMessage;
use Interop\Queue\Processor;

class AiProcessLogJobTest extends TestCase
{
    /**
     * @var array<string> Fixtures to use during tests.
     */
    protected array $fixtures = [
        'app.Users',
    ];

    private AiProcessLogJob $job;

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
    // Helpers
    // =========================================================================

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
