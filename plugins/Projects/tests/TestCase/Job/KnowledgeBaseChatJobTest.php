<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Job;

use App\Lib\AIAssistant;
use App\Model\Entity\User;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\Message;
use Cake\TestSuite\TestCase;
use Closure;
use Interop\Queue\Context;
use Interop\Queue\Message as QueueMessage;
use Interop\Queue\Processor;
use Projects\Job\KnowledgeBaseChatJob;
use Projects\Lib\KnowledgeBase\KbOperationApplier;
use Projects\Lib\KnowledgeBase\KbService;

/**
 * KnowledgeBaseChatJob with a model that answers from a closure.
 */
class KnowledgeBaseChatJobTest extends TestCase
{
    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';
    private const JOB = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

    /**
     * @var array<int, string>
     */
    protected array $fixtures = [
        'app.Users',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsUsers',
        'plugin.Projects.ProjectsKbRevisions',
    ];

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $file = TMP . 'ai_jobs' . DS . self::JOB . '_result.json';
        if (file_exists($file)) {
            unlink($file);
        }
        parent::tearDown();
    }

    /**
     * @param \Closure $reply Called with the message of the user; returns the answer of the "model".
     * @return \Projects\Job\KnowledgeBaseChatJob
     */
    private function job(Closure $reply): KnowledgeBaseChatJob
    {
        return new class ($reply) extends KnowledgeBaseChatJob {
            /**
             * @param \Closure $reply Answer of the model.
             */
            public function __construct(private Closure $reply)
            {
            }

            /**
             * @param \App\Model\Entity\User $user User.
             * @param string $projectId Project.
             * @return \App\Lib\AIAssistant
             */
            protected function createAssistant(User $user, string $projectId): AIAssistant
            {
                return new class ($user, $this->reply) extends AIAssistant {
                    /**
                     * @param \App\Model\Entity\User $user User.
                     * @param \Closure $reply Answer of the model.
                     */
                    public function __construct(User $user, private Closure $reply)
                    {
                        parent::__construct($user);
                    }

                    /**
                     * @param string $userInput Message.
                     * @return string
                     */
                    public function getResponse(string $userInput): string
                    {
                        $this->setHistory(array_merge($this->getHistory(), [
                            ['role' => 'user', 'content' => $userInput],
                        ]));

                        return ($this->reply)($userInput);
                    }
                };
            }
        };
    }

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
     * @return array<string, mixed>
     */
    private function arguments(): array
    {
        return [
            'user_id' => USER_ADMIN,
            'project_id' => self::PROJECT,
            'message' => 'Dodaj opozorilo o PGD',
            'history' => [],
            'job_id' => self::JOB,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readResult(): array
    {
        $file = TMP . 'ai_jobs' . DS . self::JOB . '_result.json';
        $this->assertFileExists($file);

        return json_decode((string)file_get_contents($file), true);
    }

    /**
     * @return void
     */
    public function testAnswerWithoutChangesCarriesNoChangeData(): void
    {
        $outcome = $this->job(fn(string $message): string => 'Projekt ima **eno** opozorilo.')
            ->execute($this->message($this->arguments()));

        $this->assertSame(Processor::ACK, $outcome);
        $result = $this->readResult();
        $this->assertSame('done', $result['status']);
        $this->assertStringContainsString('<strong>eno</strong>', $result['response']);
        $this->assertSame(USER_ADMIN, $result['user_id']);
        $this->assertSame(self::PROJECT, $result['project_id']);
        $this->assertNotEmpty($result['history']);
        $this->assertArrayNotHasKey('changes', $result);
        $this->assertArrayNotHasKey('undo_revision_id', $result);
    }

    /**
     * @return void
     */
    public function testChangesOfTheConversationAreReportedWithAWayToUndoThem(): void
    {
        // The project already has a knowledge base, so that the undo goes back to a real state.
        (new KbService())->apply(self::PROJECT, [
            ['op' => 'add_item', 'section' => 'warnings', 'text' => 'Staro opozorilo'],
        ], KbOperationApplier::BATCH);
        $before = TableRegistry::getTableLocator()->get('Projects.Projects')->get(self::PROJECT);

        $outcome = $this->job(function (string $message): string {
            (new KbService())->apply(self::PROJECT, [
                ['op' => 'add_item', 'section' => 'warnings', 'severity' => 'high', 'text' => 'Manjka PGD'],
            ], KbOperationApplier::CHAT, USER_ADMIN, null, 'Dodano opozorilo o PGD');
            // A change of someone else in the meantime is not the conversation's.
            (new KbService())->apply(self::PROJECT, [
                ['op' => 'add_item', 'section' => 'open_questions', 'text' => 'Tuje vprašanje'],
            ], KbOperationApplier::BATCH);

            return 'Dodano.';
        })->execute($this->message($this->arguments()));

        $this->assertSame(Processor::ACK, $outcome);
        $result = $this->readResult();
        $this->assertSame(['Dodano opozorilo o PGD'], $result['changes']);
        $this->assertStringContainsString('Manjka PGD', $result['kb_html']);
        $this->assertSame($before->kb_version + 2, $result['kb_version']);

        $target = TableRegistry::getTableLocator()->get('Projects.ProjectsKbRevisions')->get($result['undo_revision_id']);
        $this->assertSame($before->kb_version, $target->version);
    }

    /**
     * @return void
     */
    public function testCompanyThatIsNotListedGetsNoAnswer(): void
    {
        $before = Configure::read('Projects.KnowledgeBase.companies');
        Configure::write('Projects.KnowledgeBase.companies', ['11111111-2222-4333-8444-555555555555']);
        try {
            $outcome = $this->job(fn(string $message): string => 'Ne bi smelo priti sem.')
                ->execute($this->message($this->arguments()));
        } finally {
            Configure::write('Projects.KnowledgeBase.companies', $before);
        }

        $this->assertSame(Processor::REJECT, $outcome);
        $result = $this->readResult();
        $this->assertSame('error', $result['status']);
        $this->assertArrayNotHasKey('response', $result);
    }

    /**
     * @return void
     */
    public function testUserWithoutAccessGetsAnErrorAndNoAnswer(): void
    {
        // The job builds its own authorization from the policies; USER_COMMON is not a member of the project.
        $arguments = ['user_id' => USER_COMMON] + $this->arguments();
        $outcome = $this->job(fn(string $message): string => 'Ne bi smelo priti sem.')
            ->execute($this->message($arguments));

        $this->assertSame(Processor::REJECT, $outcome);
        $result = $this->readResult();
        $this->assertSame('error', $result['status']);
        $this->assertArrayNotHasKey('response', $result);
    }

    /**
     * @return void
     */
    public function testMissingArgumentsAreRejected(): void
    {
        $arguments = ['message' => ''] + $this->arguments();

        $outcome = $this->job(fn(string $message): string => 'x')->execute($this->message($arguments));

        $this->assertSame(Processor::REJECT, $outcome);
    }
}
