<?php
declare(strict_types=1);

namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TravelOrdersMileagesFixture
 */
class TravelOrdersMileagesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => 'b1c23456-7890-4bcd-8f12-345678901234',
            'travel_order_id' => 'a1b23456-7890-4bcd-8f12-345678901234',
            'start_time' => '2015-02-10 06:00:00',
            'end_time' => '2015-02-10 10:00:00',
            'road_description' => 'Ljubljana - Zagreb',
            'distance_km' => 140.0,
            'price_per_km' => 0.21,
            'total' => 29.40,
            'created' => '2015-02-10 07:00:00',
            'modified' => '2015-02-10 07:00:00',
        ],
    ];
}
