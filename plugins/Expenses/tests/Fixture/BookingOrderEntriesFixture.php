<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BookingOrderEntriesFixture
 */
class BookingOrderEntriesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => BOOKING_ORDER_ENTRY_1,
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 1,
            'partner_id' => PARTNER_1,
            'model' => null,
            'foreign_id' => null,
            'no' => 1,
            'descript' => 'First entry',
            'debit' => '100.00',
            'credit' => '0.00',
        ],
        [
            'id' => BOOKING_ORDER_ENTRY_2,
            'booking_order_id' => BOOKING_ORDER_1,
            'account_id' => 1,
            'partner_id' => null,
            'model' => null,
            'foreign_id' => null,
            'no' => 2,
            'descript' => 'Second entry',
            'debit' => '0.00',
            'credit' => '100.00',
        ],
    ];
}
