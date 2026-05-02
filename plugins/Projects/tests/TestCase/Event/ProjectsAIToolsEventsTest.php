<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Event;

use App\Model\Entity\User;
use ArrayObject;
use Authorization\AuthorizationServiceInterface;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Projects\Event\ProjectsAIToolsEvents;

/**
 * Projects\Event\ProjectsAIToolsEvents Test Case
 */
class ProjectsAIToolsEventsTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsLogs',
        'plugin.Projects.ProjectsMilestones',
        'plugin.Projects.ProjectsTasks',
        'plugin.Projects.ProjectsWorkhours',
        'plugin.Projects.ProjectsUsers',
    ];

    protected ProjectsAIToolsEvents $listener;
    protected User $user;

    /** Fixture project IDs */
    private const PROJECT_1 = '4dd53305-9715-4be4-b169-20defe113d2a';
    private const PROJECT_2 = '4dd53305-9715-4be4-b169-20defe113d2b';

    public function setUp(): void
    {
        parent::setUp();
        $this->listener = new ProjectsAIToolsEvents();

        $authService = $this->createMock(AuthorizationServiceInterface::class);
        $authService->method('applyScope')
            ->willReturnCallback(fn($identity, $action, $resource) => $resource);
        $authService->method('can')
            ->willReturn(true);

        $this->user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $this->user->setAuthorization($authService);
    }

    // -------------------------------------------------------------------------
    // implementedEvents
    // -------------------------------------------------------------------------

    public function testImplementedEvents(): void
    {
        $events = $this->listener->implementedEvents();

        $this->assertArrayHasKey('App.AIAssistant.tools', $events);
        $this->assertArrayHasKey('App.AIAssistant.executeTool', $events);
        $this->assertCount(2, $events);
        $this->assertEquals('aiAssistantTools', $events['App.AIAssistant.tools']);
        $this->assertEquals('aiAssistantExecuteTool', $events['App.AIAssistant.executeTool']);
    }

    // -------------------------------------------------------------------------
    // aiAssistantTools — tool registration
    // -------------------------------------------------------------------------

    public function testAiAssistantToolsRegisters10Tools(): void
    {
        $event = new Event('App.AIAssistant.tools');
        $toolsList = new ArrayObject();
        $this->listener->aiAssistantTools($event, $toolsList);

        $this->assertCount(10, $toolsList);

        $names = array_map(fn($t) => $t->name, iterator_to_array($toolsList));
        $this->assertContains('Projects.search_projects', $names);
        $this->assertContains('Projects.get_project', $names);
        $this->assertContains('Projects.get_project_tasks', $names);
        $this->assertContains('Projects.get_task', $names);
        $this->assertContains('Projects.create_task', $names);
        $this->assertContains('Projects.update_task', $names);
        $this->assertContains('Projects.add_task_comment', $names);
        $this->assertContains('Projects.add_project_log', $names);
        $this->assertContains('Projects.log_workhours', $names);
        $this->assertContains('Projects.create_milestone', $names);
    }

    // -------------------------------------------------------------------------
    // search_projects
    // -------------------------------------------------------------------------

    public function testSearchProjectsReturnsArray(): void
    {
        $event = $this->makeEvent('Projects.search_projects', []);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.search_projects', []);

        $this->assertIsArray($event->getResult());
    }

    public function testSearchProjectsWithSearchTerm(): void
    {
        $args = ['search' => 'First Project'];
        $event = $this->makeEvent('Projects.search_projects', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.search_projects', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(self::PROJECT_1, $result[0]->id);
    }

    // -------------------------------------------------------------------------
    // get_project
    // -------------------------------------------------------------------------

    public function testGetProjectFound(): void
    {
        $args = ['id' => self::PROJECT_1];
        $event = $this->makeEvent('Projects.get_project', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.get_project', $args);

        $result = $event->getResult();
        $this->assertNotNull($result);
        $this->assertIsNotArray($result);
        $this->assertEquals(self::PROJECT_1, $result->id);
        $this->assertEquals('First Project Title', $result->title);
    }

    public function testGetProjectNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Projects.get_project', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.get_project', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // get_project_tasks
    // -------------------------------------------------------------------------

    public function testGetProjectTasksReturnsArray(): void
    {
        $args = ['project_id' => self::PROJECT_1];
        $event = $this->makeEvent('Projects.get_project_tasks', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.get_project_tasks', $args);

        $this->assertIsArray($event->getResult());
    }

    public function testGetProjectTasksProjectNotFound(): void
    {
        $args = ['project_id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Projects.get_project_tasks', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.get_project_tasks', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // get_task
    // -------------------------------------------------------------------------

    public function testGetTaskNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000'];
        $event = $this->makeEvent('Projects.get_task', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.get_task', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not found', $result['error']);
    }

    // -------------------------------------------------------------------------
    // create_task
    // -------------------------------------------------------------------------

    public function testCreateTask(): void
    {
        $args = ['project_id' => self::PROJECT_1, 'title' => 'New Test Task'];
        $event = $this->makeEvent('Projects.create_task', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.create_task', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('New Test Task', $result['title']);
    }

    public function testCreateTaskProjectNotFound(): void
    {
        $args = ['project_id' => '00000000-0000-0000-0000-000000000000', 'title' => 'Task'];
        $event = $this->makeEvent('Projects.create_task', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.create_task', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // update_task
    // -------------------------------------------------------------------------

    public function testUpdateTaskNotFound(): void
    {
        $args = ['id' => '00000000-0000-0000-0000-000000000000', 'title' => 'Updated'];
        $event = $this->makeEvent('Projects.update_task', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.update_task', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testUpdateTaskUpdatesTitle(): void
    {
        // First create a task, then update it
        $createArgs = ['project_id' => self::PROJECT_1, 'title' => 'Original Title'];
        $createEvent = $this->makeEvent('Projects.create_task', $createArgs);
        $this->listener->aiAssistantExecuteTool($createEvent, 'Projects.create_task', $createArgs);
        $taskId = $createEvent->getResult()['id'];

        $args = ['id' => $taskId, 'title' => 'Updated Title'];
        $event = $this->makeEvent('Projects.update_task', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.update_task', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Updated Title', $result['title']);
    }

    public function testUpdateTaskClose(): void
    {
        $createArgs = ['project_id' => self::PROJECT_1, 'title' => 'Task To Close'];
        $createEvent = $this->makeEvent('Projects.create_task', $createArgs);
        $this->listener->aiAssistantExecuteTool($createEvent, 'Projects.create_task', $createArgs);
        $taskId = $createEvent->getResult()['id'];

        $args = ['id' => $taskId, 'closed' => true];
        $event = $this->makeEvent('Projects.update_task', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.update_task', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    // -------------------------------------------------------------------------
    // add_task_comment
    // -------------------------------------------------------------------------

    public function testAddTaskCommentTaskNotFound(): void
    {
        $args = ['task_id' => '00000000-0000-0000-0000-000000000000', 'descript' => 'Hi'];
        $event = $this->makeEvent('Projects.add_task_comment', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.add_task_comment', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testAddTaskComment(): void
    {
        $createArgs = ['project_id' => self::PROJECT_1, 'title' => 'Task For Comment'];
        $createEvent = $this->makeEvent('Projects.create_task', $createArgs);
        $this->listener->aiAssistantExecuteTool($createEvent, 'Projects.create_task', $createArgs);
        $taskId = $createEvent->getResult()['id'];

        $args = ['task_id' => $taskId, 'descript' => 'This is a comment'];
        $event = $this->makeEvent('Projects.add_task_comment', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.add_task_comment', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    // -------------------------------------------------------------------------
    // add_project_log
    // -------------------------------------------------------------------------

    public function testAddProjectLog(): void
    {
        $args = ['project_id' => self::PROJECT_1, 'descript' => 'Test log entry'];
        $event = $this->makeEvent('Projects.add_project_log', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.add_project_log', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testAddProjectLogProjectNotFound(): void
    {
        $args = ['project_id' => '00000000-0000-0000-0000-000000000000', 'descript' => 'x'];
        $event = $this->makeEvent('Projects.add_project_log', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.add_project_log', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // log_workhours
    // -------------------------------------------------------------------------

    public function testLogWorkhours(): void
    {
        $args = ['project_id' => self::PROJECT_1, 'duration' => 60, 'descript' => 'Test work'];
        $event = $this->makeEvent('Projects.log_workhours', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.log_workhours', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(60 * 60, $result['duration']);
    }

    public function testLogWorkhoursProjectNotFound(): void
    {
        $args = ['project_id' => '00000000-0000-0000-0000-000000000000', 'duration' => 30];
        $event = $this->makeEvent('Projects.log_workhours', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.log_workhours', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // create_milestone
    // -------------------------------------------------------------------------

    public function testCreateMilestone(): void
    {
        $args = ['project_id' => self::PROJECT_1, 'title' => 'Milestone Alpha', 'date_due' => '2025-12-31'];
        $event = $this->makeEvent('Projects.create_milestone', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.create_milestone', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Milestone Alpha', $result['title']);
    }

    public function testCreateMilestoneProjectNotFound(): void
    {
        $args = ['project_id' => '00000000-0000-0000-0000-000000000000', 'title' => 'M1'];
        $event = $this->makeEvent('Projects.create_milestone', $args);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.create_milestone', $args);

        $result = $event->getResult();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // -------------------------------------------------------------------------
    // Unknown tool
    // -------------------------------------------------------------------------

    public function testUnknownToolDoesNothing(): void
    {
        $event = $this->makeEvent('Projects.nonexistent_tool', []);
        $this->listener->aiAssistantExecuteTool($event, 'Projects.nonexistent_tool', []);

        $this->assertNull($event->getResult());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEvent(string $tool, array $arguments): Event
    {
        return new Event('App.AIAssistant.executeTool', null, [$tool, $arguments, $this->user]);
    }
}
