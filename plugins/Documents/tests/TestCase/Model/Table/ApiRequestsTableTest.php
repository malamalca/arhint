<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Model\Table;

use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Model\Table\ApiRequestsTable;

class ApiRequestsTableTest extends TestCase
{
    public array $fixtures = ['plugin.Documents.ApiRequests'];

    private const USER = '048acacf-d87c-4088-a3a7-4bab30f6a040';

    private ApiRequestsTable $ApiRequests;

    public function setUp(): void
    {
        parent::setUp();

        $this->ApiRequests = TableRegistry::getTableLocator()->get('Documents.ApiRequests');
    }

    public function testClaimCompleteAndReplay(): void
    {
        [$status, $record] = $this->ApiRequests->claim(self::USER, 'k1', 'hash');
        $this->assertSame(ApiRequestsTable::CLAIMED, $status);

        [$status] = $this->ApiRequests->claim(self::USER, 'k1', 'hash');
        $this->assertSame(ApiRequestsTable::IN_PROGRESS, $status);

        $this->ApiRequests->complete($record, 'invoice-1');

        [$status, $existing] = $this->ApiRequests->claim(self::USER, 'k1', 'hash');
        $this->assertSame(ApiRequestsTable::REPLAY, $status);
        $this->assertSame('invoice-1', $existing->invoice_id);

        [$status] = $this->ApiRequests->claim(self::USER, 'k1', 'other');
        $this->assertSame(ApiRequestsTable::MISMATCH, $status);
    }

    public function testKeysAreScopedPerUser(): void
    {
        $this->ApiRequests->claim(self::USER, 'k1', 'hash');

        [$status] = $this->ApiRequests->claim('048acacf-d87c-4088-a3a7-4bab30f6a041', 'k1', 'hash');

        $this->assertSame(ApiRequestsTable::CLAIMED, $status);
    }

    public function testReleaseAllowsClaimingAgainButNotAfterCompletion(): void
    {
        [, $record] = $this->ApiRequests->claim(self::USER, 'k1', 'hash');
        $this->ApiRequests->release($record);
        [$status, $record] = $this->ApiRequests->claim(self::USER, 'k1', 'hash');
        $this->assertSame(ApiRequestsTable::CLAIMED, $status);

        $this->ApiRequests->complete($record, 'invoice-1');
        $this->ApiRequests->release($record);

        [$status] = $this->ApiRequests->claim(self::USER, 'k1', 'hash');
        $this->assertSame(ApiRequestsTable::REPLAY, $status);
    }

    public function testAbandonedRequestIsTakenOver(): void
    {
        [, $record] = $this->ApiRequests->claim(self::USER, 'k1', 'hash');
        $this->ApiRequests->updateAll(['modified' => new DateTime('-10 minutes')], ['id' => $record->id]);

        [$status] = $this->ApiRequests->claim(self::USER, 'k1', 'hash');

        $this->assertSame(ApiRequestsTable::CLAIMED, $status);
    }
}
