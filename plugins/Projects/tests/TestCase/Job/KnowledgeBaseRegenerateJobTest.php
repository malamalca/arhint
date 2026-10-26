<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Job;

use Cake\Core\ContainerInterface;
use Cake\Queue\Job\Message;
use Cake\TestSuite\TestCase;
use Interop\Queue\Context;
use Interop\Queue\Message as QueueMessage;
use Interop\Queue\Processor;
use Projects\Job\KnowledgeBaseRegenerateJob;

class KnowledgeBaseRegenerateJobTest extends TestCase
{
    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';

    /**
     * @var array<int, string>
     */
    protected array $fixtures = [
        'plugin.Projects.Projects',
    ];

    /**
     * @param array<string, mixed> $arguments Arguments of the job.
     * @return \Cake\Queue\Job\Message
     */
    private function message(array $arguments): Message
    {
        $queueMessage = $this->createStub(QueueMessage::class);
        $queueMessage->method('getBody')->willReturn((string)json_encode(['data' => $arguments]));

        return new Message(
            $queueMessage,
            $this->createStub(Context::class),
            $this->createStub(ContainerInterface::class),
        );
    }

    /**
     * @return void
     */
    public function testWaitsInTheQueueWhileTheHourlyUpdateRuns(): void
    {
        $marker = KnowledgeBaseRegenerateJob::markerFile(self::PROJECT);
        touch($marker);
        $lock = fopen(TMP . 'projects_knowledge_base.lock', 'c');
        flock($lock, LOCK_EX);

        try {
            $outcome = (new KnowledgeBaseRegenerateJob())->execute($this->message(['project_id' => self::PROJECT]));
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $this->assertSame(Processor::REQUEUE, $outcome);
        $this->assertTrue(KnowledgeBaseRegenerateJob::isPending(self::PROJECT));
        unlink($marker);
    }

    /**
     * @return void
     */
    public function testRejectsAMissingProjectAndClearsTheMarker(): void
    {
        $missing = '00000000-0000-4000-8000-000000000000';
        $marker = KnowledgeBaseRegenerateJob::markerFile($missing);
        touch($marker);

        $outcome = (new KnowledgeBaseRegenerateJob())->execute($this->message(['project_id' => $missing]));

        $this->assertSame(Processor::REJECT, $outcome);
        $this->assertFalse(KnowledgeBaseRegenerateJob::isPending($missing));
        $this->assertSame(Processor::REJECT, (new KnowledgeBaseRegenerateJob())->execute($this->message([])));
    }
}
