<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\UsersController Test Case
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Users',
    ];

    private function login($userId)
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test login method
     *
     * @return void
     * @uses \App\Controller\UsersController::login()
     */
    public function testLogin(): void
    {
        $this->get('/users/login');
        $this->assertResponseOk();

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/users/login', ['username' => 'admin', 'passwd' => 'pass']);
        $this->assertRedirect('/');
        $this->assertSessionHasKey('Auth');

        $this->login(USER_ADMIN);
        $this->get('/users/login');
        $this->assertRedirect();
    }

    /**
     * Test logout method
     *
     * @return void
     * @uses \App\Controller\UsersController::logout()
     */
    public function testLogout(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/users/logout');
        $this->assertRedirect(Router::url('/', true));
    }

    /**
     * Test reset method
     *
     * @return void
     * @uses \App\Controller\UsersController::reset()
     */
    public function testReset(): void
    {
        $this->get('/users/reset');
        $this->assertResponseOk();

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $model = $this->getMockForModel('Users', ['sendResetEmail']);
        $model->expects($this->once())
        ->method('sendResetEmail')
        ->will($this->returnValue(true));

        $this->post('/users/reset', ['email' => 'admin@arhim.si']);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/success');

        $this->post('/users/reset', ['email' => 'notexistant@arhim.si']);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');

        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $this->assertNotEmpty($user->reset_key);
    }

    /**
     * Test changePassword method
     *
     * @return void
     * @uses \App\Controller\UsersController::changePassword()
     */
    public function testChangePassword(): void
    {
        $this->get('/users/change-password');
        $this->assertResponseError();

        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $user->reset_key = uniqid();
        TableRegistry::getTableLocator()->get('Users')->save($user);

        $this->get('/users/change-password/' . $user->reset_key);
        $this->assertResponseOk();

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $this->post(
            '/users/change-password/' . $user->reset_key,
            ['passwd' => 'newpass', 'repeat_passwd' => 'newpass']
        );
        $this->assertRedirect();

        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $this->assertTrue((new DefaultPasswordHasher())->check('newpass', $user->passwd));
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\UsersController::index()
     */
    public function testIndex(): void
    {
        $this->get('/users/index');
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        $this->get('/users/index');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\UsersController::view()
     */
    public function testView(): void
    {
        $this->get('/users/view');
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        $this->get('/users/view/nonexistant');
        $this->assertResponseError();

        $this->get('/users/view/' . USER_ADMIN);
        $this->assertResponseOk();
    }

    /**
     * Test loginAs method
     *
     * @return void
     * @uses \App\Controller\UsersController::loginAs()
     */
    public function testLoginAs(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\UsersController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test properties method
     *
     * @return void
     * @uses \App\Controller\UsersController::properties()
     */
    public function testProperties(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\UsersController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test avatar method
     *
     * @return void
     * @uses \App\Controller\UsersController::avatar()
     */
    public function testAvatar(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
