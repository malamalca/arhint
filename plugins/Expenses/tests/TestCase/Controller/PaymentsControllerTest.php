<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\PaymentsController Test Case
 */
class PaymentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.Users',
        'plugin.Expenses.Payments',
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

        $this->get('/expenses/payments/index');
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

        $this->get('/expenses/payments/edit/c7f20dee-74f1-40e5-a3a9-d46d6fb43153');
        $this->assertResponseOk();

        $this->get('/expenses/payments/edit');
        $this->assertResponseOk();

        $data = [
            'id' => 'c7f20dee-74f1-40e5-a3a9-d46d6fb43153',
            'account_id' => 'c7f20dee-74f1-40e5-a129-d46d6fb43153',
            'dat_happened' => '2015-08-16',
            'descript' => 'New Title',
            'amount' => 1,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/payments/edit/c7f20dee-74f1-40e5-a3a9-d46d6fb43153', $data);
        $this->assertRedirect(['action' => 'index']);
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

        $this->get('/expenses/payments/delete/c7f20dee-74f1-40e5-a3a9-d46d6fb43153');
        $this->assertRedirect(['action' => 'index']);

        $this->disableErrorHandlerMiddleware();
        $this->expectException(RecordNotFoundException::class);
        TableRegistry::getTableLocator()->get('Expenses.Payments')->get('c7f20dee-74f1-40e5-a3a9-d46d6fb43153');
    }
}
