<?php
declare(strict_types=1);

namespace Calendar\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Calendar\Controller\EventsController Test Case
 *
 * @uses \Calendar\Controller\EventsController
 */
class EventsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Users',
        'plugin.Calendar.Events',
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
        $usersTable = TableRegistry::getTableLocator()->get('Users', ['className' => 'App\Model\Table\UsersTable']);
        $user = $usersTable->get($userId);

        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \Calendar\Controller\EventsController::index()
     */
    public function testIndex(): void
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/calendar/events/index');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \Calendar\Controller\EventsController::view()
     */
    public function testView(): void
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/calendar/events/view/185383a4-38c8-4194-9516-52c9069bc3bf');
        $this->assertResponseOk();
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \Calendar\Controller\EventsController::edit()
     */
    public function testEdit(): void
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/calendar/events/edit/185383a4-38c8-4194-9516-52c9069bc3bf');
        $this->assertResponseOk();

        $this->get('/calendar/events/edit');
        $this->assertResponseOk();

        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => '185383a4-38c8-4194-9516-52c9069bc3bf',
            'owner_id' => COMPANY_FIRST,
            'calendar_id' => USER_ADMIN,
            'title' => 'Edited Title',
            'location' => 'Ljubljana',
            'body' => 'This is an event description.',
            'all_day' => 0,
            'dat_start' => '2022-01-27 12:49:26',
            'dat_end' => '2022-01-27 13:49:26',
            'reminder' => 0,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/calendar/events/edit/185383a4-38c8-4194-9516-52c9069bc3bf', $data);
        $this->assertRedirect(['action' => 'index']);
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \Calendar\Controller\EventsController::delete()
     */
    public function testDelete(): void
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/calendar/events/delete/185383a4-38c8-4194-9516-52c9069bc3bf');
        $this->assertRedirect();
    }
}
