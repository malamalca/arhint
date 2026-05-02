<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\BookingOrderEntriesController Test Case
 */
class BookingOrderEntriesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    public array $fixtures = [
        'app.Users',
        'plugin.Expenses.BookingOrders',
        'plugin.Expenses.BookingOrderEntries',
        'plugin.Expenses.Accounts',
        'plugin.Expenses.Partners',
        'plugin.Crm.Contacts',
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

        $this->get('/expenses/booking-order-entries/index');
        $this->assertResponseOk();
    }

    /**
     * Test index method filtered by booking order.
     *
     * @return void
     */
    public function testIndexFilteredByOrder(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-order-entries/index/' . BOOKING_ORDER_1);
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

        $this->get('/expenses/booking-order-entries/edit?booking_order_id=' . BOOKING_ORDER_1);
        $this->assertResponseOk();
    }

    /**
     * Test add (POST) creates a new entry without a partner; redirects to parent order view.
     *
     * @return void
     */
    public function testAddPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 1,
            'partner_id' => '',
            'descript' => 'New entry',
            'debit' => '50.00',
            'credit' => '0.00',
        ];

        $this->post('/expenses/booking-order-entries/edit', $data);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);
    }

    /**
     * Test add (POST) with autocomplete-selected account and partner (unlocked hidden fields).
     *
     * @return void
     */
    public function testAddPostWithAutocomplete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 2,
            'partner_id' => PARTNER_1,
            'descript' => 'Autocomplete entry',
            'debit' => '75.00',
            'credit' => '0.00',
        ];

        $this->post('/expenses/booking-order-entries/edit', $data);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Entries */
        $Entries = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $entry = $Entries->find()->where(['descript' => 'Autocomplete entry'])->firstOrFail();
        $this->assertSame(2, $entry->account_id);
        $this->assertSame(PARTNER_1, $entry->partner_id);
    }

    /**
     * Test edit (GET) renders form with existing record.
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-order-entries/edit/' . BOOKING_ORDER_ENTRY_1);
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
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 1,
            'partner_id' => PARTNER_1,
            'descript' => 'Updated entry',
            'debit' => '200.00',
            'credit' => '0.00',
        ];

        $this->post('/expenses/booking-order-entries/edit/' . BOOKING_ORDER_ENTRY_1, $data);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);
    }

    /**
     * Test edit (POST) with autocomplete-changed account and partner (unlocked hidden fields).
     *
     * Simulates JS filling unlocked hidden fields with values different from the
     * original entity values, which would trigger a FormProtectionException if the
     * fields were not properly unlocked.
     *
     * @return void
     */
    public function testEditPostWithAutocomplete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        // BOOKING_ORDER_ENTRY_2 has account_id=1 and partner_id=null;
        // simulate JS changing both to new values via autocomplete.
        $data = [
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 2,
            'partner_id' => PARTNER_2,
            'descript' => 'Autocomplete edit',
            'debit' => '0.00',
            'credit' => '50.00',
        ];

        $this->post('/expenses/booking-order-entries/edit/' . BOOKING_ORDER_ENTRY_2, $data);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Entries */
        $Entries = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $entry = $Entries->get(BOOKING_ORDER_ENTRY_2);
        $this->assertSame(2, $entry->account_id);
        $this->assertSame(PARTNER_2, $entry->partner_id);
    }

    /**
     * Test delete action removes an entry and redirects to parent order view.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->delete('/expenses/booking-order-entries/delete/' . BOOKING_ORDER_ENTRY_1);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Entries */
        $Entries = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $this->assertSame(1, $Entries->find()->where(['booking_order_id' => BOOKING_ORDER_1])->count());
    }
}
