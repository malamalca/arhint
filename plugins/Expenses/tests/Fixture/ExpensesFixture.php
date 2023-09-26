<?php
namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ExpensesFixture
 */
class ExpensesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => 'c60fe467-cd81-4a2c-b25d-4c3e0fe8c63e',
            'owner_id' => COMPANY_FIRST,
            'model' => null,
            'foreign_id' => null,
            'project_id' => null,
            'dat_happened' => '2015-08-16',
            'month' => '2015-08',
            'title' => 'This is random unlinked expense',
            'net_total' => 10,
            'total' => 10.22,
            'created' => '2015-08-16 17:36:42',
            'modified' => '2015-08-16 17:36:42',
        ],
    ];
}
