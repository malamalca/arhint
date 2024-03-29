<?php
namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PaymentsFixture
 */
class PaymentsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => 'c7f20dee-74f1-40e5-a3a9-d46d6fb43153',
            'owner_id' => COMPANY_FIRST,
            'account_id' => 'c7f20dee-74f1-40e5-a129-d46d6fb43153',
            'dat_happened' => '2015-08-16',
            'descript' => 'Lorem ipsum dolor sit amet',
            'amount' => 1,
            'sepa_id' => '23',
            'created' => '2015-08-16 17:39:09',
            'modified' => '2015-08-16 17:39:09',
        ],
    ];
}
