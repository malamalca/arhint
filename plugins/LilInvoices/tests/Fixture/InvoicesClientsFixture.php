<?php
namespace LilInvoices\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesClientsFixture
 */
class InvoicesClientsFixture extends TestFixture
{
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
