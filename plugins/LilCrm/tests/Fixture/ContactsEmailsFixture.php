<?php
namespace LilCrm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsEmailsFixture
 *
 */
class ContactsEmailsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => 36, 'null' => false, 'default' => null, 'comment' => '', 'fixed' => null],
        'contact_id' => ['type' => 'uuid', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'primary' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
        'email' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'kind' => ['type' => 'string', 'length' => 1, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
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
            'id' => '1',
            'contact_id' => COMPANY_FIRST,
            'primary' => 1,
            'email' => 'info@arhim.si',
            'kind' => 'W',
            'created' => '2015-01-31 07:29:20',
            'modified' => '2015-01-31 07:29:20',
        ],
    ];
}
