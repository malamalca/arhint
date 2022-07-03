<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Projects\Controller\ProjectsWorkhoursController Test Case
 */
class ProjectsWorkhoursControllerTest extends TestCase
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
        'plugin.Projects.ProjectsWorkhours',
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
        $this->login(USER_ADMIN);

        $this->get('projects/projects-workhours/index');
        $this->assertResponseOk();
    }

    /**
     * Test list method
     *
     * @return void
     */
    public function testList()
    {
        $this->login(USER_ADMIN);

        $this->get('projects/projects-workhours/list');
        $this->assertResponseOk();
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        $this->login(USER_ADMIN);

        $this->get('projects/projects-workhours/edit/a1895b24-5809-40cb-9670-302a37aa35bf');
        $this->assertResponseOk();

        $this->get('projects/projects-workhours/edit?project=4dd53305-9715-4be4-b169-20defe113d2a');
        $this->assertResponseOk();

        $data = [
            'id' => 'a1895b24-5809-40cb-9670-302a37aa35bf',
            'project_id' => '4dd53305-9715-4be4-b169-20defe113d2a',
            'user_id' => USER_ADMIN,
            'started' => '2018-02-27 06:33:56',
            'duration' => 5*60*60,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('projects/projects-workhours/edit/a1895b24-5809-40cb-9670-302a37aa35bf', $data);
        $this->assertRedirect(['controller' => 'ProjectsWorkhours', 'action' => 'index', '?' => ['project' => '4dd53305-9715-4be4-b169-20defe113d2a']]);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->login(USER_ADMIN);

        $this->get('projects/projects-workhours/delete/a1895b24-5809-40cb-9670-302a37aa35bf');
        $this->assertRedirect();
    }

    /**
     * Test import method
     *
     * @return void
     */
    public function testImport()
    {
        $this->configRequest([
            'environment' => [
                'PHP_AUTH_USER' => 'admin',
                'PHP_AUTH_PW' => 'pass',
            ],
        ]);

        $data = ['data' => [
            0 => [
                'mode' => 'start',
                'project_id' => '4dd53305-9715-4be4-b169-20defe113d2a',
                'datetime' => '2018-02-27 06:33:56',
            ],
            1 => [
                'mode' => 'stop',
                'project_id' => '4dd53305-9715-4be4-b169-20defe113d2a',
                'datetime' => '2018-02-27 07:33:56',
            ],
        ]];

        $this->post('projects/projects-workhours/import', $data);
        $this->assertResponseOk();

        $workhours = TableRegistry::getTableLocator()
            ->get('Projects.ProjectsWorkhours')
            ->getTotalDuration('4dd53305-9715-4be4-b169-20defe113d2a');

        $this->assertEquals(5*60*60, $workhours);
    }
}
