<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DashboardNotesFixture
 */
class DashboardNotesFixture extends TestFixture
{
    public string $connection = 'test';

    public function init(): void
    {
        $this->records = [
            [
                'id' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeee01',
                'user_id' => '048acacf-d87c-4088-a3a7-4bab30f6a040', // USER_ADMIN
                'note' => 'Fixture note for admin user',
                'created' => '2025-01-01 10:00:00',
                'modified' => '2025-01-01 10:00:00',
            ],
            [
                'id' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeee02',
                'user_id' => '048acacf-d87c-4088-a3a7-4bab30f6a041', // USER_COMMON
                'note' => 'Fixture note for common user',
                'created' => '2025-01-01 11:00:00',
                'modified' => '2025-01-01 11:00:00',
            ],
        ];
        parent::init();
    }
}
