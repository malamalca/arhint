<?php
namespace LilInvoices\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesTemplatesFixture
 */
class InvoicesTemplatesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 'a08d3c00-7443-40e0-ac62-0caca1747e24',
            'owner_id' => COMPANY_FIRST,
            'kind' => 'header',
            'body' => 'Test header',
            'main' => true,
        ],

    ];
}
