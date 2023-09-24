<?php
declare(strict_types=1);

namespace Tasks\Test\TestCase\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * TasksFolders\Controller\TasksController Test Case
 */
class TasksFoldersControllerTest extends TestCase
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
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/tasks/tasks-folders/edit/6a4b457b-0394-4b5b-a4b9-d0f602c4d98f');
        $this->assertResponseOk();

        $this->get('/tasks/tasks-folders/edit');
        $this->assertResponseOk();

        $data = [
            'id' => '6a4b457b-0394-4b5b-a4b9-d0f602c4d98f',
            'owner_id' => COMPANY_FIRST,
            'title' => 'Random Renamed Task Folder',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/tasks/tasks-folders/edit/6a4b457b-0394-4b5b-a4b9-d0f602c4d98f', $data);
        $this->assertRedirect(['controller' => 'Tasks', 'action' => 'index']);
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

        $this->get('/tasks/tasks-folders/delete/6a4b457b-0394-4b5b-a4b9-d0f602c4d98f');
        $this->assertRedirect(['controller' => 'Tasks', 'action' => 'index']);

        $this->disableErrorHandlerMiddleware();
        $this->expectException(RecordNotFoundException::class);
        TableRegistry::getTableLocator()->get('Tasks.TasksFolders')->get('6a4b457b-0394-4b5b-a4b9-d0f602c4d98f');
    }
}
