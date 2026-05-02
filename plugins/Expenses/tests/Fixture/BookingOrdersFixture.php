<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BookingOrdersFixture
 */
class BookingOrdersFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => BOOKING_ORDER_1,
            'owner_id' => COMPANY_FIRST,
            'opener_id' => USER_ADMIN,
            'no' => 'BO-2026-001',
            'model' => null,
            'foreign_id' => null,
            'title' => 'First booking order',
            'date_created' => '2026-01-15',
            'status' => 'draft',
            'created' => '2026-01-15 10:00:00',
            'modified' => '2026-01-15 10:00:00',
        ],
        [
            'id' => BOOKING_ORDER_2,
            'owner_id' => COMPANY_FIRST,
            'opener_id' => USER_ADMIN,
            'no' => 'BO-2026-002',
            'model' => null,
            'foreign_id' => null,
            'title' => 'Posted booking order',
            'date_created' => '2026-02-01',
            'status' => 'posted',
            'created' => '2026-02-01 09:00:00',
            'modified' => '2026-02-01 09:00:00',
        ],
    ];
}
