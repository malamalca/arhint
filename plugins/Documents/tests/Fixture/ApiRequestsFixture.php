<?php
declare(strict_types=1);

namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ApiRequestsFixture - empty table, tests create their own records
 */
class ApiRequestsFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'documents_api_requests';

    /**
     * Records
     *
     * @var array
     */
    public array $records = [];
}
