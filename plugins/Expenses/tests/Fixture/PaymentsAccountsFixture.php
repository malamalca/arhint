<?php
namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PaymentsAccountsFixture
 */
class PaymentsAccountsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => 'c7f20dee-74f1-40e5-a129-d46d6fb43153',
            'owner_id' => COMPANY_FIRST,
            'title' => 'Default Bank Account',
            'primary' => true,
            'active' => true,
            'created' => '2015-08-16 17:39:09',
            'modified' => '2015-08-16 17:39:09',
        ],
    ];
}
