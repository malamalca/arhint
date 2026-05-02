<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BookingRuleAccountEntriesFixture
 *
 * Two account entries for BOOKING_RULE_1: debit from 'net_total', credit 0.
 */
class BookingRuleAccountEntriesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => BOOKING_RULE_ACCOUNT_ENTRY_1,
            'rule_id' => BOOKING_RULE_1,
            'account_id' => 1,
            'value' => 'net_total',
            'sort' => 10,
            'created' => '2026-01-10 08:00:00',
            'modified' => '2026-01-10 08:00:00',
        ],
        [
            'id' => BOOKING_RULE_ACCOUNT_ENTRY_2,
            'rule_id' => BOOKING_RULE_1,
            'account_id' => 2,
            'value' => '0',
            'sort' => 20,
            'created' => '2026-01-10 08:00:00',
            'modified' => '2026-01-10 08:00:00',
        ],
    ];
}
