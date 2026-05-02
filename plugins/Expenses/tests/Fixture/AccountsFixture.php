<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AccountsFixture – minimal chart-of-accounts records for testing.
 */
class AccountsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => 1,
            'parent_id' => null,
            'code' => '1',
            'name' => 'Assets',
            'lft' => 1,
            'rght' => 4,
            'level' => 0,
            'created' => '2026-01-01 00:00:00',
            'modified' => '2026-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'parent_id' => 1,
            'code' => '100',
            'name' => 'Cash',
            'lft' => 2,
            'rght' => 3,
            'level' => 1,
            'created' => '2026-01-01 00:00:00',
            'modified' => '2026-01-01 00:00:00',
        ],
    ];
}
