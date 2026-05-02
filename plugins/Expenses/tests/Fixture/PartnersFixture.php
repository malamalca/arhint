<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PartnersFixture
 */
class PartnersFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => PARTNER_1,
            'contact_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'role' => 'buyer',
            'date_start' => '2020-01-01',
            'date_end' => null,
            'created' => '2020-01-01 00:00:00',
            'modified' => '2020-01-01 00:00:00',
        ],
        [
            'id' => PARTNER_2,
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a42',
            'role' => 'seller',
            'date_start' => '2020-01-01',
            'date_end' => null,
            'created' => '2020-01-01 00:00:00',
            'modified' => '2020-01-01 00:00:00',
        ],
    ];
}
