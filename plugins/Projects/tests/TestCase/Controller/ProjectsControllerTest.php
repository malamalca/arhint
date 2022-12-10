<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Projects\Controller\ProjectsController Test Case
 */
class ProjectsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Users',
        'Projects' => 'plugin.Projects.Projects',
        'ProjectsUsers' => 'plugin.Projects.ProjectsUsers',
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

        $this->get('/projects/projects/index');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/projects/projects/view/4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseOk();
    }

    /**
     * Test view common user method
     *
     * @return void
     */
    public function testViewCommonUser()
    {
        // Set session data
        $this->login(USER_COMMON);

        $this->get('/projects/projects/view/4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseError();
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

        $this->get('/projects/projects/edit/4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseOk();

        $this->get('/projects/projects/edit');
        $this->assertResponseOk();

        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => '4dd53305-9715-4be4-b169-20defe113d2a',
            'owner_id' => COMPANY_FIRST,
            'status_id' => null,
            'no' => '2022-01',
            'title' => 'Edited Project Title',
            'lat' => null,
            'lon' => null,
            'colorize' => null,
            'ico' => null,
            'active' => 1,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/projects/projects/edit/4dd53305-9715-4be4-b169-20defe113d2a', $data);
        $this->assertRedirect(['action' => 'view', '4dd53305-9715-4be4-b169-20defe113d2a']);
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

        $this->get('/projects/projects/edit/4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseError();
    }

    /**
     * Test user method
     *
     * @return void
     */
    public function testUser()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/projects/projects/user/invalidUuid');
        $this->assertResponseError();

        $this->get('/projects/projects/user/4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseOk();

        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => null,
            'project_id' => '4dd53305-9715-4be4-b169-20defe113d2a',
            'user_id' => USER_COMMON,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/projects/projects/user/4dd53305-9715-4be4-b169-20defe113d2a', $data);
        $this->assertRedirect(['action' => 'view', '4dd53305-9715-4be4-b169-20defe113d2a', '?' => ['tab' => 'users']]);
    }

    /**
     * Test delete user method
     *
     * @return void
     */
    public function testDeleteUser()
    {
        $projectsUsersTable = TableRegistry::getTableLocator()->get('ProjectsUsers');
        $pu = $projectsUsersTable->newEmptyEntity();
        $pu->project_id = '4dd53305-9715-4be4-b169-20defe113d2a';
        $pu->user_id = USER_COMMON;
        $projectsUsersTable->save($pu);

        $this->login(USER_ADMIN);

        $this->get('/projects/projects/deleteUser/4dd53305-9715-4be4-b169-20defe113d2a/' . USER_COMMON);
        $this->assertRedirect(['action' => 'view', '4dd53305-9715-4be4-b169-20defe113d2a', '?' => ['tab' => 'users']]);
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

        $this->get('/projects/projects/delete/4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertRedirect();
    }

    /**
     * Test delete common user method
     *
     * @return void
     */
    public function testDeleteCommonUser()
    {
        // Set session data
        $this->login(USER_COMMON);

        $this->get('/projects/projects/delete/4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseError();
    }
}
