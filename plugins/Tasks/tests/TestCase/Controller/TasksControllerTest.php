<?php
declare(strict_types=1);

namespace Tasks\Test\TestCase\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Tasks\Controller\TasksController Test Case
 */
class TasksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'plugin.Tasks.Tasks',
        'plugin.Tasks.TasksFolders',
        'app.Users',
    ];

    /**
     * Login method
     *
     * @var string $userId User id
     * @return void
     */
    private function login($userId)
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/tasks/tasks/index');
        $this->assertResponseOk();

        $this->get('/tasks/tasks/index?folder=6a4b457b-0394-4b5b-a4b9-d0f602c4d98f');
        $this->assertResponseOk();

        $this->get('/tasks/tasks/index?due=tomorrow');
        $this->assertResponseOk();

        $this->get('/tasks/tasks/index?completed=only');
        $this->assertResponseOk();
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/tasks/tasks/edit/b3a635df-c8dc-4fc4-82a9-062002007571');
        $this->assertResponseOk();

        $this->get('/tasks/tasks/edit');
        $this->assertResponseOk();

        $data = [
            'id' => 'b3a635df-c8dc-4fc4-82a9-062002007571',
            'owner_id' => COMPANY_FIRST,
            'folder_id' => '6a4b457b-0394-4b5b-a4b9-d0f602c4d98f',
            'user_id' => USER_ADMIN,
            'tasker_id' => USER_ADMIN,
            'title' => 'New Task Title',
            'descript' => 'This is a simple Task',
            'started' => '2015-12-07',
            'deadline' => '2015-12-07',
            'completed' => null,
            'priority' => 1,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/tasks/tasks/edit/b3a635df-c8dc-4fc4-82a9-062002007571', $data);
        $this->assertRedirect(['action' => 'index']);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testToggle()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/tasks/tasks/toggle/b3a635df-c8dc-4fc4-82a9-062002007571');
        $this->assertRedirect(['action' => 'index']);

        $task = TableRegistry::getTableLocator()->get('Tasks.Tasks')->get('b3a635df-c8dc-4fc4-82a9-062002007571');
        $this->assertNotEmpty($task->completed);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/tasks/tasks/delete/b3a635df-c8dc-4fc4-82a9-062002007571');
        $this->assertRedirect(['action' => 'index']);

        $this->disableErrorHandlerMiddleware();
        $this->expectException(RecordNotFoundException::class);
        TableRegistry::getTableLocator()->get('Tasks.Tasks')->get('b3a635df-c8dc-4fc4-82a9-062002007571');
    }
}
