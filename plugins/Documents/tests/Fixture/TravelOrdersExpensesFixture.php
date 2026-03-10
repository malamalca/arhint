<?php
declare(strict_types=1);

namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TravelOrdersExpensesFixture
 */
class TravelOrdersExpensesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => 'c1d23456-7890-4bcd-8f12-345678901234',
            'travel_order_id' => 'a1b23456-7890-4bcd-8f12-345678901234',
            'start_time' => '2015-02-10 06:00:00',
            'end_time' => '2015-02-10 10:00:00',
            'type' => 'Toll',
            'description' => 'Highway toll Ljubljana-Zagreb',
            'quantity' => 1.0,
            'price' => 16.10,
            'currency' => 'EUR',
            'total' => 16.10,
            'approved_total' => 16.10,
            'created' => '2015-02-10 07:00:00',
            'modified' => '2015-02-10 07:00:00',
        ],
    ];
}
