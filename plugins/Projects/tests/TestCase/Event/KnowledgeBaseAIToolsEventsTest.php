<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Event;

use App\Lib\AIAssistant;
use App\Model\Entity\User;
use ArrayObject;
use Authorization\AuthorizationServiceInterface;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Projects\Event\KnowledgeBaseAIToolsEvents;
use Projects\Event\ProjectsAIToolsEvents;
use Projects\Lib\KnowledgeBase\KbDocument;
use ReflectionMethod;
use ReflectionProperty;

class KnowledgeBaseAIToolsEventsTest extends TestCase
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
    ];

    private KnowledgeBaseAIToolsEvents $listener;

    private User $user;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new KnowledgeBaseAIToolsEvents();
        $this->user = $this->userWith(true);
    }

    /**
     * @param bool $access Whether the authorization allows the user to work with the knowledge base.
     * @return \App\Model\Entity\User
     */
    private function userWith(bool $access): User
    {
        $service = $this->createStub(AuthorizationServiceInterface::class);
        $service->method('can')->willReturn($access);
        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $user->setAuthorization($service);

        return $user;
    }

    /**
     * @param array<string, mixed> $context Context of the assistant.
     * @return \App\Lib\AIAssistant
     */
    private function assistant(array $context): AIAssistant
    {
        return new AIAssistant($this->user, $context);
    }

    /**
     * @param string $tool Tool.
     * @param array<string, mixed> $arguments Arguments.
     * @param \App\Lib\AIAssistant $assistant Assistant that runs the tool.
     * @param \App\Model\Entity\User|null $user User, the default one when empty.
     * @return mixed
     */
    private function callTool(string $tool, array $arguments, AIAssistant $assistant, ?User $user = null): mixed
    {
        $event = new Event('App.AIAssistant.executeTool', $assistant, [$tool, $arguments, $user ?? $this->user]);
        $this->listener->aiAssistantExecuteTool($event, $tool, $arguments);

        return $event->getResult();
    }

    /**
     * @return array<string, mixed>
     */
    private function context(): array
    {
        return [KnowledgeBaseAIToolsEvents::CONTEXT_PROJECT => self::PROJECT];
    }

    /**
     * @return void
     */
    public function testToolsAreOfferedOnlyToAConversationAboutAProject(): void
    {
        $with = new ArrayObject();
        $this->listener->aiAssistantTools(new Event('App.AIAssistant.tools', $this->assistant($this->context())), $with);
        $without = new ArrayObject();
        $this->listener->aiAssistantTools(new Event('App.AIAssistant.tools', $this->assistant([])), $without);

        $this->assertSame(
            ['Projects.kb_read', 'Projects.kb_apply'],
            array_map(fn($tool) => $tool->name, $with->getArrayCopy()),
        );
        $this->assertSame([], $without->getArrayCopy());
    }

    /**
     * @return void
     */
    public function testApplyChangesTheProjectOfTheConversationOnly(): void
    {
        $assistant = $this->assistant($this->context());

        $result = $this->callTool('Projects.kb_apply', [
            'operations' => [['op' => 'add_item', 'section' => 'warnings', 'severity' => 'high', 'text' => 'Manjka PGD']],
            'changes_summary' => 'Dodano opozorilo o PGD',
            // A project named by the model is ignored.
            'project_id' => '4dd53305-9715-4be4-b169-20defe113d2b',
        ], $assistant);

        $this->assertSame(1, $result['applied']);
        $this->assertSame([], $result['rejected']);
        $projects = TableRegistry::getTableLocator()->get('Projects.Projects');
        $doc = KbDocument::parse((string)$projects->get(self::PROJECT)->descript);
        $this->assertSame('Manjka PGD', $doc->items('warnings')[0]->text);
        $this->assertStringContainsString('Dodano opozorilo o PGD', $doc->changelog()[0]);
        $this->assertSame(0, $projects->get('4dd53305-9715-4be4-b169-20defe113d2b')->kb_version);
        $revision = TableRegistry::getTableLocator()->get('Projects.ProjectsKbRevisions')->find()
            ->orderBy(['version' => 'DESC'])->firstOrFail();
        $this->assertSame('chat', $revision->source);
        $this->assertSame(USER_ADMIN, $revision->user_id);
    }

    /**
     * @return void
     */
    public function testApplyReportsRejectedOperationsAndAcceptsAJsonString(): void
    {
        $assistant = $this->assistant($this->context());

        $result = $this->callTool('Projects.kb_apply', [
            'operations' => json_encode([
                ['op' => 'add_item', 'section' => 'deadlines', 'text' => 'Brez datuma'],
                ['op' => 'add_item', 'section' => 'open_questions', 'text' => 'Kdo plača priključek?'],
            ]),
        ], $assistant);

        $this->assertSame(1, $result['applied']);
        $this->assertCount(1, $result['rejected']);

        $result = $this->callTool('Projects.kb_apply', ['operations' => [['op' => 'remove_item', 'id' => 'q-0000']]], $assistant);
        $this->assertArrayHasKey('error', $result);
        $this->assertCount(1, $result['rejected']);

        $result = $this->callTool('Projects.kb_apply', ['operations' => []], $assistant);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * @return void
     */
    public function testReadReturnsSectionsAndItemsWithIds(): void
    {
        $assistant = $this->assistant($this->context());
        $this->callTool('Projects.kb_apply', ['operations' => [
            ['op' => 'add_item', 'section' => 'deadlines', 'date' => '2026-11-15', 'text' => 'Oddaja'],
            ['op' => 'set_section', 'section' => 'summary', 'text' => 'Gradnja poteka.'],
        ]], $assistant);

        $items = $this->callTool('Projects.kb_read', ['section' => 'deadlines'], $assistant);
        $this->assertMatchesRegularExpression('/^d-[0-9a-f]{4}$/', $items['items'][0]['id']);
        $this->assertSame('2026-11-15', $items['items'][0]['date']);

        $this->assertSame('Gradnja poteka.', $this->callTool('Projects.kb_read', ['section' => 'summary'], $assistant)['text']);
        $this->assertArrayHasKey('error', $this->callTool('Projects.kb_read', ['section' => 'nope'], $assistant));

        $all = $this->callTool('Projects.kb_read', [], $assistant);
        $this->assertSame(2, $all['version']);
        $this->assertStringContainsString('Oddaja', $all['knowledge_base']);
    }

    /**
     * @return void
     */
    public function testToolsAreRefusedWithoutProjectContextOrAccess(): void
    {
        $this->assertArrayHasKey('error', $this->callTool('Projects.kb_read', [], $this->assistant([])));

        $noAccess = $this->userWith(false);
        $result = $this->callTool('Projects.kb_apply', [
            'operations' => [['op' => 'add_item', 'section' => 'warnings', 'text' => 'X']],
        ], $this->assistant($this->context()), $noAccess);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame(0, TableRegistry::getTableLocator()->get('Projects.Projects')->get(self::PROJECT)->kb_version);
    }

    /**
     * @return void
     */
    public function testToolsAreRefusedForACompanyThatIsNotListed(): void
    {
        $before = Configure::read('Projects.KnowledgeBase.companies');
        Configure::write('Projects.KnowledgeBase.companies', ['11111111-2222-4333-8444-555555555555']);
        try {
            $assistant = $this->assistant($this->context());

            $read = $this->callTool('Projects.kb_read', [], $assistant);
            $apply = $this->callTool('Projects.kb_apply', [
                'operations' => [['op' => 'add_item', 'section' => 'warnings', 'text' => 'X']],
            ], $assistant);

            $this->assertArrayHasKey('error', $read);
            $this->assertArrayHasKey('error', $apply);
            $this->assertSame(
                0,
                TableRegistry::getTableLocator()->get('Projects.Projects')->get(self::PROJECT)->kb_version,
            );
        } finally {
            Configure::write('Projects.KnowledgeBase.companies', $before);
        }
    }

    /**
     * @return void
     */
    public function testOtherToolsAreNotHandled(): void
    {
        $this->assertNull($this->callTool('Projects.get_project', [], $this->assistant($this->context())));
    }

    /**
     * @return void
     */
    public function testRestrictedAssistantOffersAndRunsOnlyTheAllowedTools(): void
    {
        // The assistant collects its tools from the global event manager, as it does in the application.
        $projectsTools = new ProjectsAIToolsEvents();
        EventManager::instance()->on($this->listener);
        EventManager::instance()->on($projectsTools);
        $assistant = $this->assistant($this->context());
        EventManager::instance()->off($this->listener);
        EventManager::instance()->off($projectsTools);

        $tools = new ReflectionProperty(AIAssistant::class, 'tools');
        $this->assertContains('Projects.create_task', array_map(fn($tool) => $tool->name, $tools->getValue($assistant)));
        $assistant->restrictTools(KnowledgeBaseAIToolsEvents::CHAT_TOOLS);

        $names = array_map(fn($tool) => $tool->name, $tools->getValue($assistant));
        $this->assertContains('Projects.kb_apply', $names);
        $this->assertNotContains('Projects.create_task', $names);
        $this->assertNotContains('Projects.update_project', $names);
        $this->assertSame([], array_diff($names, KnowledgeBaseAIToolsEvents::CHAT_TOOLS));

        $run = new ReflectionMethod(AIAssistant::class, 'runTool');
        $refused = $run->invoke($assistant, 'Projects.create_task', ['project_id' => self::PROJECT, 'title' => 'X']);
        $this->assertArrayHasKey('error', $refused);
    }
}
