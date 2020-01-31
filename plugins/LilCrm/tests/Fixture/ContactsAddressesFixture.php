<?php
namespace LilCrm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsAddressesFixture
 *
 */
class ContactsAddressesFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => 36, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'contact_id' => ['type' => 'uuid', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'primary' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
        'kind' => ['type' => 'string', 'length' => 1, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'street' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'town' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'zip' => ['type' => 'string', 'length' => 20, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'city' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'country' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
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
        /*[
            'id' => '49a90cfe-fda4-49ca-b7ed-ca50783b5a41',
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a41',
            'primary' => true,
            'kind' => 'W',
            'street' => 'Slakova ulica 36',
            'zip' => '8210',
            'city' => 'Trebnje',
            'country' => 'Slovenia',
            'created' => '2015-01-31 07:29:14',
            'modified' => '2015-01-31 07:29:14',
        ],
        [
            'id' => '49a90cfe-fda4-49ca-b7ed-ca50783b5a42',
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'primary' => true,
            'kind' => 'W',
            'street' => 'First Clients Rd 21',
            'zip' => '111',
            'city' => 'AcmeCity',
            'country' => 'AcMe',
            'created' => '2015-01-31 07:29:14',
            'modified' => '2015-01-31 07:29:14',
        ],*/
    ];
}
