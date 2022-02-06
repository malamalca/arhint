<?php
namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesItemsFixture
 */
class InvoicesItemsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'item_id' => null,
            'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
            'descript' => 'Hrup\' 13',
            'qty' => 1,
            'unit' => 'pcs',
            'price' => 290,
            'discount' => 0,
            'created' => '2015-02-08 06:34:24',
            'modified' => '2015-02-08 06:34:24',
        ],
    ];
}
