<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\BookingRuleAccountEntriesController Test Case
 */
class BookingRuleAccountEntriesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    public array $fixtures = [
        'app.Users',
        'plugin.Expenses.BookingRules',
        'plugin.Expenses.BookingRuleFilters',
        'plugin.Expenses.BookingRuleAccountEntries',
        'plugin.Expenses.Accounts',
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
     * Test add (GET) renders form pre-filled with rule_id from query string.
     *
     * @return void
     */
    public function testAddGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-rule-account-entries/edit?rule_id=' . BOOKING_RULE_1);
        $this->assertResponseOk();
    }

    /**
     * Test add (POST) creates a new account entry and redirects to parent rule view.
     *
     * @return void
     */
    public function testAddPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'rule_id' => BOOKING_RULE_1,
            'account_id' => 2,
            'value' => 'total',
            'sort' => 30,
        ];

        $this->post('/expenses/booking-rule-account-entries/edit', $data);
        $this->assertRedirectContains('/expenses/booking-rules/view/' . BOOKING_RULE_1);

        /** @var \Expenses\Model\Table\BookingRuleAccountEntriesTable $entries */
        $entries = TableRegistry::getTableLocator()->get('Expenses.BookingRuleAccountEntries');
        $this->assertSame(3, $entries->find()->count());
    }

    /**
     * Test edit (GET) renders form for an existing account entry.
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-rule-account-entries/edit/' . BOOKING_RULE_ACCOUNT_ENTRY_1);
        $this->assertResponseOk();
    }

    /**
     * Test edit (POST) updates an existing account entry.
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'rule_id' => BOOKING_RULE_1,
            'account_id' => 1,
            'value' => 'total',
            'sort' => 10,
        ];

        $this->post('/expenses/booking-rule-account-entries/edit/' . BOOKING_RULE_ACCOUNT_ENTRY_1, $data);
        $this->assertRedirectContains('/expenses/booking-rules/view/' . BOOKING_RULE_1);

        /** @var \Expenses\Model\Table\BookingRuleAccountEntriesTable $entries */
        $entries = TableRegistry::getTableLocator()->get('Expenses.BookingRuleAccountEntries');
        $entry = $entries->get(BOOKING_RULE_ACCOUNT_ENTRY_1);
        $this->assertSame('total', $entry->value);
    }

    /**
     * Test add (POST) with missing account_id stays on form.
     *
     * @return void
     */
    public function testAddPostInvalid(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/booking-rule-account-entries/edit', [
            'rule_id' => BOOKING_RULE_1,
            'account_id' => '',
            'value' => 'net_total',
        ]);
        $this->assertResponseOk();
    }

    /**
     * Test delete removes an account entry and redirects to parent rule view.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->delete(
            '/expenses/booking-rule-account-entries/delete/' . BOOKING_RULE_ACCOUNT_ENTRY_1,
        );
        $this->assertRedirectContains('/expenses/booking-rules/view/' . BOOKING_RULE_1);

        /** @var \Expenses\Model\Table\BookingRuleAccountEntriesTable $entries */
        $entries = TableRegistry::getTableLocator()->get('Expenses.BookingRuleAccountEntries');
        $this->assertSame(1, $entries->find()->count());
    }
}
