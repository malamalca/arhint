<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\BankStatementsController Test Case
 */
class BankStatementsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    public array $fixtures = [
        'app.Users',
        'plugin.Expenses.BankStatements',
        'plugin.Expenses.BankStatementEntries',
        'plugin.Expenses.BookingOrders',
        'plugin.Expenses.BookingOrderEntries',
    ];

    /**
     * Login as a given user.
     *
     * @param string $userId User id.
     * @return void
     */
    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method.
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/bank-statements/index');
        $this->assertResponseOk();
    }

    /**
     * Test index with IBAN filter.
     *
     * @return void
     */
    public function testIndexWithIbanFilter(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/bank-statements/index?q=iban:SI56610000005092459');
        $this->assertResponseOk();
    }

    /**
     * Test view method.
     *
     * @return void
     */
    public function testView(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/bank-statements/view/' . BANK_STATEMENT_1);
        $this->assertResponseOk();
    }

    /**
     * Test view method – unknown id returns 404.
     *
     * @return void
     */
    public function testViewNotFound(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/bank-statements/view/999');
        $this->assertResponseCode(404);
    }

    /**
     * Test import (GET) renders upload form.
     *
     * @return void
     */
    public function testImportGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/bank-statements/import');
        $this->assertResponseOk();
    }

    /**
     * Test import (POST) without a file shows the form again with an error.
     *
     * @return void
     */
    public function testImportPostNoFile(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/bank-statements/import', []);
        // No redirect – form should re-render with error message
        $this->assertResponseOk();
    }

    /**
     * Test delete method.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/bank-statements/delete/' . BANK_STATEMENT_1);
        $this->assertRedirectContains('/expenses/bank-statements');

        /** @var \Expenses\Model\Table\BankStatementsTable $BankStatements */
        $BankStatements = TableRegistry::getTableLocator()->get('Expenses.BankStatements');
        $this->assertFalse($BankStatements->exists(['id' => BANK_STATEMENT_1]));
    }
}
