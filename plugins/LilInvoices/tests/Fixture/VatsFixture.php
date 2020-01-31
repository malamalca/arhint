<?php
namespace LilInvoices\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * VatsFixture
 *
 */
class VatsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'owner_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'descript' => ['type' => 'string', 'length' => 200, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'percent' => ['type' => 'decimal', 'length' => 8, 'precision' => 1, 'unsigned' => false, 'null' => false, 'default' => '0.0', 'comment' => ''],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB', 'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => '512e8f57-ba55-4162-ae1c-c011a8847351',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'descript' => '0 %',
            'percent' => 0,
            'created' => '2015-02-08 06:34:35',
            'modified' => '2015-02-08 06:34:35',
        ],
        [
            'id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'descript' => '22 %',
            'percent' => 22,
            'created' => '2015-02-08 06:34:36',
            'modified' => '2015-02-08 06:34:36',
        ],
        [
            'id' => 'e0ef11f0-0d75-4731-a147-9efaf4462e93',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'descript' => '9.5 %',
            'percent' => 9.5,
            'created' => '2015-02-08 06:34:37',
            'modified' => '2015-02-08 06:34:37',
        ],
    ];
}
