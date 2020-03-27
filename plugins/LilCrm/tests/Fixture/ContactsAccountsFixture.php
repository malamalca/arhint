<?php
namespace LilCrm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsAccountsFixture
 *
 */
class ContactsAccountsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => 36, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'contact_id' => ['type' => 'uuid', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'primary' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
        'kind' => ['type' => 'string', 'fixed' => true, 'length' => 1, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'bban' => ['type' => 'string', 'fixed' => true, 'length' => 19, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'bic' => ['type' => 'string', 'fixed' => true, 'length' => 8, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB', 'collation' => 'utf8_unicode_ci'
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
            'id' => 1,
            'contact_id' => COMPANY_FIRST,
            'primary' => 1,
            'kind' => 'W',
            'bban' => 'SI56 2420 3901 0691 883',
            'bic' => 'KREKSI22',
            'created' => '2015-01-31 07:29:08',
            'modified' => '2015-01-31 07:29:08',
        ],
    ];
}
