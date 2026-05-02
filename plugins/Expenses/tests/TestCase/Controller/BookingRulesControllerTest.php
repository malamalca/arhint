<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\BookingRulesController Test Case
 */
class BookingRulesControllerTest extends TestCase
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
     * Test index method returns OK.
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-rules/index');
        $this->assertResponseOk();
    }

    /**
     * Test view method returns OK.
     *
     * @return void
     */
    public function testView(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-rules/view/' . BOOKING_RULE_1);
        $this->assertResponseOk();
    }

    /**
     * Test view with unknown id returns 404.
     *
     * @return void
     */
    public function testViewNotFound(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-rules/view/00000099-9999-9999-9999-999999999999');
        $this->assertResponseCode(404);
    }

    /**
     * Test add (GET) renders form.
     *
     * @return void
     */
    public function testAddGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-rules/edit');
        $this->assertResponseOk();
    }

    /**
     * Test add (POST) creates a new booking rule and redirects to view.
     *
     * @return void
     */
    public function testAddPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'model' => 'TravelOrders',
            'title' => 'New travel order rule',
        ];

        $this->post('/expenses/booking-rules/edit', $data);
        $this->assertRedirectContains('/expenses/booking-rules/view/');

        /** @var \Expenses\Model\Table\BookingRulesTable $rules */
        $rules = TableRegistry::getTableLocator()->get('Expenses.BookingRules');
        $this->assertSame(3, $rules->find()->where(['owner_id' => COMPANY_FIRST])->count());
    }

    /**
     * Test edit (GET) renders form with existing record.
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-rules/edit/' . BOOKING_RULE_1);
        $this->assertResponseOk();
    }

    /**
     * Test edit (POST) updates an existing booking rule.
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'model' => 'Invoices',
            'title' => 'Updated invoice rule',
        ];

        $this->post('/expenses/booking-rules/edit/' . BOOKING_RULE_1, $data);
        $this->assertRedirectContains('/expenses/booking-rules/view/' . BOOKING_RULE_1);

        /** @var \Expenses\Model\Table\BookingRulesTable $rules */
        $rules = TableRegistry::getTableLocator()->get('Expenses.BookingRules');
        $rule = $rules->get(BOOKING_RULE_1);
        $this->assertSame('Updated invoice rule', $rule->title);
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

        $this->post('/expenses/booking-rules/edit', ['model' => '', 'title' => '']);
        $this->assertResponseOk();
    }

    /**
     * Test delete removes a booking rule and redirects to index.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->delete('/expenses/booking-rules/delete/' . BOOKING_RULE_1);
        $this->assertRedirectContains('/expenses/booking-rules');

        /** @var \Expenses\Model\Table\BookingRulesTable $rules */
        $rules = TableRegistry::getTableLocator()->get('Expenses.BookingRules');
        $this->assertSame(1, $rules->find()->where(['owner_id' => COMPANY_FIRST])->count());
    }

    /**
     * Test delete via POST also works.
     *
     * @return void
     */
    public function testDeleteViaPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/booking-rules/delete/' . BOOKING_RULE_2);
        $this->assertRedirectContains('/expenses/booking-rules');
    }
}
