<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Projects\Controller\ProjectsLogsController Test Case
 */
class ProjectsLogsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Users',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsLogs', 
        'plugin.Projects.ProjectsUsers', 
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
        $this->login(USER_ADMIN);

        $this->get('projects/projects-logs/edit/0cc5e896-da92-4408-af50-9eaf1d4cc50b');
        $this->assertResponseOk();

        $this->get('projects/projects-logs/edit?project=4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseOk();

        $data = [
            'id' => '0cc5e896-da92-4408-af50-9eaf1d4cc50b',
            'project_id' => '4dd53305-9715-4be4-b169-20defe113d2a',
            'user_id' => USER_ADMIN,
            'descript' => 'Edited Text',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('projects/projects-logs/edit/0cc5e896-da92-4408-af50-9eaf1d4cc50b', $data);
        $this->assertRedirect(['controller' => 'Projects', 'action' => 'view', '4dd53305-9715-4be4-b169-20defe113d2a', '?' => ['tab' => 'logs']]);
    }

    /**
     * Test edit common user method
     *
     * @return void
     */
    public function testEditCommonUser()
    {
        // Set session data
        $this->login(USER_COMMON);

        $this->get('projects/projects-logs/edit/4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseError();

        $this->get('projects/projects-logs/edit?project=4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseError();

        $this->get('projects/projects-logs/edit?project=4dd53305-9715-4be4-b169-20defe113d2b');
        $this->assertResponseOk();

        $data = [
            'descript' => 'New Log Entry',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('projects/projects-logs/edit?project=4dd53305-9715-4be4-b169-20defe113d2b', $data);
        $this->assertRedirect(['controller' => 'Projects', 'action' => 'view', '4dd53305-9715-4be4-b169-20defe113d2b', '?' => ['tab' => 'logs']]);
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

        $this->get('projects/projects-logs/delete/0cc5e896-da92-4408-af50-9eaf1d4cc50b');
        $this->assertRedirect();
    }
}
