<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\AIAssistant;
use App\Lib\AITool;
use App\Model\Entity\User;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use ReflectionClass;

class AIAssistantTest extends TestCase
{
    public function testSetHistoryCompactsOlderMessagesIntoSummary(): void
    {
        $assistant = new AIAssistant();

        $history = [];
        for ($index = 1; $index <= 12; $index++) {
            $history[] = ['role' => 'user', 'content' => 'User message ' . $index];
        }

        $assistant->setHistory($history);
        $storedHistory = $assistant->getHistory();

        $this->assertCount(9, $storedHistory);
        $this->assertSame('assistant', $storedHistory[0]['role']);
        $this->assertStringStartsWith('Conversation summary: ', (string)$storedHistory[0]['content']);
        $this->assertSame('User message 12', $storedHistory[8]['content']);
    }

    /**
     * @param int $count Number of tool calls requested by one assistant message.
     * @return array<int, array<string, mixed>>
     */
    private function toolBatch(int $count): array
    {
        $toolCalls = [];
        $responses = [];
        for ($index = 1; $index <= $count; $index++) {
            $toolCalls[] = ['id' => 'call_' . $index, 'type' => 'function', 'function' => ['name' => 'x', 'arguments' => '{}']];
            $responses[] = ['role' => 'tool', 'tool_call_id' => 'call_' . $index, 'content' => '{}'];
        }

        return array_merge(
            [['role' => 'user', 'content' => 'Analyse'], ['role' => 'assistant', 'content' => '', 'tool_calls' => $toolCalls]],
            $responses,
        );
    }

    public function testToolBatchLargerThanHistoryWindowKeepsAssistantMessage(): void
    {
        $assistant = new AIAssistant();
        $reflection = new ReflectionClass($assistant);
        $append = $reflection->getMethod('appendConversationMessage');
        foreach ($this->toolBatch(10) as $message) {
            $append->invoke($assistant, $message);
        }

        $history = array_values(array_filter(
            $assistant->getHistory(),
            fn(array $message): bool => !str_starts_with((string)$message['content'], 'Conversation summary: '),
        ));
        $this->assertSame('assistant', $history[0]['role']);
        $this->assertCount(10, $history[0]['tool_calls']);
        $this->assertCount(11, $history);
        $this->assertSame('tool', $history[10]['role']);
    }

    public function testSetHistoryDropsOrphanedToolMessages(): void
    {
        $assistant = new AIAssistant();
        $assistant->setHistory([
            ['role' => 'tool', 'tool_call_id' => 'call_1', 'content' => '{}'],
            ['role' => 'tool', 'tool_call_id' => 'call_2', 'content' => '{}'],
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Calling', 'tool_calls' => [['id' => 'call_9', 'type' => 'function']]],
            ['role' => 'user', 'content' => 'Again'],
        ]);

        $history = $assistant->getHistory();
        $this->assertSame(['user', 'assistant', 'user'], array_column($history, 'role'));
        $this->assertArrayNotHasKey('tool_calls', $history[1]);
    }

    public function testNativeToolCallsEnabledForOpenAIByDefault(): void
    {
        $user = new User();
        $user->set('properties', json_encode(['ai_assistant' => ['provider' => 'openai']]));
        $assistant = new AIAssistant($user);

        $reflection = new ReflectionClass($assistant);
        $method = $reflection->getMethod('shouldUseNativeToolCalls');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($assistant));
    }

    public function testNativeToolCallsCanBeEnabledForNonOpenAIProviders(): void
    {
        $user = new User();
        $user->set('properties', json_encode(['ai_assistant' => ['provider' => 'local', 'native_tool_calls' => true]]));
        $assistant = new AIAssistant($user);

        $reflection = new ReflectionClass($assistant);
        $method = $reflection->getMethod('shouldUseNativeToolCalls');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($assistant));
    }

    public function testSelectToolsForRequestPrioritizesRelevantModuleAndCapsList(): void
    {
        $assistant = new AIAssistant();
        $reflection = new ReflectionClass($assistant);

        $toolsProperty = $reflection->getProperty('tools');
        $toolsProperty->setAccessible(true);
        $toolsProperty->setValue($assistant, [
            new AITool('Projects.search_projects', ['search' => ['type' => 'string']], 'Search projects'),
            new AITool('Projects.get_project', ['id' => ['type' => 'string']], 'Get project'),
            new AITool('Crm.search_contacts', ['search' => ['type' => 'string']], 'Search contacts'),
            new AITool('Crm.get_contact', ['id' => ['type' => 'string']], 'Get contact'),
            new AITool('Documents.search_documents', ['search' => ['type' => 'string']], 'Search documents'),
            new AITool('Documents.get_document', ['id' => ['type' => 'string']], 'Get document'),
            new AITool('Expenses.search_expenses', ['search' => ['type' => 'string']], 'Search expenses'),
            new AITool('Expenses.get_expense', ['id' => ['type' => 'string']], 'Get expense'),
            new AITool('Tasks.search_tasks', ['search' => ['type' => 'string']], 'Search tasks'),
            new AITool('Calendar.search_events', ['search' => ['type' => 'string']], 'Search events'),
        ]);

        $method = $reflection->getMethod('selectToolsForRequest');
        $method->setAccessible(true);

        $selectedTools = $method->invoke($assistant, 'list all active projects');
        $selectedNames = array_map(fn(AITool $tool): string => $tool->name, $selectedTools);

        $this->assertCount(
            2,
            array_filter($selectedNames, fn(string $name): bool => str_starts_with($name, 'Projects.')),
        );
        $this->assertContains('Projects.search_projects', $selectedNames);
        $this->assertLessThanOrEqual(8, count($selectedNames));
    }

    public function testCompactToolResultSummarizesLargeCollections(): void
    {
        $assistant = new AIAssistant();
        $reflection = new ReflectionClass($assistant);
        $method = $reflection->getMethod('compactToolResult');
        $method->setAccessible(true);

        $result = $method->invoke($assistant, 'Projects.search_projects', [
            ['id' => '1', 'title' => 'Alpha', 'no' => 'P-1', 'active' => true],
            ['id' => '2', 'title' => 'Beta', 'no' => 'P-2', 'active' => true],
            ['id' => '3', 'title' => 'Gamma', 'no' => 'P-3', 'active' => true],
            ['id' => '4', 'title' => 'Delta', 'no' => 'P-4', 'active' => false],
        ]);

        $this->assertSame('Returned 4 items', $result['summary']);
        $this->assertSame(4, $result['count']);
        $this->assertCount(4, $result['items']);
        $this->assertSame('Alpha', $result['items'][0]['title']);
    }

    public function testBuildToolResultSummaryMessageIncludesStructuredListData(): void
    {
        $assistant = new AIAssistant();
        $reflection = new ReflectionClass($assistant);
        $method = $reflection->getMethod('buildToolResultSummaryMessage');
        $method->setAccessible(true);

        $message = $method->invoke($assistant, 'Projects.search_projects', [
            [
                'id' => '1',
                'no' => '2024-01',
                'title' => 'Alpha',
                'active' => true,
                'milestones_open' => 2,
                'view_url' => '/projects/view/1',
            ],
            ['id' => '2', 'no' => '2024-02', 'title' => 'Beta', 'active' => true, 'milestones_open' => 1],
            ['id' => '3', 'no' => '2024-03', 'title' => 'Gamma', 'active' => true, 'milestones_open' => 4],
            ['id' => '4', 'no' => '2024-04', 'title' => 'Delta', 'active' => true, 'milestones_open' => 0],
        ]);

        $this->assertStringStartsWith('Tool result for Projects.search_projects: {', $message);
        $this->assertStringContainsString('"count":4', $message);
        $this->assertStringContainsString('"items"', $message);
        $this->assertStringContainsString('"title":"Alpha"', $message);
        $this->assertStringContainsString('"title":"Delta"', $message);

        $payload = json_decode((string)substr($message, strpos($message, ': ') + 2), true);
        $this->assertIsArray($payload);
        $this->assertSame('/projects/view/1', $payload['items'][0]['view_url']);
    }

    public function testSelectToolsForProjectLogsIncludesGetProjectLogs(): void
    {
        $assistant = new AIAssistant();
        $reflection = new ReflectionClass($assistant);

        $toolsProperty = $reflection->getProperty('tools');
        $toolsProperty->setAccessible(true);
        $toolsProperty->setValue($assistant, [
            new AITool('Projects.search_projects', ['search' => ['type' => 'string']], 'Search projects by title'),
            new AITool('Projects.get_project', ['id' => ['type' => 'string']], 'Fetch project details'),
            new AITool('Projects.get_project_tasks', ['project_id' => ['type' => 'string']], 'List project tasks'),
            new AITool('Projects.get_task', ['id' => ['type' => 'string']], 'Fetch task details'),
            new AITool('Projects.create_task', ['project_id' => ['type' => 'string']], 'Create task'),
            new AITool('Projects.update_task', ['id' => ['type' => 'string']], 'Update task'),
            new AITool('Projects.add_task_comment', ['task_id' => ['type' => 'string']], 'Add task comment'),
            new AITool('Projects.add_project_log', ['project_id' => ['type' => 'string']], 'Add project log'),
            new AITool('Projects.log_workhours', ['project_id' => ['type' => 'string']], 'Log workhours'),
            new AITool('Projects.create_milestone', ['project_id' => ['type' => 'string']], 'Create milestone'),
            new AITool('Projects.get_project_logs', ['project_id' => ['type' => 'string']], 'List project logs'),
            new AITool('Projects.get_project_users', ['project_id' => ['type' => 'string']], 'List project users'),
            new AITool('Projects.get_project_documents', ['project_id' => ['type' => 'string']], 'List project documents'),
        ]);

        $method = $reflection->getMethod('selectToolsForRequest');
        $method->setAccessible(true);

        $selectedTools = $method->invoke($assistant, 'show logs for project ProjectName');
        $selectedNames = array_map(fn(AITool $tool): string => $tool->name, $selectedTools);

        // When AI detection fails (no HTTP in tests), fallback keyword scoring still includes the logs tool.
        $this->assertContains('Projects.get_project_logs', $selectedNames);
    }

    public function testSelectToolsForListAllProjectsReturnsAllProjectsTools(): void
    {
        $assistant = new AIAssistant();
        $reflection = new ReflectionClass($assistant);

        $toolsProperty = $reflection->getProperty('tools');
        $toolsProperty->setAccessible(true);
        $toolsProperty->setValue($assistant, [
            new AITool('Projects.search_projects', ['search' => ['type' => 'string']], 'Lists or searches accessible projects. Call with no arguments to list all.'),
            new AITool('Projects.get_project', ['id' => ['type' => 'string']], 'Fetch project details'),
            new AITool('Projects.get_project_tasks', ['project_id' => ['type' => 'string']], 'List project tasks'),
            new AITool('Projects.get_task', ['id' => ['type' => 'string']], 'Fetch task details'),
            new AITool('Projects.create_task', ['project_id' => ['type' => 'string']], 'Create task'),
            new AITool('Projects.update_task', ['id' => ['type' => 'string']], 'Update task'),
            new AITool('Projects.add_task_comment', ['task_id' => ['type' => 'string']], 'Add task comment'),
            new AITool('Projects.add_project_log', ['project_id' => ['type' => 'string']], 'Add project log'),
            new AITool('Projects.log_workhours', ['project_id' => ['type' => 'string']], 'Log workhours'),
            new AITool('Projects.create_milestone', ['project_id' => ['type' => 'string']], 'Create milestone'),
            new AITool('Projects.get_project_logs', ['project_id' => ['type' => 'string']], 'List project logs'),
            new AITool('Projects.get_project_users', ['project_id' => ['type' => 'string']], 'List project users'),
            new AITool('Projects.get_project_documents', ['project_id' => ['type' => 'string']], 'List project documents'),
            new AITool('Crm.search_contacts', ['search' => ['type' => 'string']], 'Search contacts'),
            new AITool('Crm.get_contact', ['id' => ['type' => 'string']], 'Get contact'),
        ]);

        $method = $reflection->getMethod('selectToolsForRequest');
        $method->setAccessible(true);

        // When AI detection fails (no HTTP), keyword fallback still picks Projects over Crm.
        $selectedTools = $method->invoke($assistant, 'list all projects');
        $selectedNames = array_map(fn(AITool $tool): string => $tool->name, $selectedTools);

        $this->assertContains('Projects.search_projects', $selectedNames);
        $this->assertNotContains('Crm.search_contacts', $selectedNames);
    }

    public function testRoutingOfConfirmationKeepsOriginalRequestAndRoutesAllItsModules(): void
    {
        $assistant = new class extends AIAssistant {
            /**
             * @var array<int, string>
             */
            public array $routingPrompts = [];

            protected function doRequest(array $data, int $timeoutSeconds = 180): array
            {
                $this->routingPrompts[] = (string)$data['messages'][0]['content'];

                return ['content' => '["Documents", "Crm"]', 'finish_reason' => 'stop', 'tool_calls' => []];
            }
        };
        $reflection = new ReflectionClass(AIAssistant::class);
        $tools = [];
        foreach (['Projects', 'Crm', 'Documents', 'Tasks', 'Calendar'] as $module) {
            $tools[] = new AITool($module . '.search', [], 'Search');
            $tools[] = new AITool($module . '.create', [], 'Create');
        }
        $reflection->getProperty('tools')->setValue($assistant, $tools);
        $assistant->setHistory([
            ['role' => 'user', 'content' => 'Pripravi ponudbo in dodaj investitorja Janez Novak kot kontakt'],
            ['role' => 'assistant', 'content' => '', 'tool_calls' => [
                ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'Documents__search', 'arguments' => '{}']],
            ]],
            ['role' => 'tool', 'tool_call_id' => 'c1', 'content' => '{"tool":"Documents.search","result":[]}'],
            ['role' => 'assistant', 'content' => 'Predogled ponudbe. Naj jo shranim?'],
            ['role' => 'user', 'content' => 'da'],
        ]);

        $selected = $reflection->getMethod('selectToolsForRequest')->invoke($assistant, 'da');
        $names = array_map(fn(AITool $tool): string => $tool->name, $selected);

        $this->assertStringContainsString('dodaj investitorja Janez Novak', $assistant->routingPrompts[0]);
        $this->assertContains('Crm.create', $names);
        $this->assertContains('Documents.create', $names);
    }

    public function testDetectModulesViaAIParsesJsonArrayResponse(): void
    {
        $assistant = new AIAssistant();
        $reflection = new ReflectionClass($assistant);

        $method = $reflection->getMethod('detectModulesViaAI');
        $method->setAccessible(true);

        // Stub doRequest to return a pre-built response without hitting the network.
        $doRequest = $reflection->getMethod('doRequest');
        $doRequest->setAccessible(true);

        // Replace doRequest with a closure that returns a fixed response.
        // We achieve this by partially overriding via a mock subclass created at runtime.
        $mockAssistant = new class extends AIAssistant {
            public string $stubbedContent = '';

            protected function doRequest(array $data, int $timeoutSeconds = 180): array
            {
                return ['content' => $this->stubbedContent, 'finish_reason' => 'stop', 'tool_calls' => []];
            }
        };

        $toolsProperty = $reflection->getProperty('tools');
        $toolsProperty->setAccessible(true);
        $toolsProperty->setValue($mockAssistant, []);

        $mockReflection = new ReflectionClass($mockAssistant);
        $detectMethod = $mockReflection->getMethod('detectModulesViaAI');
        $detectMethod->setAccessible(true);

        // Exact JSON array response.
        $mockAssistant->stubbedContent = '["Projects"]';
        $result = $detectMethod->invoke($mockAssistant, ['Projects', 'Crm', 'Documents'], [], 'prikaži projekte');
        $this->assertSame(['Projects'], $result);

        // JSON wrapped in markdown fences.
        $mockAssistant->stubbedContent = "```json\n[\"Crm\"]\n```";
        $result = $detectMethod->invoke($mockAssistant, ['Projects', 'Crm', 'Documents'], [], 'pokaži kontakte');
        $this->assertSame(['Crm'], $result);

        // Unknown module name is filtered out.
        $mockAssistant->stubbedContent = '["Unknown"]';
        $result = $detectMethod->invoke($mockAssistant, ['Projects', 'Crm'], [], 'something');
        $this->assertSame([], $result);

        // Empty array.
        $mockAssistant->stubbedContent = '[]';
        $result = $detectMethod->invoke($mockAssistant, ['Projects', 'Crm'], [], 'hello');
        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // Empty replies and long tool results
    // -------------------------------------------------------------------------

    /**
     * Assistant whose model returns the given contents one after another.
     *
     * @param array<int, string> $contents Replies of the model.
     * @return \App\Lib\AIAssistant
     */
    private function assistantReplying(array $contents): AIAssistant
    {
        return new class ($contents) extends AIAssistant {
            /**
             * @var array<int, array<string, mixed>>
             */
            public array $requests = [];

            /**
             * @param array<int, string> $contents
             */
            public function __construct(private array $contents)
            {
                parent::__construct();
            }

            protected function doRequest(array $data, int $timeoutSeconds = 180): array
            {
                $this->requests[] = $data;

                return [
                    'content' => array_shift($this->contents) ?? '',
                    'finish_reason' => 'stop',
                    'tool_calls' => [],
                ];
            }
        };
    }

    public function testEmptyReplyIsRetriedOnce(): void
    {
        $assistant = $this->assistantReplying(['', 'Here is the answer.']);

        $this->assertSame('Here is the answer.', $assistant->getResponse('Hello'));

        $this->assertCount(2, $assistant->requests);
        $lastMessage = end($assistant->requests[1]['messages']);
        $this->assertStringContainsString('previous reply was empty', $lastMessage['content']);

        // The nudge is not stored in the conversation history.
        $contents = array_column($assistant->getHistory(), 'content');
        $this->assertNotContains($lastMessage['content'], $contents);
    }

    public function testEmptyReplyIsNeverReturnedOrStored(): void
    {
        $assistant = $this->assistantReplying(['', '']);

        $reply = $assistant->getResponse('Hello');

        $this->assertNotSame('', $reply);
        foreach ($assistant->getHistory() as $message) {
            $this->assertNotSame('', trim((string)$message['content']));
        }
    }

    public function testOldToolMessagesAreShortenedButRepliesAreKept(): void
    {
        $longText = str_repeat('Zahteva. ', 1000);
        $longAnswer = str_repeat('Odgovor. ', 500);
        $assistant = $this->assistantReplying(['Short answer.']);
        $assistant->setHistory([
            ['role' => 'user', 'content' => 'Read the attachment'],
            ['role' => 'assistant', 'content' => json_encode([
                'tool' => 'Documents.update_document',
                'arguments' => ['id' => 'd1', 'descript' => $longText],
            ])],
            ['role' => 'user', 'content' => 'Tool result for App.read_attachment: ' . $longText],
            ['role' => 'assistant', 'content' => $longAnswer],
        ]);

        $assistant->getResponse('Make it shorter');

        // [0] system prompt, [1] user, [2] tool call, [3] tool result, [4] earlier reply, [5] new question.
        $sent = $assistant->requests[0]['messages'];
        $toolCall = json_decode($sent[2]['content'], true);
        $this->assertSame('Documents.update_document', $toolCall['tool']);
        $this->assertLessThan(500, mb_strlen($sent[2]['content']));
        $this->assertLessThan(1600, mb_strlen($sent[3]['content']));
        $this->assertStringContainsString('[truncated]', $sent[3]['content']);
        $this->assertSame($longAnswer, $sent[4]['content']);
    }

    public function testUpToTenToolCallsAreExecutedPerRequest(): void
    {
        $toolCall = '{"tool": "Unknown.tool", "arguments": {}}';
        $assistant = $this->assistantReplying(array_merge(array_fill(0, 7, $toolCall), ['Final answer.']));

        $this->assertSame('Final answer.', $assistant->getResponse('Do many things'));
        $this->assertCount(8, $assistant->requests);
    }

    public function testToolCallsStopAfterTenAndNeverExposeRawJson(): void
    {
        $toolCall = '{"tool": "Unknown.tool", "arguments": {}}';
        $assistant = $this->assistantReplying(array_fill(0, 12, $toolCall));

        $this->assertSame('Done.', $assistant->getResponse('Loop forever'));
        $this->assertCount(11, $assistant->requests);
    }

    // -------------------------------------------------------------------------
    // Tool catalog and load_tools
    // -------------------------------------------------------------------------

    /**
     * Assistant with native tool calls and thirteen fake tools, whose model returns the given messages.
     *
     * @param array<int, array<string, mixed>> $replies Messages of the model.
     * @return \App\Lib\AIAssistant
     */
    private function catalogAssistant(array $replies): AIAssistant
    {
        $user = new User();
        $user->set('properties', json_encode(['ai_assistant' => ['provider' => 'openai']]));
        $assistant = new class ($user, $replies) extends AIAssistant {
            /**
             * @var array<int, array<string, mixed>>
             */
            public array $requests = [];

            /**
             * @param \App\Model\Entity\User $user
             * @param array<int, array<string, mixed>> $replies
             */
            public function __construct(User $user, private array $replies)
            {
                parent::__construct($user);
            }

            protected function doRequest(array $data, int $timeoutSeconds = 180): array
            {
                $this->requests[] = $data;

                return array_shift($this->replies) + ['content' => '', 'finish_reason' => 'stop', 'tool_calls' => []];
            }
        };

        $tools = [new AITool('App.vector_search', [], 'Searches everything.')];
        foreach (['Fake', 'Other'] as $module) {
            for ($index = 1; $index <= 6; $index++) {
                $tools[] = new AITool(
                    "$module.tool$index",
                    ['id' => ['type' => 'string']],
                    "Does $module thing $index. Second sentence is not in the catalog.",
                );
            }
        }
        $tools[] = new AITool('Fake.create', ['title' => ['type' => 'string']], 'Long text.', 'Creates a fake.');
        (new ReflectionClass(AIAssistant::class))->getProperty('tools')->setValue($assistant, $tools);

        return $assistant;
    }

    /**
     * @param string $name Tool name as sent to the model.
     * @param array<string, mixed> $arguments Arguments.
     * @return array<string, mixed> Model message calling the tool.
     */
    private function callTool(string $name, array $arguments = []): array
    {
        return [
            'content' => '',
            'finish_reason' => 'tool_calls',
            'tool_calls' => [[
                'id' => 'call_' . uniqid(),
                'type' => 'function',
                'function' => ['name' => $name, 'arguments' => (string)json_encode($arguments)],
            ]],
        ];
    }

    /**
     * @param array<int, string> $executed Receives names of the executed tools.
     * @return callable
     */
    private function listenToTools(array &$executed): callable
    {
        $listener = function (EventInterface $event, string $tool) use (&$executed): void {
            $executed[] = $tool;
            $event->setResult(['ok' => true]);
        };
        EventManager::instance()->on('App.AIAssistant.executeTool', $listener);

        return $listener;
    }

    /**
     * @param array<string, mixed> $request Request sent to the model.
     * @return array<int, string>
     */
    private function offeredTools(array $request): array
    {
        return array_map(fn(array $tool): string => $tool['function']['name'], $request['tools'] ?? []);
    }

    public function testCatalogListsAllToolsAndOffersOnlyLoadedOnes(): void
    {
        $assistant = $this->catalogAssistant([
            $this->callTool('load_tools', ['names' => ['Fake.create', 'Missing.tool']]),
            $this->callTool('Fake__create', ['title' => 'X']),
            ['content' => 'Ustvarjeno.'],
        ]);
        $executed = [];
        $listener = $this->listenToTools($executed);

        try {
            $this->assertSame('Ustvarjeno.', $assistant->getResponse('Ustvari fake'));
        } finally {
            EventManager::instance()->off('App.AIAssistant.executeTool', $listener);
        }

        $system = $assistant->requests[0]['messages'][0]['content'];
        $this->assertStringContainsString('- Other.tool6: Does Other thing 6.', $system);
        $this->assertStringNotContainsString('Second sentence', $system);
        $this->assertStringContainsString('- Fake.create: Creates a fake.', $system);
        $this->assertSame(['load_tools', 'App__vector_search'], $this->offeredTools($assistant->requests[0]));
        $this->assertContains('Fake__create', $this->offeredTools($assistant->requests[1]));

        // load_tools is handled by the assistant, only the real tool reaches the listeners.
        $this->assertSame(['Fake.create'], $executed);
        $this->assertStringContainsString('Missing.tool', (string)json_encode($assistant->requests[1]['messages']));
        $this->assertSame(['Fake.create'], $assistant->getLoadedTools());

        // The date comes after the catalog, so the start of the prompt stays the same.
        $this->assertGreaterThan(strpos($system, 'Tool catalog'), strpos($system, 'Current date and time'));
    }

    public function testToolCalledWithoutLoadingIsLoadedButNotRun(): void
    {
        $assistant = $this->catalogAssistant([
            $this->callTool('Fake__tool2', ['id' => '1']),
            $this->callTool('Nope__tool'),
            ['content' => 'Ni mogoče.'],
        ]);
        $executed = [];
        $listener = $this->listenToTools($executed);

        try {
            $assistant->getResponse('Naredi nekaj');
        } finally {
            EventManager::instance()->off('App.AIAssistant.executeTool', $listener);
        }

        $this->assertSame([], $executed);
        $this->assertContains('Fake__tool2', $this->offeredTools($assistant->requests[1]));
        $toolMessages = array_values(array_filter(
            $assistant->requests[2]['messages'],
            fn(array $message): bool => $message['role'] === 'tool',
        ));
        $this->assertStringContainsString('was not loaded', $toolMessages[0]['content']);
        $this->assertStringContainsString('Unknown tool', $toolMessages[1]['content']);
    }

    public function testLoadedToolsStayOfferedInNextTurn(): void
    {
        $assistant = $this->catalogAssistant([['content' => 'Shranjeno.']]);
        $assistant->setLoadedTools(['Other.tool3', 'Missing.tool']);

        $assistant->getResponse('da');

        $this->assertContains('Other__tool3', $this->offeredTools($assistant->requests[0]));
        $this->assertSame(['Other.tool3'], $assistant->getLoadedTools());
    }

    public function testCatalogSummaryIsShortAndKeepsAbbreviations(): void
    {
        $assistant = new AIAssistant();
        $reflection = new ReflectionClass(AIAssistant::class);
        $reflection->getProperty('tools')->setValue($assistant, [
            new AITool('Fake.abbrev', [], 'Writes text, e.g. a summary of a document. Second sentence.'),
            new AITool('Fake.long', [], str_repeat('Very long description ', 20) . 'end.'),
        ]);

        $catalog = (string)$reflection->getMethod('buildToolCatalog')->invoke($assistant);

        $this->assertStringContainsString('- Fake.abbrev: Writes text, e.g. a summary of a document.', $catalog);
        $this->assertStringNotContainsString('Second sentence', $catalog);
        $this->assertSame(1, preg_match('/- Fake\.long: (.*)/', $catalog, $matches));
        $this->assertLessThanOrEqual(AIAssistant::MAX_CATALOG_SUMMARY_CHARS, mb_strlen($matches[1] ?? ''));
    }

    public function testToolCatalogCanBeTurnedOff(): void
    {
        $user = new User();
        $user->set('properties', json_encode(['ai_assistant' => ['provider' => 'openai', 'tool_catalog' => false]]));
        $assistant = new AIAssistant($user);
        $reflection = new ReflectionClass(AIAssistant::class);
        $reflection->getProperty('tools')->setValue(
            $assistant,
            array_map(fn(int $i): AITool => new AITool("Fake.t$i", [], 'Tool.'), range(1, 12)),
        );

        $this->assertFalse($reflection->getMethod('useToolCatalog')->invoke($assistant));
    }

    /**
     * Every instruction whose answer is shown to a person asks for Slovenian.
     *
     * @return void
     */
    public function testInstructionsRequireSlovenian(): void
    {
        $this->assertStringContainsString('Slovenian', AIAssistant::SYSTEM_PROMPT);
        $this->assertStringContainsString('Slovenian', AIAssistant::LANGUAGE_RULE);
        $this->assertSame(0, substr_count(sprintf(AIAssistant::SYSTEM_PROMPT, 'now', 'user', 'tools'), '%'));

        $job = (string)file_get_contents(dirname(__DIR__, 3) . '/src/Job/AiProcessLogJob.php');
        $this->assertStringContainsString('AIAssistant::LANGUAGE_RULE', $job);
        $this->assertStringContainsString('must be in', $job);
        $search = (string)file_get_contents(dirname(__DIR__, 3) . '/src/Lib/VectorDBSearchTool.php');
        $this->assertStringContainsString('AIAssistant::LANGUAGE_RULE', $search);
    }
}
