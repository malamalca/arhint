<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\BookingOrdersController Test Case
 */
class BookingOrdersControllerTest extends TestCase
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
        'plugin.Expenses.BankStatements',
        'plugin.Expenses.BankStatementEntries',
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

        $this->get('/expenses/booking-orders/index');
        $this->assertResponseOk();
    }

    /**
     * Test index method with search filter.
     *
     * @return void
     */
    public function testIndexWithSearch(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-orders/index?search=First');
        $this->assertResponseOk();
    }

    /**
     * Test index method with status filter.
     *
     * @return void
     */
    public function testIndexWithStatus(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-orders/index?status=draft');
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

        $this->get('/expenses/booking-orders/view/' . BOOKING_ORDER_1);
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

        $this->get('/expenses/booking-orders/view/999');
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

        $this->get('/expenses/booking-orders/edit');
        $this->assertResponseOk();
    }

    /**
     * Test add (POST) creates a new booking order.
     *
     * @return void
     */
    public function testAddPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'no' => 'BO-2026-TEST',
            'title' => 'Test order',
            'date_created' => '2026-03-01',
            'status' => 'draft',
        ];

        $this->post('/expenses/booking-orders/edit', $data);
        $this->assertRedirectContains('/expenses/booking-orders/view/');
    }

    /**
     * Test edit (GET) renders form with existing record.
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-orders/edit/' . BOOKING_ORDER_1);
        $this->assertResponseOk();
    }

    /**
     * Test edit (POST) updates an existing booking order.
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'no' => 'BO-2026-001-EDITED',
            'title' => 'First booking order (edited)',
            'date_created' => '2026-01-15',
            'status' => 'draft',
        ];

        $this->post('/expenses/booking-orders/edit/' . BOOKING_ORDER_1, $data);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);
    }

    /**
     * Test post action transitions draft → posted.
     *
     * @return void
     */
    public function testPostAction(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/expenses/booking-orders/post/' . BOOKING_ORDER_1);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);

        /** @var \Expenses\Model\Table\BookingOrdersTable $BookingOrders */
        $BookingOrders = TableRegistry::getTableLocator()->get('Expenses.BookingOrders');
        $order = $BookingOrders->get(BOOKING_ORDER_1);
        $this->assertSame('posted', $order->status);
    }

    /**
     * Test lock action transitions posted → locked.
     *
     * @return void
     */
    public function testLockAction(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        // Record 2 is already posted
        $this->post('/expenses/booking-orders/lock/' . BOOKING_ORDER_2);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_2);

        /** @var \Expenses\Model\Table\BookingOrdersTable $BookingOrders */
        $BookingOrders = TableRegistry::getTableLocator()->get('Expenses.BookingOrders');
        $order = $BookingOrders->get(BOOKING_ORDER_2);
        $this->assertSame('locked', $order->status);
    }

    /**
     * Test links GET – unknown model returns 400.
     *
     * @return void
     */
    public function testLinksGetBadModel(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-orders/links?model=Unknown&foreignid=' . BANK_STATEMENT_ENTRY_1);
        $this->assertResponseCode(400);
    }

    /**
     * Test links GET – missing foreignid returns 400.
     *
     * @return void
     */
    public function testLinksGetMissingForeignId(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-orders/links?model=BankStatementEntry');
        $this->assertResponseCode(400);
    }

    /**
     * Test links GET – renders page for a BankStatementEntry with no existing entries.
     *
     * @return void
     */
    public function testLinksGetNoExisting(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1);
        $this->assertResponseOk();
    }

    /**
     * Test links GET – shows existing entries when they are already linked.
     *
     * @return void
     */
    public function testLinksGetExisting(): void
    {
        $this->login(USER_ADMIN);

        // Pre-create a linked entry.
        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Boe */
        $Boe = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $Boe->save($Boe->newEntity([
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 1,
            'model' => 'BankStatementEntry',
            'foreign_id' => BANK_STATEMENT_ENTRY_1,
            'no' => 10,
            'descript' => 'Linked entry',
            'debit' => '50.00',
            'credit' => '0.00',
        ]));

        $this->get('/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1);
        $this->assertResponseOk();
        $this->assertResponseContains('value="50.00"');
    }

    /**
     * Test links POST – creates a new BookingOrder and saves entries.
     *
     * @return void
     */
    public function testLinksPostNewOrder(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->setUnlockedFields(['entries']);
        $this->enableCsrfToken();

        /** @var \Expenses\Model\Table\BookingOrdersTable $Orders */
        $Orders = TableRegistry::getTableLocator()->get('Expenses.BookingOrders');
        $newOrder = $Orders->newEntity([
            'owner_id' => COMPANY_FIRST,
            'opener_id' => USER_ADMIN,
            'no' => 'BSE-2026-001',
            'title' => 'Bank entry booking',
            'date_created' => '2026-03-13',
            'status' => 'draft',
        ]);
        $Orders->saveOrFail($newOrder);

        $data = [
            'bookingid' => $newOrder->id,
            'partner_id' => PARTNER_1,
            'entries' => [
                [
                    'id' => '',
                    'no' => '1',
                    'account_id' => '1',
                    'debit' => '106.43',
                    'credit' => '0.00',
                ],
            ],
        ];

        $url = '/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1;
        $this->post($url, $data);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . $newOrder->id);

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Boe */
        $Boe = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $this->assertTrue($Boe->exists([
            'model' => 'BankStatementEntry',
            'foreign_id' => BANK_STATEMENT_ENTRY_1,
            'descript' => 'Akontacija dohodnine za FEB 2026',
        ]));
    }

    /**
     * Test links POST – adds entries to an existing draft BookingOrder.
     *
     * @return void
     */
    public function testLinksPostExistingOrder(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->setUnlockedFields(['entries']);
        $this->enableCsrfToken();

        $data = [
            'bookingid' => BOOKING_ORDER_1,
            'partner_id' => PARTNER_1,
            'entries' => [
                [
                    'id' => '',
                    'no' => '3',
                    'account_id' => '1',
                    'debit' => '50.00',
                    'credit' => '0.00',
                ],
            ],
        ];

        $url = '/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1;
        $this->post($url, $data);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Boe */
        $Boe = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $this->assertTrue($Boe->exists([
            'booking_order_id' => BOOKING_ORDER_1,
            'model' => 'BankStatementEntry',
            'foreign_id' => BANK_STATEMENT_ENTRY_1,
            'descript' => 'Akontacija dohodnine za FEB 2026',
        ]));
    }

    /**
     * Test links POST – selecting a non-existent booking_order_id when using
     * submit_action=existing redirects back with an error.
     *
     * @return void
     */
    public function testLinksPostExistingOrderMissingId(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->setUnlockedFields(['entries']);
        $this->enableCsrfToken();

        $data = [
            'bookingid' => '',
            'entries' => [],
        ];

        $url = '/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1;
        $this->post($url, $data);
        $this->assertRedirectContains('booking-orders/links');
    }

    /**
     * Test links POST – rejects adding to a posted (non-draft) BookingOrder.
     *
     * @return void
     */
    public function testLinksPostExistingOrderPosted(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->setUnlockedFields(['entries']);
        $this->enableCsrfToken();

        // BOOKING_ORDER_2 has status=posted
        $data = [
            'bookingid' => BOOKING_ORDER_2,
            'entries' => [
                [
                    'account_id' => '1',
                    'partner_id' => '',
                    'debit' => '10.00',
                    'credit' => '0.00',
                ],
            ],
        ];

        $url = '/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1;
        $this->post($url, $data);
        $this->assertRedirectContains('booking-orders/links');

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Boe */
        $Boe = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $this->assertFalse($Boe->exists([
            'booking_order_id' => BOOKING_ORDER_2,
            'model' => 'BankStatementEntry',
            'foreign_id' => BANK_STATEMENT_ENTRY_1,
        ]));
    }

    /**
     * Test links GET – step 2 renders entry form when bookingid is provided.
     *
     * @return void
     */
    public function testLinksGetStep2(): void
    {
        $this->login(USER_ADMIN);

        $this->get(
            '/expenses/booking-orders/links?model=BankStatementEntry'
            . '&foreignid=' . BANK_STATEMENT_ENTRY_1
            . '&bookingid=' . BOOKING_ORDER_1,
        );
        $this->assertResponseOk();
    }

    /**
     * Test delete action removes a draft booking order.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->delete('/expenses/booking-orders/delete/' . BOOKING_ORDER_1);
        $this->assertRedirectContains('/expenses/booking-orders');

        /** @var \Expenses\Model\Table\BookingOrdersTable $BookingOrders */
        $BookingOrders = TableRegistry::getTableLocator()->get('Expenses.BookingOrders');
        $this->assertSame(1, $BookingOrders->find()->where(['owner_id' => COMPANY_FIRST])->count());
    }

    // ── links action – no / position tests ───────────────────────────────────

    /**
     * Helper: returns a sorted array of `no` values for all entries in a booking order.
     *
     * @param string $bookingOrderId
     * @return array<int>
     */
    private function nosForOrder(string $bookingOrderId): array
    {
        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Boe */
        $Boe = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $nos = $Boe->find()
            ->where(['booking_order_id' => $bookingOrderId])
            ->orderBy(['no' => 'ASC'])
            ->all()
            ->extract('no')
            ->map(fn($v) => (int)$v)
            ->toList();

        return $nos;
    }

    /**
     * Helper: returns the `no` for entries matching the given model/foreign_id,
     * sorted ascending.
     *
     * @param string $bookingOrderId
     * @param string $model
     * @param string $foreignId
     * @return array<int>
     */
    private function nosForEntity(string $bookingOrderId, string $model, string $foreignId): array
    {
        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Boe */
        $Boe = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $nos = $Boe->find()
            ->where([
                'booking_order_id' => $bookingOrderId,
                'model' => $model,
                'foreign_id' => $foreignId,
            ])
            ->orderBy(['no' => 'ASC'])
            ->all()
            ->extract('no')
            ->map(fn($v) => (int)$v)
            ->toList();

        return $nos;
    }

    /**
     * POST links for a new entity to an empty booking order:
     * entries should be numbered 1, 2, … with no gaps.
     *
     * @return void
     */
    public function testLinksPostNoNumberingNewEntityEmptyOrder(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->setUnlockedFields(['entries']);
        $this->enableCsrfToken();

        // BOOKING_ORDER_1 already has fixture entries (no=1, no=2) for no model/foreign_id.
        // Use a fresh order with no entries.
        /** @var \Expenses\Model\Table\BookingOrdersTable $Orders */
        $Orders = TableRegistry::getTableLocator()->get('Expenses.BookingOrders');
        $newOrder = $Orders->newEntity([
            'owner_id' => COMPANY_FIRST,
            'opener_id' => USER_ADMIN,
            'no' => 'BO-TEST-EMPTY',
            'title' => 'Empty order',
            'date_created' => '2026-03-24',
            'status' => 'draft',
        ]);
        $Orders->saveOrFail($newOrder);

        $url = '/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1;
        $this->post($url, [
            'bookingid' => $newOrder->id,
            'partner_id' => PARTNER_1,
            'entries' => [
                ['id' => '', 'no' => '1', 'account_id' => '1', 'debit' => '100.00', 'credit' => '0.00'],
                ['id' => '', 'no' => '2', 'account_id' => '1', 'debit' => '0.00', 'credit' => '100.00'],
            ],
        ]);
        $this->assertRedirectContains('/expenses/booking-orders/view/');

        $nos = $this->nosForOrder($newOrder->id);
        $this->assertSame([1, 2], $nos, 'Entries should be numbered 1, 2');
    }

    /**
     * POST links for a new entity to a booking order that already has entries
     * (from another entity): new entries should be appended after the existing max no,
     * and the overall sequence must be gap-free after save.
     *
     * @return void
     */
    public function testLinksPostNoNumberingNewEntityOrderWithExistingEntries(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->setUnlockedFields(['entries']);
        $this->enableCsrfToken();

        // BOOKING_ORDER_1 already has fixture entries at no=1 and no=2 (no link to any BSE).
        // Link BSE_1 to the same order; new entries should land at no=3 and no=4.
        $url = '/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1;
        $this->post($url, [
            'bookingid' => BOOKING_ORDER_1,
            'partner_id' => PARTNER_1,
            'entries' => [
                ['id' => '', 'no' => '3', 'account_id' => '1', 'debit' => '50.00', 'credit' => '0.00'],
                ['id' => '', 'no' => '4', 'account_id' => '1', 'debit' => '0.00', 'credit' => '50.00'],
            ],
        ]);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);

        $nos = $this->nosForOrder(BOOKING_ORDER_1);
        $this->assertSame([1, 2, 3, 4], $nos, 'All four entries should be numbered 1-4 with no gaps');

        $entityNos = $this->nosForEntity(BOOKING_ORDER_1, 'BankStatementEntry', BANK_STATEMENT_ENTRY_1);
        $this->assertSame([3, 4], $entityNos, 'New entries should be at positions 3 and 4');
    }

    /**
     * POST links inserts new entries in the middle of an existing sequence
     * (an entity already has entries 1,2 and another entity has entries 3,4):
     * after inserting at position 3 (for the first entity), the second entity's
     * entries must be shifted up and the final sequence must be gap-free.
     *
     * @return void
     */
    public function testLinksPostNoShiftsOtherEntityEntries(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->setUnlockedFields(['entries']);
        $this->enableCsrfToken();

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Boe */
        $Boe = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');

        // Fixture entries (no=1, no=2) have no model; link them to BSE_1 for this test.
        $Boe->updateAll(
            ['model' => 'BankStatementEntry', 'foreign_id' => BANK_STATEMENT_ENTRY_1],
            ['booking_order_id' => BOOKING_ORDER_1],
        );

        // Seed BSE_2 entries at no=3, no=4 in the same order.
        $Boe->saveOrFail($Boe->newEntity([
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 1,
            'partner_id' => PARTNER_1,
            'model' => 'BankStatementEntry',
            'foreign_id' => BANK_STATEMENT_ENTRY_2,
            'no' => 3,
            'descript' => 'BSE2 entry A',
            'debit' => '10.00',
            'credit' => '0.00',
        ]));
        $Boe->saveOrFail($Boe->newEntity([
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 1,
            'partner_id' => PARTNER_1,
            'model' => 'BankStatementEntry',
            'foreign_id' => BANK_STATEMENT_ENTRY_2,
            'no' => 4,
            'descript' => 'BSE2 entry B',
            'debit' => '0.00',
            'credit' => '10.00',
        ]));

        // Get the IDs of the existing BSE_1 entries so we can pass them back.
        $bse1Entries = $Boe->find()
            ->where([
                'booking_order_id' => BOOKING_ORDER_1,
                'foreign_id' => BANK_STATEMENT_ENTRY_1,
            ])
            ->orderBy(['no' => 'ASC'])
            ->all()
            ->toList();

        // Re-save BSE_1 entries with a new third entry inserted after them.
        $url = '/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1;
        $this->post($url, [
            'bookingid' => BOOKING_ORDER_1,
            'partner_id' => PARTNER_1,
            'entries' => [
                ['id' => $bse1Entries[0]->id, 'no' => '1', 'account_id' => '1', 'debit' => '100.00', 'credit' => '0.00'],
                ['id' => $bse1Entries[1]->id, 'no' => '2', 'account_id' => '1', 'debit' => '0.00', 'credit' => '100.00'],
                ['id' => '', 'no' => '3', 'account_id' => '1', 'debit' => '20.00', 'credit' => '0.00'],
            ],
        ]);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);

        // Overall sequence must be gap-free: 1,2,3,4,5
        $nos = $this->nosForOrder(BOOKING_ORDER_1);
        $this->assertSame([1, 2, 3, 4, 5], $nos, 'Gap-free sequence 1-5 expected after shift');

        // BSE_1 must occupy 1,2,3
        $bse1Nos = $this->nosForEntity(BOOKING_ORDER_1, 'BankStatementEntry', BANK_STATEMENT_ENTRY_1);
        $this->assertSame([1, 2, 3], $bse1Nos, 'BSE_1 entries should be at 1, 2, 3');

        // BSE_2 must have been pushed to 4, 5
        $bse2Nos = $this->nosForEntity(BOOKING_ORDER_1, 'BankStatementEntry', BANK_STATEMENT_ENTRY_2);
        $this->assertSame([4, 5], $bse2Nos, 'BSE_2 entries should have been shifted to 4, 5');
    }

    /**
     * POST links that removes an entry: remaining entries must be renumbered
     * contiguously with no gaps.
     *
     * @return void
     */
    public function testLinksPostDeleteEntryRenumbers(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->setUnlockedFields(['entries']);
        $this->enableCsrfToken();

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $Boe */
        $Boe = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');

        // Link fixture entry 1 (no=1) and entry 2 (no=2) to BSE_1.
        $Boe->updateAll(
            ['model' => 'BankStatementEntry', 'foreign_id' => BANK_STATEMENT_ENTRY_1],
            ['booking_order_id' => BOOKING_ORDER_1],
        );

        // Add a third entry at no=3 for BSE_2 so we can verify it also stays correct.
        $Boe->saveOrFail($Boe->newEntity([
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 1,
            'partner_id' => PARTNER_1,
            'model' => 'BankStatementEntry',
            'foreign_id' => BANK_STATEMENT_ENTRY_2,
            'no' => 3,
            'descript' => 'BSE2 entry',
            'debit' => '5.00',
            'credit' => '0.00',
        ]));

        // Submit only entry 1 (omitting entry 2 → deletion).
        $url = '/expenses/booking-orders/links?model=BankStatementEntry&foreignid=' . BANK_STATEMENT_ENTRY_1;
        $this->post($url, [
            'bookingid' => BOOKING_ORDER_1,
            'partner_id' => PARTNER_1,
            'entries' => [
                ['id' => BOOKING_ORDER_ENTRY_1, 'no' => '1', 'account_id' => '1', 'debit' => '100.00', 'credit' => '0.00'],
            ],
        ]);
        $this->assertRedirectContains('/expenses/booking-orders/view/' . BOOKING_ORDER_1);

        // After deleting no=2 and renumbering: should be 1, 2 (no=3 for BSE_2 moves to no=2).
        $nos = $this->nosForOrder(BOOKING_ORDER_1);
        $this->assertSame([1, 2], $nos, 'After deleting one entry the sequence should be 1, 2');
    }

    /**
     * nextPosition action returns max no + 1 for the given booking order.
     *
     * @return void
     */
    public function testNextPositionReturnsMaxPlusOne(): void
    {
        $this->login(USER_ADMIN);

        // BOOKING_ORDER_1 has fixture entries at no=1 and no=2.
        $this->get(
            '/expenses/booking-orders/next-position.json?bookingorderid=' . BOOKING_ORDER_1,
        );
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(3, $body['next_no'], 'next_no should be max(no)+1 = 3');
    }

    /**
     * nextPosition action returns 1 when the booking order has no entries.
     *
     * @return void
     */
    public function testNextPositionEmptyOrderReturnsOne(): void
    {
        $this->login(USER_ADMIN);

        /** @var \Expenses\Model\Table\BookingOrdersTable $Orders */
        $Orders = TableRegistry::getTableLocator()->get('Expenses.BookingOrders');
        $newOrder = $Orders->newEntity([
            'owner_id' => COMPANY_FIRST,
            'opener_id' => USER_ADMIN,
            'no' => 'BO-TEST-EMPTY2',
            'title' => 'Empty order 2',
            'date_created' => '2026-03-24',
            'status' => 'draft',
        ]);
        $Orders->saveOrFail($newOrder);

        $this->get('/expenses/booking-orders/next-position.json?bookingorderid=' . $newOrder->id);
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(1, $body['next_no'], 'next_no should be 1 for an order with no entries');
    }
}
