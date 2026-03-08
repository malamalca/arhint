<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AttachmentsFixture
 */
class AttachmentsFixture extends TestFixture
{
    public string $connection = 'test';

    public function init(): void
    {
        parent::init();

        $this->records = [
            [
                'id' => '3e7c2fba-1c29-4e5b-9bb2-000000000001',
                'model' => 'Test',
                // foreign_id does not exist in any documents table, so
                // isOwnedBy() returns true for any logged-in user
                'foreign_id' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'filename' => 'test.pdf',
                'ext' => 'pdf',
                'mimetype' => 'application/pdf',
                'filesize' => 1024,
                'description' => 'Test attachment',
                'created' => '2025-01-01 00:00:00',
                'modified' => '2025-01-01 00:00:00',
            ],
        ];
    }
}
