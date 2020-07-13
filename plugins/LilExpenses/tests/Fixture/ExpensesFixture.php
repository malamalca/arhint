<?php
namespace LilExpenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ExpensesFixture
 */
class ExpensesFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'string', 'length' => 36, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'model' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'foreign_id' => ['type' => 'string', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'project_id' => ['type' => 'string', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'user_id' => ['type' => 'string', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'dat_happened' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'month' => ['type' => 'string', 'length' => 7, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'title' => ['type' => 'string', 'length' => 250, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'net_total' => ['type' => 'decimal', 'length' => 15, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'total' => ['type' => 'decimal', 'length' => 15, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => '0.00', 'comment' => ''],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'MyISAM',
            'collation' => 'utf8_unicode_ci'
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
            'id' => 'c60fe467-cd81-4a2c-b25d-4c3e0fe8c63e',
            'model' => 'Lorem ipsum dolor sit amet',
            'foreign_id' => 'Lorem ipsum dolor sit amet',
            'project_id' => 'Lorem ipsum dolor sit amet',
            'user_id' => 'Lorem ipsum dolor sit amet',
            'dat_happened' => '2015-08-16',
            'month' => '2015-08',
            'title' => 'Lorem ipsum dolor sit amet',
            'net_total' => '',
            'total' => '',
            'created' => '2015-08-16 17:36:42',
            'modified' => '2015-08-16 17:36:42',
        ],
    ];
}
