<?php
declare(strict_types=1);

namespace App\Test\TestCase\Job;

use App\Job\AiProcessLogJob;
use App\Model\Entity\Log;
use Cake\Core\ContainerInterface;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\Message;
use Cake\TestSuite\TestCase;
use Interop\Queue\Context;
use Interop\Queue\Message as QueueMessage;
use Interop\Queue\Processor;

/**
 * @group live
 */
class AiProcessLogJobLiveTest extends TestCase
{
    private const DEFAULT_AI_LIVE_URL = 'http://192.168.68.58:8080/v1/chat/completions';
    private const DEFAULT_AI_MODEL = 'qwen';

    /** @var string Admin user ID from UsersFixture. */
    private const USER_ID = '048acacf-d87c-4088-a3a7-4bab30f6a040';

    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * @var string Live AI URL.
     */
    private string $liveUrl;

    /**
     * @var string Live AI model.
     */
    private string $model;

    /**
     * @var AiProcessLogJob The job under test.
     */
    private AiProcessLogJob $job;

    /**
     * @var array<string, mixed>|null Cached AI probe result.
     */
    private ?array $aiProbe = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->liveUrl = (string)(getenv('ARHINT_AI_LIVE_URL') ?: self::DEFAULT_AI_LIVE_URL);
        $this->model = (string)(getenv('ARHINT_AI_LIVE_MODEL') ?: self::DEFAULT_AI_MODEL);
        $this->job = new AiProcessLogJob();
    }

    // =========================================================================
    // Full pipeline tests — live AI + Embedding + Qdrant
    // =========================================================================

    /**
     * Test full execute() with live AI server against a Log entity.
     * Verifies the job returns ACK and saves to LogsAnalysis table.
     */
    public function testExecuteWithLiveServerAndLogEntity(): void
    {
        $this->skipIfAiUnavailable();

        // Use a real Log entity from the fixture so storeInQdrant picks up metadata.
        $log = new Log([
            'id' => uniqid('live-log-', true),
            'model' => 'Projects.ProjectsLog',
            'foreign_id' => uniqid('proj-', true),
            'user_id' => self::USER_ID,
            'action' => 'test_live_action',
            'descript' => json_encode(['event' => 'Project log entry for live test']),
        ]);

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => $log,
            'job_id' => 'live-log-job-' . uniqid(),
        ]);

        $result = $this->job->execute($message);
        $this->assertSame(Processor::ACK, $result);

        // Verify LogsAnalysis was saved (event_id should match the log id).
        $logsAnalysisTable = TableRegistry::getTableLocator()->get('LogsAnalysis');
        $analysis = $logsAnalysisTable->find()
            ->where(['event_id' => (string)$log->get('id')])
            ->orderByDesc('created')
            ->first();

        $this->assertNotNull($analysis, 'LogsAnalysis record should exist for the log event.');
        $this->assertNotSame('', (string)$analysis->get('summary'), 'Summary should not be empty.');
    }

    /**
     * Test full execute() with a simple array entity.
     */
    public function testExecuteWithArrayEntityAgainstLiveServer(): void
    {
        $this->skipIfAiUnavailable();

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => [
                'action' => 'login',
                'description' => 'User login event for testing',
            ],
            'job_id' => 'live-array-job-' . uniqid(),
        ]);

        $result = $this->job->execute($message);
        $this->assertSame(Processor::ACK, $result);
    }

    /**
     * Test full execute() with a string entity.
     */
    public function testExecuteWithStringEntityAgainstLiveServer(): void
    {
        $this->skipIfAiUnavailable();

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => 'User logged in from 192.168.1.1',
            'job_id' => 'live-string-job-' . uniqid(),
        ]);

        $result = $this->job->execute($message);
        $this->assertSame(Processor::ACK, $result);
    }

    /**
     * Test full execute() with a complex nested array entity.
     */
    public function testExecuteWithComplexNestedEntityAgainstLiveServer(): void
    {
        $this->skipIfAiUnavailable();

        $entity = [
            'level1' => [
                'level2' => [
                    'level3' => ['data' => 'deep value'],
                ],
            ],
            'array' => [1, 2, 3],
            'mixed' => 'test',
        ];

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => $entity,
            'job_id' => 'live-complex-job-' . uniqid(),
        ]);

        $result = $this->job->execute($message);
        $this->assertSame(Processor::ACK, $result);
    }

    /**
     * Test execute() with __toString entity.
     */
    public function testExecuteWithToStringEntityAgainstLiveServer(): void
    {
        $this->skipIfAiUnavailable();

        $entity = new class ('Custom toString output') {
            private string $text;

            public function __construct(string $text)
            {
                $this->text = $text;
            }

            public function __toString(): string
            {
                return $this->text;
            }
        };

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => $entity,
            'job_id' => 'live-tostring-job-' . uniqid(),
        ]);

        $result = $this->job->execute($message);
        $this->assertSame(Processor::ACK, $result);
    }

    // =========================================================================
    // Early-validation tests — do NOT require live servers
    // =========================================================================

    public function testExecuteRejectsWithMissingUserId(): void
    {
        $message = $this->createMessage([
            'user_id' => '',
            'entity' => ['test' => 'data'],
            'job_id' => 'job-123',
        ]);
        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWithNullEntity(): void
    {
        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => null,
            'job_id' => 'job-123',
        ]);
        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWithMissingJobId(): void
    {
        $message = $this->createMessage([
            'user_id' => self::USER_ID,
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

    // =========================================================================
    // User loading tests — require DB but NOT live AI server
    // =========================================================================

    public function testExecuteRejectsWhenUserNotFound(): void
    {
        $message = $this->createMessage([
            'user_id' => '99999999-9999-9999-9999-999999999999',
            'entity' => ['test' => 'data'],
            'job_id' => 'job-notfound',
        ]);
        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testExecuteRejectsWithInvalidUserIdFormat(): void
    {
        $message = $this->createMessage([
            'user_id' => 'not-a-uuid',
            'entity' => ['test' => 'data'],
            'job_id' => 'job-invalid',
        ]);
        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    // =========================================================================
    // Edge cases — live server required
    // =========================================================================

    public function testExecuteWithEmptyEntity(): void
    {
        $this->skipIfAiUnavailable();

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => [],
            'job_id' => 'live-empty-job-' . uniqid(),
        ]);
        $this->assertSame(Processor::ACK, $this->job->execute($message));
    }

    public function testExecuteWithNumericJobId(): void
    {
        $this->skipIfAiUnavailable();

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => ['test' => 'data'],
            'job_id' => 123456,
        ]);
        $this->assertSame(Processor::ACK, $this->job->execute($message));
    }

    public function testResultIsAlwaysString(): void
    {
        // Reject path.
        $msgReject = $this->createMessage([
            'user_id' => '',
            'entity' => null,
            'job_id' => '',
        ]);
        $this->assertIsString($this->job->execute($msgReject));

        // ACK path (live).
        $this->skipIfAiUnavailable();

        $msgAck = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => ['test' => 'data'],
            'job_id' => 'live-result-job-' . uniqid(),
        ]);
        $this->assertIsString($this->job->execute($msgAck));
    }

    public function testResultIsNeverNull(): void
    {
        $this->skipIfAiUnavailable();

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => ['test' => 'data'],
            'job_id' => 'live-null-job-' . uniqid(),
        ]);
        $this->assertNotNull($this->job->execute($message));
    }

    public function testExecuteRejectsWithWhitespaceUserId(): void
    {
        $message = $this->createMessage([
            'user_id' => '   ',
            'entity' => ['test' => 'data'],
            'job_id' => 'test-job',
        ]);
        $this->assertSame(Processor::REJECT, $this->job->execute($message));
    }

    public function testMultipleExecuteCallsAreIndependent(): void
    {
        // Reject path.
        $msg1 = $this->createMessage([
            'user_id' => '',
            'entity' => null,
            'job_id' => 'job1',
        ]);
        $this->assertSame(Processor::REJECT, $this->job->execute($msg1));

        // ACK path (live).
        $this->skipIfAiUnavailable();

        $msg2 = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => ['test' => 'data'],
            'job_id' => 'live-multi-job-' . uniqid(),
        ]);
        $this->assertSame(Processor::ACK, $this->job->execute($msg2));
    }

    public function testExecuteWithFalseEntity(): void
    {
        $this->skipIfAiUnavailable();

        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => false,
            'job_id' => 'live-false-job-' . uniqid(),
        ]);
        $this->assertSame(Processor::ACK, $this->job->execute($message));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a real Cake\Queue\Job\Message instance with the given arguments.
     */

    /**
     * @param array<string, mixed> $arguments Arguments to inject.
     * @return \Cake\Queue\Job\Message
     */
    private function createMessage(array $arguments): Message
    {
        $wrapped = !empty($arguments) && (isset($arguments['user_id']) || isset($arguments['entity']))
            ? ['data' => $arguments]
            : ['data' => []];

        $body = json_encode($wrapped);

        $queueMessage = $this->createMock(QueueMessage::class);
        $queueMessage->method('getBody')->willReturn($body);

        $context = $this->createStub(Context::class);
        $container = $this->createStub(ContainerInterface::class);

        return new Message($queueMessage, $context, $container);
    }

    private function skipIfAiUnavailable(): void
    {
        if ($this->probeLiveServer() === null) {
            $this->markTestSkipped('Live AI server is not available at ' . $this->liveUrl);
        }
        // Some reasoning models put output in reasoning_content. Check both.
        $msg = $this->aiProbe['choices'][0]['message'];
        $content = (string)($msg['content'] ?? '');
        $reasoning = (string)($msg['reasoning_content'] ?? '');
        if (trim($content) === '' && trim($reasoning) === '') {
            $this->markTestSkipped('Live AI server at ' . $this->liveUrl . ' returns empty content.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function probeLiveServer(): ?array
    {
        if ($this->aiProbe !== null) {
            return $this->aiProbe;
        }

        $payload = json_encode([
            'model' => $this->model,
            'messages' => [['role' => 'user', 'content' => 'Reply with the single word OK.']],
            'max_tokens' => 128,
        ]);
        if ($payload === false) {
            return null;
        }

        $ch = curl_init($this->liveUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded) || !isset($decoded['choices'][0]['message'])) {
            return null;
        }

        $this->aiProbe = $decoded;

        return $this->aiProbe;
    }
}
