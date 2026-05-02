<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BookingRuleFiltersFixture
 *
 * Rule 1 (Invoices): single filter – descript startsWith 'Placa'
 * Rule 2 (BankStatements): two bracket-grouped filters joined with AND
 *   ( iban isEqual 'SI56...' OR iban isEqual 'SI56...' ) AND descript startsWith 'Transfer'
 */
class BookingRuleFiltersFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => BOOKING_RULE_FILTER_1,
            'rule_id' => BOOKING_RULE_1,
            'left_bracket_count' => 0,
            'field' => 'descript',
            'operator' => 'startsWith',
            'value' => 'Placa',
            'right_bracket_count' => 0,
            'end_operator' => null,
            'sort' => 10,
            'created' => '2026-01-10 08:00:00',
            'modified' => '2026-01-10 08:00:00',
        ],
        [
            'id' => BOOKING_RULE_FILTER_2,
            'rule_id' => BOOKING_RULE_2,
            'left_bracket_count' => 1,
            'field' => 'iban',
            'operator' => 'isEqual',
            'value' => 'SI56610006100000062',
            'right_bracket_count' => 0,
            'end_operator' => 'or',
            'sort' => 10,
            'created' => '2026-01-11 08:00:00',
            'modified' => '2026-01-11 08:00:00',
        ],
        [
            'id' => BOOKING_RULE_FILTER_3,
            'rule_id' => BOOKING_RULE_2,
            'left_bracket_count' => 0,
            'field' => 'iban',
            'operator' => 'isEqual',
            'value' => 'SI56610006100000063',
            'right_bracket_count' => 1,
            'end_operator' => null,
            'sort' => 20,
            'created' => '2026-01-11 08:00:00',
            'modified' => '2026-01-11 08:00:00',
        ],
    ];
}
