<?php
namespace LilCrm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsFixture
 *
 */
class ContactsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'owner_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'kind' => ['type' => 'string', 'fixed' => true, 'length' => 5, 'null' => false, 'default' => 'T', 'comment' => '', 'precision' => null],
        'name' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'surname' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'title' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'descript' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'mat_no' => ['type' => 'string', 'length' => 13, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'tax_no' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'tax_status' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'company_id' => ['type' => 'uuid', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'job' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        //'syncable' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => false, 'comment' => '', 'precision' => null],
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
            'id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'kind' => 'C',
            'name' => null,
            'surname' => null,
            'title' => 'Arhim d.o.o.',
            'descript' => 'We brought you to this app.',
            'mat_no' => null,
            'tax_no' => 'SI55736645',
            'tax_status' => 1,
            'company_id' => null,
            'job' => null,
            //'syncable' => false,
            'created' => '2015-01-31 07:28:53',
            'modified' => '2015-01-31 07:28:53',
        ],
        [
            'id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a42',
            'kind' => 'T',
            'name' => 'Miha',
            'surname' => 'Nahtigal',
            'title' => 'Nahtigal Miha',
            'descript' => 'That\'s me!',
            'mat_no' => null,
            'tax_no' => null,
            'tax_status' => 0,
            'company_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'job' => 'CEO',
            //'syncable' => true,
            'created' => '2015-01-31 07:28:54',
            'modified' => '2015-01-31 07:28:54',
        ],
        [
            'id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'kind' => 'C',
            'name' => null,
            'surname' => null,
            'title' => 'Client d.o.o.',
            'descript' => 'This is a first client',
            'mat_no' => null,
            'tax_no' => 'SI55736645',
            'tax_status' => 1,
            'company_id' => null,
            'job' => null,
            //'syncable' => false,
            'created' => '2015-01-31 07:28:53',
            'modified' => '2015-01-31 07:28:53',
        ],
    ];
}
