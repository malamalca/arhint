<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\BookingRuleFiltersController Test Case
 */
class BookingRuleFiltersControllerTest extends TestCase
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

        $this->get('/expenses/booking-rule-filters/edit?rule_id=' . BOOKING_RULE_1);
        $this->assertResponseOk();
    }

    /**
     * Test add (POST) creates a new filter and redirects to parent rule view.
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
            'left_bracket_count' => 0,
            'field' => 'no',
            'operator' => 'startsWith',
            'value' => 'INV',
            'right_bracket_count' => 0,
            'end_operator' => '',
            'sort' => 30,
        ];

        $this->post('/expenses/booking-rule-filters/edit', $data);
        $this->assertRedirectContains('/expenses/booking-rules/view/' . BOOKING_RULE_1);

        /** @var \Expenses\Model\Table\BookingRuleFiltersTable $filters */
        $filters = TableRegistry::getTableLocator()->get('Expenses.BookingRuleFilters');
        $this->assertSame(4, $filters->find()->count());
    }

    /**
     * Test edit (GET) renders form for an existing filter.
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-rule-filters/edit/' . BOOKING_RULE_FILTER_1);
        $this->assertResponseOk();
    }

    /**
     * Test edit (POST) updates an existing filter.
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
            'left_bracket_count' => 0,
            'field' => 'descript',
            'operator' => 'contains',
            'value' => 'salary',
            'right_bracket_count' => 0,
            'end_operator' => '',
            'sort' => 10,
        ];

        $this->post('/expenses/booking-rule-filters/edit/' . BOOKING_RULE_FILTER_1, $data);
        $this->assertRedirectContains('/expenses/booking-rules/view/' . BOOKING_RULE_1);

        /** @var \Expenses\Model\Table\BookingRuleFiltersTable $filters */
        $filters = TableRegistry::getTableLocator()->get('Expenses.BookingRuleFilters');
        $filter = $filters->get(BOOKING_RULE_FILTER_1);
        $this->assertSame('contains', $filter->operator);
        $this->assertSame('salary', $filter->value);
    }

    /**
     * Test add (POST) with invalid data stays on form.
     *
     * @return void
     */
    public function testAddPostInvalid(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/booking-rule-filters/edit', [
            'rule_id' => BOOKING_RULE_1,
            'field' => '',
            'operator' => 'invalidOp',
            'value' => '',
        ]);
        $this->assertResponseOk();
    }

    /**
     * Test delete removes a filter and redirects to parent rule view.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->delete('/expenses/booking-rule-filters/delete/' . BOOKING_RULE_FILTER_1);
        $this->assertRedirectContains('/expenses/booking-rules/view/' . BOOKING_RULE_1);

        /** @var \Expenses\Model\Table\BookingRuleFiltersTable $filters */
        $filters = TableRegistry::getTableLocator()->get('Expenses.BookingRuleFilters');
        $this->assertSame(2, $filters->find()->count());
    }
}
