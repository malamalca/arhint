<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Controller;

use App\AppPluginsEnum;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Projects\Controller\ProjectsController Test Case
 */
class ProjectsControllerTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.Users',
        'Projects' => 'plugin.Projects.Projects',
        'ProjectsUsers' => 'plugin.Projects.ProjectsUsers',
        'plugin.Documents.DocumentsCounters',
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
     * Test creating a new user from the project user form
     *
     * @return void
     */
    public function testUserCreateNew()
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $projectId = '4dd53305-9715-4be4-b169-20defe113d2a';
        $this->post('/projects/projects/user/' . $projectId, [
            'project_id' => $projectId,
            'new_user' => '1',
            'new_name' => 'New Person',
            'new_email' => 'new.person@example.com',
            'new_privileges' => '10',
        ]);
        $this->assertRedirect(['action' => 'view', $projectId, '?' => ['tab' => 'users']]);

        $user = TableRegistry::getTableLocator()->get('Users')->find()
            ->where(['email' => 'new.person@example.com'])
            ->firstOrFail();
        $this->assertTrue($user->hasAccess(AppPluginsEnum::Projects));
        $this->assertNotEmpty($user->reset_key);
        $this->assertTrue(TableRegistry::getTableLocator()->get('Projects.ProjectsUsers')
            ->hasAccess($projectId, $user->id));

        $this->assertMailSentTo('new.person@example.com');
        $this->assertMailContains($user->reset_key);
    }

    /**
     * Test that a new user is linked only to the chosen counters of the company
     *
     * @return void
     */
    public function testUserCreateNewWithCounters()
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $projectId = '4dd53305-9715-4be4-b169-20defe113d2a';
        $this->post('/projects/projects/user/' . $projectId, [
            'project_id' => $projectId,
            'new_user' => '1',
            'new_name' => 'Counter Person',
            'new_email' => 'counter.person@example.com',
            'new_privileges' => '15',
            'new_counters' => [
                '1d53bc5b-de2d-4e85-b13b-81b39a97fc88', // own company
                '1d53bc5b-de2d-4e85-b13b-81b39a97fc89', // another company
            ],
        ]);
        $this->assertRedirect(['action' => 'view', $projectId, '?' => ['tab' => 'users']]);

        $user = TableRegistry::getTableLocator()->get('Users')->find()
            ->where(['email' => 'counter.person@example.com'])
            ->firstOrFail();
        $this->assertTrue($user->hasAccess(AppPluginsEnum::Documents));

        $links = TableRegistry::getTableLocator()
            ->get('CountersUsersCheck', ['table' => 'documents_counters_users'])
            ->find()
            ->where(['user_id' => $user->id])
            ->all();
        $this->assertCount(1, $links);
        $this->assertSame('1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $links->first()->counter_id);
    }

    /**
     * Test creating a new user with an already used email
     *
     * @return void
     */
    public function testUserCreateNewDuplicateEmail()
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $existing = TableRegistry::getTableLocator()->get('Users')->get(USER_COMMON);
        $this->post('/projects/projects/user/4dd53305-9715-4be4-b169-20defe113d2a', [
            'project_id' => '4dd53305-9715-4be4-b169-20defe113d2a',
            'new_user' => '1',
            'new_name' => 'Duplicate',
            'new_email' => $existing->email,
        ]);
        $this->assertResponseOk();
        $this->assertNoMailSent();
    }

    /**
     * Test that assigning an existing user adds the missing plugin access bits and keeps the others
     *
     * @return void
     */
    public function testUserExistingGetsPluginAccess()
    {
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get(USER_COMMON);
        $calendarBit = 1 << AppPluginsEnum::Calendar->getOrdinal();
        $user->access = $calendarBit;
        $usersTable->saveOrFail($user);

        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $projectId = '4dd53305-9715-4be4-b169-20defe113d2a';
        $this->post('/projects/projects/user/' . $projectId, ['project_id' => $projectId, 'user_id' => USER_COMMON]);
        $this->assertRedirect(['action' => 'view', $projectId, '?' => ['tab' => 'users']]);

        $user = $usersTable->get(USER_COMMON);
        $this->assertTrue($user->hasAccess(AppPluginsEnum::Projects));
        $this->assertTrue($user->hasAccess(AppPluginsEnum::Documents));
        $this->assertTrue($user->hasAccess(AppPluginsEnum::Calendar));
        $this->assertFalse($user->hasAccess(AppPluginsEnum::Crm));
        $this->assertNoMailSent();
    }

    /**
     * Test that a new user can be created as group admin
     *
     * @return void
     */
    public function testUserCreateNewGroupAdmin()
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $projectId = '4dd53305-9715-4be4-b169-20defe113d2a';
        $this->post('/projects/projects/user/' . $projectId, [
            'project_id' => $projectId,
            'new_user' => '1',
            'new_name' => 'Group Admin',
            'new_email' => 'group.admin@example.com',
            'new_privileges' => '7',
        ]);
        $this->assertRedirect(['action' => 'view', $projectId, '?' => ['tab' => 'users']]);

        $user = TableRegistry::getTableLocator()->get('Users')->find()
            ->where(['email' => 'group.admin@example.com'])
            ->firstOrFail();
        $this->assertSame(7, $user->privileges);
    }

    /**
     * Test resending login instructions
     *
     * @return void
     */
    public function testResendInstructions()
    {
        $projectId = '4dd53305-9715-4be4-b169-20defe113d2a';
        $projectsUsersTable = TableRegistry::getTableLocator()->get('ProjectsUsers');
        $projectsUsersTable->saveOrFail($projectsUsersTable->newEntity(['project_id' => $projectId, 'user_id' => USER_COMMON]));

        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $url = '/projects/projects/resendInstructions/' . $projectId . '/' . USER_COMMON;

        $this->get($url);
        $this->assertResponseError();

        $this->post($url);
        $this->assertRedirect(['action' => 'view', $projectId, '?' => ['tab' => 'users']]);
        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_COMMON);
        $this->assertNotEmpty($user->reset_key);
        $this->assertMailSentTo($user->email);
        $this->assertMailContains($user->reset_key);
    }

    /**
     * Test resending instructions to a user that is not on the project
     *
     * @return void
     */
    public function testResendInstructionsNotLinked()
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/projects/projects/resendInstructions/4dd53305-9715-4be4-b169-20defe113d2a/' . USER_COMMON);
        $this->assertNoMailSent();
    }

    /**
     * Test resending instructions by a common user
     *
     * @return void
     */
    public function testResendInstructionsCommonUser()
    {
        $this->login(USER_COMMON);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/projects/projects/resendInstructions/4dd53305-9715-4be4-b169-20defe113d2a/' . USER_COMMON);
        $this->assertResponseError();
        $this->assertNoMailSent();
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
