<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\Queue\TestSuite\QueueTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AiController Test Case
 *
 * @uses \App\Controller\AiController
 */
class AiControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use QueueTrait;

    /**
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    // -------------------------------------------------------------------------
    // POST /ai/chat — unauthenticated
    // -------------------------------------------------------------------------

    public function testChatRequiresAuthentication(): void
    {
        $this->post('/ai/chat', ['message' => 'Hello']);
        $this->assertResponseCode(302);
    }

    // -------------------------------------------------------------------------
    // POST /ai/chat — empty message
    // -------------------------------------------------------------------------

    public function testChatRejectsEmptyMessage(): void
    {
        $this->login(USER_ADMIN);
        $this->post('/ai/chat', ['message' => '   ']);

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $body = json_decode((string)$this->_response?->getBody(), true);
        $this->assertArrayHasKey('error', $body);
        $this->assertNull($body['job_id']);
    }

    // -------------------------------------------------------------------------
    // POST /ai/chat — valid message
    // -------------------------------------------------------------------------

    public function testChatEnqueuesJobAndReturnsJobId(): void
    {
        $this->login(USER_ADMIN);
        $this->post('/ai/chat', ['message' => 'What is 2+2?']);

        $responseBody = (string)$this->_response?->getBody();
        $this->assertResponseOk($responseBody);
        $this->assertContentType('application/json');
        $body = json_decode((string)$this->_response?->getBody(), true);

        $this->assertArrayHasKey('job_id', $body);
        $this->assertArrayHasKey('status', $body);
        $this->assertSame('pending', $body['status']);
        // Must be a valid UUID.
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            (string)$body['job_id'],
        );
        // Job must have been pushed onto the queue.
        $this->assertJobQueued('App\Job\AiChatJob');
    }

    // -------------------------------------------------------------------------
    // GET /ai/chat-status — invalid job ID
    // -------------------------------------------------------------------------

    public function testChatStatusRejectsMalformedJobId(): void
    {
        $this->login(USER_ADMIN);
        $this->get('/ai/chat-status?job_id=../../etc/passwd');

        $this->assertResponseCode(400);
        $body = json_decode((string)$this->_response?->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    // -------------------------------------------------------------------------
    // GET /ai/chat-status — pending (no result file yet)
    // -------------------------------------------------------------------------

    public function testChatStatusReturnsPendingWhenResultMissing(): void
    {
        $this->login(USER_ADMIN);
        $this->get('/ai/chat-status?job_id=aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response?->getBody(), true);
        $this->assertSame('pending', $body['status']);
    }

    // -------------------------------------------------------------------------
    // GET /ai/chat-status — done result file present
    // -------------------------------------------------------------------------

    public function testChatStatusReturnsDoneResultWhenFileExists(): void
    {
        $this->login(USER_ADMIN);

        $jobId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $resultFile = TMP . 'ai_jobs' . DS . $jobId . '_result.json';

        $jobsDir = TMP . 'ai_jobs' . DS;
        if (!is_dir($jobsDir)) {
            mkdir($jobsDir, 0755, true);
        }

        file_put_contents($resultFile, json_encode([
            'user_id' => USER_ADMIN,
            'status' => 'done',
            'response' => '<p>Four.</p>',
            'redirect' => null,
            'history' => [],
        ]));

        $this->get('/ai/chat-status?job_id=' . $jobId);

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response?->getBody(), true);
        $this->assertSame('done', $body['status']);
        $this->assertSame('<p>Four.</p>', $body['response']);
        $this->assertFileDoesNotExist($resultFile);
    }

    // -------------------------------------------------------------------------
    // GET /ai/chat-status — result belongs to different user
    // -------------------------------------------------------------------------

    public function testChatStatusForbidsResultOwnedByAnotherUser(): void
    {
        $this->login(USER_COMMON);

        $jobId = 'aaaaaaaa-bbbb-cccc-dddd-111111111111';
        $resultFile = TMP . 'ai_jobs' . DS . $jobId . '_result.json';

        $jobsDir = TMP . 'ai_jobs' . DS;
        if (!is_dir($jobsDir)) {
            mkdir($jobsDir, 0755, true);
        }

        file_put_contents($resultFile, json_encode([
            'user_id' => USER_ADMIN,
            'status' => 'done',
            'response' => '<p>Secret.</p>',
            'redirect' => null,
            'history' => [],
        ]));

        $this->get('/ai/chat-status?job_id=' . $jobId);

        $this->assertResponseCode(403);
        // Result file must NOT be deleted when access is denied.
        $this->assertFileExists($resultFile);

        // Cleanup
        unlink($resultFile);
    }

    // -------------------------------------------------------------------------
    // POST /ai/clear-history
    // -------------------------------------------------------------------------

    public function testClearHistoryWipesSession(): void
    {
        $this->login(USER_ADMIN);
        $this->session(['AIAssistant' => ['history' => [['role' => 'user', 'content' => 'hi']]]]);

        $this->post('/ai/clear-history');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response?->getBody(), true);
        $this->assertTrue($body['cleared']);
    }
}
