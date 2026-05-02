<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\BankStatementEntriesController Test Case
 */
class BankStatementEntriesControllerTest extends TestCase
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
        'plugin.Expenses.Accounts',
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

        $this->get('/expenses/bank-statement-entries/index');
        $this->assertResponseOk();
    }

    /**
     * Test index method filtered by bank statement.
     *
     * @return void
     */
    public function testIndexFilteredByStatement(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/bank-statement-entries/index/' . BANK_STATEMENT_1);
        $this->assertResponseOk();
    }

    /**
     * Test add (GET) renders form.
     *
     * @return void
     */
    public function testAddGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/bank-statement-entries/edit?statement_id=' . BANK_STATEMENT_1);
        $this->assertResponseOk();
    }

    /**
     * Test add (POST) creates a new entry.
     *
     * @return void
     */
    public function testAddPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'statement_id' => BANK_STATEMENT_1,
            'no' => 'MANUAL001',
            'client' => 'Test Client',
            'descript' => 'Manual entry',
            'debit' => '50.00',
            'credit' => '0.00',
            'iban' => 'SI56000000000000000',
            'ref' => 'RF001',
            'dat_issue' => '2026-03-13',
        ];

        $this->post('/expenses/bank-statement-entries/edit', $data);
        $this->assertRedirectContains('/expenses/bank-statements/view/' . BANK_STATEMENT_1);

        /** @var \Expenses\Model\Table\BankStatementEntriesTable $Entries */
        $Entries = TableRegistry::getTableLocator()->get('Expenses.BankStatementEntries');
        $this->assertTrue($Entries->exists(['descript' => 'Manual entry']));
    }

    /**
     * Test edit (GET) renders form.
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/bank-statement-entries/edit/' . BANK_STATEMENT_ENTRY_1);
        $this->assertResponseOk();
    }

    /**
     * Test edit (POST) updates an existing entry.
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'statement_id' => BANK_STATEMENT_1,
            'no' => '281884527',
            'client' => 'Updated Client',
            'descript' => 'Updated description',
            'debit' => '106.43',
            'credit' => '0.00',
            'iban' => 'SI56011008881000030',
            'ref' => 'SI1955736645-40002',
            'dat_issue' => '2026-03-13',
        ];

        $this->post('/expenses/bank-statement-entries/edit/' . BANK_STATEMENT_ENTRY_1, $data);
        $this->assertRedirectContains('/expenses/bank-statements/view/' . BANK_STATEMENT_1);

        /** @var \Expenses\Model\Table\BankStatementEntriesTable $Entries */
        $Entries = TableRegistry::getTableLocator()->get('Expenses.BankStatementEntries');
        $entry = $Entries->get(BANK_STATEMENT_ENTRY_1);
        $this->assertSame('Updated Client', $entry->client);
        $this->assertSame('Updated description', $entry->descript);
    }

    /**
     * Test edit returns JSON on AJAX request.
     *
     * @return void
     */
    public function testEditPostAjax(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);

        $data = [
            'statement_id' => BANK_STATEMENT_1,
            'no' => '281884527',
            'client' => 'Ajax Client',
            'descript' => 'Ajax update',
            'debit' => '106.43',
            'credit' => '0.00',
            'iban' => 'SI56011008881000030',
            'ref' => 'SI1955736645-40002',
            'dat_issue' => '2026-03-13',
        ];

        $this->post('/expenses/bank-statement-entries/edit/' . BANK_STATEMENT_ENTRY_1, $data);
        $this->assertResponseOk();

        $body = (string)$this->_response->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['success']);
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

        $this->post('/expenses/bank-statement-entries/delete/' . BANK_STATEMENT_ENTRY_1);
        $this->assertRedirectContains('/expenses/bank-statements/view/' . BANK_STATEMENT_1);

        /** @var \Expenses\Model\Table\BankStatementEntriesTable $Entries */
        $Entries = TableRegistry::getTableLocator()->get('Expenses.BankStatementEntries');
        $this->assertFalse($Entries->exists(['id' => BANK_STATEMENT_ENTRY_1]));
    }
}
