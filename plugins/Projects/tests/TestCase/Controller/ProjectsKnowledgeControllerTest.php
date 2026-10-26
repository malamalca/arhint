<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Queue\TestSuite\QueueTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Projects\Job\KnowledgeBaseRegenerateJob;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbOperationApplier;
use Projects\Lib\KnowledgeBase\KbService;

/**
 * The conversation about the knowledge base of a project: queueing, polling, clearing and undoing.
 */
class ProjectsKnowledgeControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use QueueTrait;

    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';
    private const JOB = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
    private const BASE = '/projects/projects-knowledge/';

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

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

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
     * @param string $userId User id.
     * @param bool $ai Whether the user has the AI assistant set up.
     * @return void
     */
    private function login(string $userId, bool $ai = true): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        if ($ai) {
            $users->updateAll(
                ['properties' => json_encode(['ai_assistant' => ['provider' => 'local', 'url' => 'http://localhost']])],
                ['id' => $userId],
            );
        }
        $this->session(['Auth' => $users->get($userId)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function body(): array
    {
        return json_decode((string)$this->_response?->getBody(), true);
    }

    /**
     * @return void
     */
    public function testChatQueuesTheMessageForTheProject(): void
    {
        $this->login(USER_ADMIN);

        $this->post(self::BASE . 'chat/' . self::PROJECT, ['message' => 'Kaj je odprto?']);

        $this->assertResponseOk();
        $body = $this->body();
        $this->assertSame('pending', $body['status']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $body['job_id']);
        $this->assertJobQueued('Projects\Job\KnowledgeBaseChatJob');
        $this->assertSession($body['job_id'], 'ProjectsKb.' . self::PROJECT . '.pendingJobId');
    }

    /**
     * @return void
     */
    public function testSecondMessageWhileOneIsRunningKeepsPollingTheFirst(): void
    {
        $this->login(USER_ADMIN);
        $this->session(['ProjectsKb' => [self::PROJECT => ['pendingJobId' => self::JOB]]]);

        $this->post(self::BASE . 'chat/' . self::PROJECT, ['message' => 'Drugo']);

        $this->assertSame(self::JOB, $this->body()['job_id']);
        $this->assertNoJobsQueued();
    }

    /**
     * @return void
     */
    public function testChatRefusesEmptyMessageAndUnconfiguredAi(): void
    {
        $this->login(USER_ADMIN);
        $this->post(self::BASE . 'chat/' . self::PROJECT, ['message' => '  ']);
        $this->assertArrayHasKey('error', $this->body());

        TableRegistry::getTableLocator()->get('Users')->updateAll(['properties' => null], ['id' => USER_ADMIN]);
        $this->login(USER_ADMIN, false);
        $this->post(self::BASE . 'chat/' . self::PROJECT, ['message' => 'Živjo']);
        $this->assertArrayHasKey('error', $this->body());
        $this->assertNoJobsQueued();
    }

    /**
     * @return void
     */
    public function testChatIsNotOpenToNonMembersAndNeedsPost(): void
    {
        $this->login(USER_COMMON);
        $this->post(self::BASE . 'chat/' . self::PROJECT, ['message' => 'Živjo']);
        $this->assertResponseError();
        $this->assertNoJobsQueued();

        $this->login(USER_ADMIN);
        $this->get(self::BASE . 'chat/' . self::PROJECT);
        $this->assertResponseError();
    }

    /**
     * @return void
     */
    public function testChatStatusReturnsTheAnswerOnceAndKeepsTheHistory(): void
    {
        $this->login(USER_ADMIN);
        $this->get(self::BASE . 'chatStatus/' . self::PROJECT . '?job_id=' . self::JOB);
        $this->assertSame('pending', $this->body()['status']);

        $this->get(self::BASE . 'chatStatus/' . self::PROJECT . '?job_id=nonsense');
        $this->assertResponseCode(400);

        file_put_contents(TMP . 'ai_jobs' . DS . self::JOB . '_result.json', json_encode([
            'user_id' => USER_ADMIN,
            'project_id' => self::PROJECT,
            'status' => 'done',
            'response' => '<p>Odgovor</p>',
            'history' => [['role' => 'user', 'content' => 'Kaj je odprto?']],
            'changes' => ['Dodano opozorilo'],
            'undo_revision_id' => 'rev',
            'kb_html' => '<p>Baza</p>',
            'kb_version' => 3,
        ]));
        $this->get(self::BASE . 'chatStatus/' . self::PROJECT . '?job_id=' . self::JOB);

        $body = $this->body();
        $this->assertSame('done', $body['status']);
        $this->assertSame('<p>Odgovor</p>', $body['response']);
        $this->assertSame(['Dodano opozorilo'], $body['changes']);
        $this->assertSame(3, $body['kb_version']);
        $this->assertSession([['role' => 'user', 'content' => 'Kaj je odprto?']], 'ProjectsKb.' . self::PROJECT . '.history');
        $this->assertFileDoesNotExist(TMP . 'ai_jobs' . DS . self::JOB . '_result.json');
    }

    /**
     * @return void
     */
    public function testChatStatusDoesNotGiveAnAnswerToSomeoneElse(): void
    {
        file_put_contents(TMP . 'ai_jobs' . DS . self::JOB . '_result.json', json_encode([
            'user_id' => USER_COMMON,
            'project_id' => self::PROJECT,
            'status' => 'done',
            'response' => 'Zaupno',
        ]));
        $this->login(USER_ADMIN);

        $this->get(self::BASE . 'chatStatus/' . self::PROJECT . '?job_id=' . self::JOB);

        $this->assertResponseCode(403);
        $this->assertFileExists(TMP . 'ai_jobs' . DS . self::JOB . '_result.json');
    }

    /**
     * @return void
     */
    public function testClearChatForgetsTheConversation(): void
    {
        $this->login(USER_ADMIN);
        $this->session(['ProjectsKb' => [self::PROJECT => ['history' => [['role' => 'user', 'content' => 'x']]]]]);

        $this->post(self::BASE . 'clearChat/' . self::PROJECT);

        $this->assertTrue($this->body()['cleared']);
        $this->assertSessionNotHasKey('ProjectsKb.' . self::PROJECT . '.history');
    }

    /**
     * @return void
     */
    public function testUndoReturnsTheStateBeforeTheChange(): void
    {
        $service = new KbService();
        $service->apply(self::PROJECT, [
            ['op' => 'add_item', 'section' => 'warnings', 'text' => 'Staro'],
        ], KbOperationApplier::BATCH);
        $revisions = TableRegistry::getTableLocator()->get('Projects.ProjectsKbRevisions');
        $before = $revisions->find()->orderBy(['version' => 'DESC'])->firstOrFail();
        $service->apply(self::PROJECT, [
            ['op' => 'add_item', 'section' => 'warnings', 'text' => 'Novo'],
        ], KbOperationApplier::CHAT, USER_ADMIN);
        $this->login(USER_ADMIN);

        $this->post(self::BASE . 'undo/' . self::PROJECT, ['revision_id' => $before->id]);

        $this->assertResponseOk();
        $body = $this->body();
        $this->assertStringContainsString('Staro', $body['kb_html']);
        $this->assertStringNotContainsString('Novo', $body['kb_html']);
        $this->assertSame($before->version + 2, $body['kb_version']);
    }

    /**
     * @return void
     */
    public function testUndoRefusesARevisionOfAnotherProject(): void
    {
        $service = new KbService();
        $service->apply('4dd53305-9715-4be4-b169-20defe113d2b', [
            ['op' => 'add_item', 'section' => 'warnings', 'text' => 'Tuje'],
        ], KbOperationApplier::BATCH);
        $foreign = TableRegistry::getTableLocator()->get('Projects.ProjectsKbRevisions')->find()
            ->where(['project_id' => '4dd53305-9715-4be4-b169-20defe113d2b'])->firstOrFail();
        $this->login(USER_ADMIN);

        $this->post(self::BASE . 'undo/' . self::PROJECT, ['revision_id' => $foreign->id]);

        $this->assertResponseCode(422);
        $this->assertSame(0, TableRegistry::getTableLocator()->get('Projects.Projects')->get(self::PROJECT)->kb_version);
    }

    /**
     * @return void
     */
    public function testProjectPageShowsTheChatOnlyToUsersWithAi(): void
    {
        $this->login(USER_ADMIN);
        $this->get('/projects/projects/view/' . self::PROJECT);
        $this->assertResponseOk();
        $this->assertResponseContains('id="ProjectKbChat"');
        $this->assertResponseContains('projects-knowledge/chat/' . self::PROJECT);
        $this->assertResponseContains('id="ProjectKbContent"');

        TableRegistry::getTableLocator()->get('Users')->updateAll(['properties' => null], ['id' => USER_ADMIN]);
        $this->login(USER_ADMIN, false);
        $this->get('/projects/projects/view/' . self::PROJECT);
        $this->assertResponseOk();
        $this->assertResponseNotContains('id="ProjectKbChat"');
    }

    /**
     * @return string Id of a deadline in the knowledge base of the project.
     */
    private function addDeadline(): string
    {
        (new KbService())->apply(self::PROJECT, [
            ['op' => 'add_item', 'section' => 'deadlines', 'date' => '2026-11-15', 'text' => 'Dopolnitev vloge'],
        ], KbOperationApplier::BATCH);
        $doc = KbDocument::parse((string)TableRegistry::getTableLocator()->get('Projects.Projects')
            ->get(self::PROJECT)->descript);

        return $doc->items('deadlines')[0]->id;
    }

    /**
     * @return void
     */
    public function testDeadlineCanBeAddedToAndRemovedFromTheCalendar(): void
    {
        $item = $this->addDeadline();
        $this->login(USER_ADMIN);
        $links = TableRegistry::getTableLocator()->get('Projects.ProjectsKbCalendarLinks');

        $this->post(self::BASE . 'calendar/' . self::PROJECT, ['item_id' => $item]);
        $this->assertResponseOk();
        $this->assertTrue($this->body()['linked']);
        $this->assertSame(1, $links->find()->count());

        $this->get('/projects/projects/view/' . self::PROJECT);
        $this->assertResponseContains('data-item="' . $item . '" data-linked="1"');
        $this->assertResponseContains('data-calendar-url=');

        $this->post(self::BASE . 'calendar/' . self::PROJECT, ['item_id' => $item, 'remove' => '1']);
        $this->assertFalse($this->body()['linked']);
        $this->assertSame(0, $links->find()->count());
    }

    /**
     * @return void
     */
    public function testCalendarRefusesWhatIsNotAnOpenDeadlineAndNonMembers(): void
    {
        $item = $this->addDeadline();
        $this->login(USER_ADMIN);
        $this->post(self::BASE . 'calendar/' . self::PROJECT, ['item_id' => 'd-0000']);
        $this->assertResponseCode(422);
        $this->assertArrayHasKey('error', $this->body());

        $this->login(USER_COMMON);
        $this->post(self::BASE . 'calendar/' . self::PROJECT, ['item_id' => $item]);
        $this->assertResponseError();
        $this->assertSame(0, TableRegistry::getTableLocator()->get('Projects.ProjectsKbCalendarLinks')->find()->count());
    }

    /**
     * @return void
     */
    public function testConversationIsOnlyForTheListedCompanies(): void
    {
        $before = Configure::read('Projects.KnowledgeBase.companies');
        Configure::write('Projects.KnowledgeBase.companies', ['11111111-2222-4333-8444-555555555555']);
        try {
            $this->login(USER_ADMIN);

            $this->post(self::BASE . 'chat/' . self::PROJECT, ['message' => 'Kaj je odprto?']);
            $this->assertArrayHasKey('error', $this->body());
            $this->assertNoJobsQueued();

            $this->get('/projects/projects/view/' . self::PROJECT);
            $this->assertResponseOk();
            $this->assertResponseNotContains('id="ProjectKbChat"');

            Configure::write('Projects.KnowledgeBase.companies', [COMPANY_FIRST]);
            $this->get('/projects/projects/view/' . self::PROJECT);
            $this->assertResponseContains('id="ProjectKbChat"');
        } finally {
            Configure::write('Projects.KnowledgeBase.companies', $before);
        }
    }

    /**
     * @return void
     */
    public function testAdministratorCanQueueRegenerationOnlyOnce(): void
    {
        $marker = KnowledgeBaseRegenerateJob::markerFile(self::PROJECT);
        if (file_exists($marker)) {
            unlink($marker);
        }
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();

        $this->post(self::BASE . 'regenerate/' . self::PROJECT);
        $this->assertRedirect(['controller' => 'Projects', 'action' => 'view', self::PROJECT]);
        $this->assertJobQueuedTimes('Projects\Job\KnowledgeBaseRegenerateJob', 1);
        $this->assertFileExists($marker);

        $this->post(self::BASE . 'regenerate/' . self::PROJECT);
        $this->assertJobQueuedTimes('Projects\Job\KnowledgeBaseRegenerateJob', 1);

        $this->get('/projects/projects/view/' . self::PROJECT);
        $this->assertResponseContains('id="ProjectKbRegenerating"');
        unlink($marker);

        $this->get('/projects/projects/view/' . self::PROJECT);
        $this->assertResponseContains('id="ProjectKbRegenerate"');
    }

    /**
     * @return void
     */
    public function testRegenerationNeedsAdministratorAndPost(): void
    {
        $this->enableSecurityToken();
        $this->login(USER_COMMON);
        $this->post(self::BASE . 'regenerate/' . self::PROJECT);
        $this->assertResponseError();

        $this->login(USER_ADMIN);
        $this->get(self::BASE . 'regenerate/' . self::PROJECT);
        $this->assertResponseError();
        $this->assertNoJobsQueued();
    }
}
