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

    /**
     * Loggs in specified user
     *
     * @param string $userId User id
     * @return void
     */
    private function login($userId)
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Checks if password matches users stored pass
     *
     * @param string $userId User id
     * @param string $password Password to match
     * @return bool
     */
    private function checkPassword($userId, $password)
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);

        return (new DefaultPasswordHasher())->check($password, $user->passwd);
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

        $this->get('/users/change-password/invalidkey');
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
        $this->assertTrue($this->checkPassword(USER_ADMIN, 'newpass'));
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
        $this->get('/users/logion-as');
        $this->assertRedirect();
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\UsersController::edit()
     */
    public function testEdit(): void
    {
        $this->get('/users/edit');
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        $this->get('/users/edit/nonexistant');
        $this->assertResponseError();

        $this->get('/users/edit/' . USER_ADMIN);
        $this->assertResponseOk();

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $data = [
            'id' => '048acacf-d87c-4088-a3a7-4bab30f6a040',
            'company_id' => COMPANY_FIRST,
            'name' => 'Edited Name',
            'username' => 'admin',
            'email' => 'admin@arhim.si',
            'privileges' => 2,
            'active' => 1,
        ];
        $this->post('/users/edit/' . USER_ADMIN, $data);
        $this->assertRedirect('/users/view/' . USER_ADMIN);
        $this->assertTrue($this->checkPassword(USER_ADMIN, 'pass'));

        $data = [
            'id' => '048acacf-d87c-4088-a3a7-4bab30f6a040',
            'company_id' => COMPANY_FIRST,
            'name' => 'Edited Name',
            'username' => 'admin',
            'passwd' => 'newpass',
            'repeat_passwd' => 'newpass',
            'email' => 'admin@arhim.si',
            'privileges' => 2,
            'active' => 1,
        ];
        $this->post('/users/edit/' . USER_ADMIN, $data);
        $this->assertRedirect('/users/view/' . USER_ADMIN);
        $this->assertTrue($this->checkPassword(USER_ADMIN, 'newpass'));
    }

    /**
     * Test properties method
     *
     * @return void
     * @uses \App\Controller\UsersController::properties()
     */
    public function testProperties(): void
    {
        $this->get('/users/properties');
        $this->assertRedirect();

        $this->login(USER_COMMON);

        $this->get('/users/properties');
        $this->assertResponseOk();

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $data = [
            'id' => USER_COMMON,
            'name' => 'Edited Name',
            'email' => 'user@arhim.si',
        ];
        $this->post('/users/properties', $data);
        $this->assertRedirect('/users/view/' . USER_COMMON);

        /** Change password but wrong old one */
        $data = [
            'id' => USER_COMMON,
            'name' => 'Edited Name',
            'email' => 'user@arhim.si',
            'old_passwd' => 'wrong',
            'passwd' => 'newpass',
            'repeat_passwd' => 'newpass',
        ];
        $this->post('/users/properties', $data);
        $this->assertNoRedirect();
        $this->assertFlashElement('flash/error');
        $this->assertTrue($this->checkPassword(USER_COMMON, 'password'));

        /** Change password successfully */
        $data = [
            'id' => USER_COMMON,
            'name' => 'Edited Name',
            'email' => 'user@arhim.si',
            'old_passwd' => 'password',
            'passwd' => 'newpass',
            'repeat_passwd' => 'newpass',
        ];
        $this->post('/users/properties', $data);

        $this->assertRedirect('/users/view/' . USER_COMMON);
        $this->assertFlashElement('flash/success');
        $this->assertTrue($this->checkPassword(USER_COMMON, 'newpass'));
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\UsersController::delete()
     */
    public function testDelete(): void
    {
        $this->get('/users/delete');
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        $this->get('/users/delete/nonexistant');
        $this->assertResponseError();

        $this->get('/users/delete/' . USER_COMMON);
        $this->assertRedirect('/users');
    }

    /**
     * Test avatar method
     *
     * @return void
     * @uses \App\Controller\UsersController::avatar()
     */
    public function testAvatar(): void
    {
        $this->get('/users/avatar');
        $this->assertResponseOk();

        $this->login(USER_ADMIN);

        $this->get('/users/avatar');
        $this->assertResponseOk();

        $this->get('/users/avatar/' . USER_COMMON);
        $this->assertResponseOk();
        $this->assertContentType('image/png');
    }
}
