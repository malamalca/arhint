<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BookingRulesFixture
 */
class BookingRulesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => BOOKING_RULE_1,
            'owner_id' => COMPANY_FIRST,
            'model' => 'Invoices',
            'title' => 'Standard invoice rule',
            'created' => '2026-01-10 08:00:00',
            'modified' => '2026-01-10 08:00:00',
        ],
        [
            'id' => BOOKING_RULE_2,
            'owner_id' => COMPANY_FIRST,
            'model' => 'BankStatements',
            'title' => 'Bank statement rule with brackets',
            'created' => '2026-01-11 08:00:00',
            'modified' => '2026-01-11 08:00:00',
        ],
    ];
}
