<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\ExpensesController Test Case
 */
class ExpensesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.Users',
        'plugin.Expenses.Expenses',
        'plugin.Expenses.PaymentsExpenses',
        'plugin.Expenses.Payments',
        'plugin.Expenses.PaymentsAccounts',
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

        $this->get('/expenses/expenses/index');
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

        $this->get('/expenses/expenses/view/c60fe467-cd81-4a2c-b25d-4c3e0fe8c63e');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'owner_id' => COMPANY_FIRST,
            'model' => null,
            'foreign_id' => null,
            'project_id' => null,
            'dat_happened' => '2015-08-16',
            'month' => '2015-08',
            'title' => 'New Expense',
            'net_total' => 20,
            'total' => 20.44,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/expenses/edit', $data);
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAddWithPayment()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'owner_id' => COMPANY_FIRST,
            'model' => null,
            'foreign_id' => null,
            'project_id' => null,
            'dat_happened' => '2015-08-16',
            'month' => '2015-08',
            'title' => 'New Expense',
            'net_total' => 20,
            'total' => 20.44,
            'auto_payment' => 'c7f20dee-74f1-40e5-a129-d46d6fb43153',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/expenses/edit', $data);
        $this->assertResponseOk();

        $expense = TableRegistry::getTableLocator()->get('Expenses.Expenses')
            ->find()
            ->select()
            ->contain(['Payments'])
            ->where(['title' => 'New Expense'])
            ->firstOrFail();

        $this->assertNotEmpty($expense->payments);
        $this->assertEquals(20.44, $expense->payments[0]->amount);
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

        $this->get('/expenses/expenses/edit/c60fe467-cd81-4a2c-b25d-4c3e0fe8c63e');
        $this->assertResponseOk();

        $this->get('/expenses/expenses/edit');
        $this->assertResponseOk();

        $data = [
            'id' => 'c60fe467-cd81-4a2c-b25d-4c3e0fe8c63e',
            'owner_id' => COMPANY_FIRST,
            'model' => null,
            'foreign_id' => null,
            'project_id' => null,
            'dat_happened' => '2015-08-16',
            'month' => '2015-08',
            'title' => 'Edited title',
            'net_total' => 10,
            'total' => 10.22,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/expenses/edit/c60fe467-cd81-4a2c-b25d-4c3e0fe8c63e', $data);
        $this->assertResponseOk();
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

        $this->get('/expenses/expenses/delete/c60fe467-cd81-4a2c-b25d-4c3e0fe8c63e');
        $this->assertRedirect(['action' => 'index']);

        $this->disableErrorHandlerMiddleware();
        $this->expectException(RecordNotFoundException::class);
        TableRegistry::getTableLocator()->get('Expenses.Expenses')->get('c60fe467-cd81-4a2c-b25d-4c3e0fe8c63e');
    }
}
