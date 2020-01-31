<?php
namespace LilInvoices\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesClientsFixture
 *
 */
class InvoicesClientsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'invoice_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'contact_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'kind' => ['type' => 'string', 'fixed' => true, 'length' => 2, 'null' => true, 'default' => null, 'comment' => 'II - issuer, BY - buyer, IV - receiver', 'precision' => null],
        'title' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'street' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'city' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'zip' => ['type' => 'string', 'length' => 35, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'country' => ['type' => 'string', 'length' => 35, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'country_code' => ['type' => 'string', 'length' => 35, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'iban' => ['type' => 'string', 'length' => 35, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'bank' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'tax_no' => ['type' => 'string', 'length' => 35, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'mat_no' => ['type' => 'string', 'length' => 35, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'person' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'phone' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'fax' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'email' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
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
            'id' => 'c628ae1b-f846-4818-a043-fb945ebbfdb7',
            'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'contact_id' => null,
            'kind' => 'II',
            'title' => 'SomeCompany Is Issuer ltd',
            'created' => '2015-07-12 15:39:03',
            'modified' => '2015-07-12 15:39:03',
        ],
        [
            'id' => 'c628ae1b-f846-4818-a043-fb945ebbfdb8',
            'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'contact_id' => null,
            'kind' => 'IV',
            'title' => 'ARHIM Is Receiver ltd',
            'created' => '2015-07-12 15:39:03',
            'modified' => '2015-07-12 15:39:03',
        ],
        [
            'id' => 'c628ae1b-f846-4818-a043-fb945ebbfdb9',
            'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'contact_id' => null,
            'kind' => 'BY',
            'title' => 'ARHIM Is Buyer ltd',
            'created' => '2015-07-12 15:39:03',
            'modified' => '2015-07-12 15:39:03',
        ],

        [
            'id' => 'c628ae1b-f846-4818-a043-fb945ebbfdc0',
            'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'contact_id' => null,
            'kind' => 'II',
            'title' => 'ARHIM Is Issuer ltd',
            'created' => '2015-07-12 15:39:03',
            'modified' => '2015-07-12 15:39:03',
        ],
        [
            'id' => 'c628ae1b-f846-4818-a043-fb945ebbfdc1',
            'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'contact_id' => null,
            'kind' => 'IV',
            'title' => 'SomeCompany Is Receiver ltd',
            'created' => '2015-07-12 15:39:03',
            'modified' => '2015-07-12 15:39:03',
        ],
        [
            'id' => 'c628ae1b-f846-4818-a043-fb945ebbfdc2',
            'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'contact_id' => null,
            'kind' => 'BY',
            'title' => 'SomeCompany Is Buyer ltd',
            'created' => '2015-07-12 15:39:03',
            'modified' => '2015-07-12 15:39:03',
        ],
    ];
}
