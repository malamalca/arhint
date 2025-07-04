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

        $this->records = [];
    }
}
