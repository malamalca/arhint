<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\AIAssistant;
use App\Lib\AITool;
use App\Model\Entity\User;
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

    public function testNativeToolCallsEnabledForOpenAIByDefault(): void
    {
        $user = new User();
        $user->set('ai_assistant', (object)['provider' => 'openai']);
        $assistant = new AIAssistant($user);

        $reflection = new ReflectionClass($assistant);
        $method = $reflection->getMethod('shouldUseNativeToolCalls');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($assistant));
    }

    public function testNativeToolCallsCanBeEnabledForNonOpenAIProviders(): void
    {
        $user = new User();
        $user->set('ai_assistant', (object)['provider' => 'local', 'native_tool_calls' => true]);
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

            protected function doRequest(array $data): array // @phpstan-ignore-line
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
}
