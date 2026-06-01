<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\VectorDBService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * @group live
 */
class VectorDBServiceLiveTest extends TestCase
{
    private const DEFAULT_HOST = '192.168.88.30:8001';
    // ChromaDB infers dimensions from the first upsert, but we use a consistent size for tests.
    private const VECTOR_SIZE = 3;

    /**
     * @var string Live ChromaDB host from config or env.
     */
    private string $host;

    /**
     * @var string Test collection name (unique per run).
     */
    private string $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = (string)(getenv('ARHINT_VECTORDB_HOST') ?: Configure::read('VectorDB.host', self::DEFAULT_HOST));
        // Use a truly unique collection name using random bytes.
        $this->collection = 'arhint_test_' . bin2hex(random_bytes(8));

        // Temporarily override VectorDB config so the service uses our test host.
        $scheme = (string)Configure::read('VectorDB.scheme', 'http');
        Configure::write('VectorDB.host', $this->host);
        Configure::write('VectorDB.scheme', $scheme);
        Configure::write('VectorDB.collection', $this->collection);
    }

    protected function tearDown(): void
    {
        // Clean up: delete test collection if it exists.
        try {
            $service = new VectorDBService();
            $service->deleteCollection();
        } catch (Exception) {
            // Ignore cleanup errors.
        }

        parent::tearDown();
    }

    /**
     * Test that the live ChromaDB server is reachable.
     */
    public function testServerIsReachable(): void
    {
        $this->skipIfUnavailable();
        $this->assertTrue(true, 'ChromaDB server is reachable.');
    }

    /**
     * Test resolving a new collection (creates it).
     */
    public function testResolveCollectionId(): void
    {
        $this->skipIfUnavailable();

        try {
            $service = new VectorDBService();
            $id = $service->resolveCollectionId();
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id);
        } catch (Exception $e) {
            $this->fail('resolveCollectionId failed: ' . $e->getMessage());
        }
    }

    /**
     * Test upserting a single document.
     */
    public function testUpsertOne(): void
    {
        $this->skipIfUnavailable();

        try {
            $service = new VectorDBService();
            $service->resolveCollectionId();

            $vector = $this->buildTestVector(self::VECTOR_SIZE);
            $metadata = [
                'summary' => 'Test upsert event',
                'priority' => 1,
            ];

            $id = '00000001-0000-0000-0000-000000000001';
            $result = $service->upsertOne($id, $vector, null, $metadata);
            $this->assertTrue($result, 'Upsert should succeed.');
        } catch (Exception $e) {
            $this->fail('Upsert failed: ' . $e->getMessage());
        }
    }

    /**
     * Test searching for an upserted document returns a perfect match.
     */
    public function testSearchReturnsUpsertedDocument(): void
    {
        $this->skipIfUnavailable();

        try {
            $service = new VectorDBService();
            $service->resolveCollectionId();

            $vector = $this->buildTestVector(self::VECTOR_SIZE);
            $metadata = [
                'summary' => 'Search test event',
                'priority' => 3,
            ];

            // Upsert first.
            $searchId = '00000002-0000-0000-0000-000000000002';
            $upserted = $service->upsertOne($searchId, $vector, null, $metadata);
            $this->assertTrue($upserted, 'Upsert should succeed before search.');

            // Search with same vector — should get distance of ~0.0 (L2).
            $results = $service->search($vector, 5);
            $this->assertIsArray($results);
            $this->assertNotEmpty($results, 'Search should return results.');

            $firstResult = $results[0] ?? null;
            $this->assertNotNull($firstResult, 'First result should not be null.');
            $this->assertArrayHasKey('id', $firstResult);
            $this->assertSame($searchId, $firstResult['id']);
            // ChromaDB uses L2 distance by default — identical vectors have distance 0.
            $this->assertLessThan(0.01, $firstResult['distance'] ?? 999, 'Distance should be near 0.0 for identical vector.');
        } catch (Exception $e) {
            $this->fail('Search test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test upsert with metadata fields matches expected structure.
     */
    public function testUpsertWithMetadataFields(): void
    {
        $this->skipIfUnavailable();

        try {
            $service = new VectorDBService();
            $service->resolveCollectionId();

            $vector = $this->buildTestVector(self::VECTOR_SIZE);
            $metadata = [
                'log_id' => 'abc-123',
                'log_model' => 'Projects.Project',
                'summary' => 'Project log entry test',
                'priority' => 5,
            ];

            $id3 = '00000003-0000-0000-0000-000000000003';
            $result = $service->upsertOne($id3, $vector, null, $metadata);
            $this->assertTrue($result, 'Upsert with full metadata should succeed.');

            // Search and verify metadata is returned.
            $results = $service->search($vector, 5);
            $this->assertNotEmpty($results);
            $found = false;
            foreach ($results as $r) {
                if (($r['id'] ?? null) === $id3) {
                    $meta = $r['metadata'] ?? [];
                    $this->assertSame('Project log entry test', $meta['summary'] ?? '');
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Upserted document should be found in search results.');
        } catch (Exception $e) {
            $this->fail('Upsert with metadata failed: ' . $e->getMessage());
        }
    }

    /**
     * Test batch upsert.
     */
    public function testBatchUpsert(): void
    {
        $this->skipIfUnavailable();

        try {
            $service = new VectorDBService();
            $service->resolveCollectionId();

            $ids = ['batch-1', 'batch-2', 'batch-3'];
            $vectors = [
                $this->buildTestVector(self::VECTOR_SIZE),
                $this->buildTestVector(self::VECTOR_SIZE, 0.5),
                $this->buildTestVector(self::VECTOR_SIZE, 0.25),
            ];
            $metadatas = [
                ['summary' => 'First batch item'],
                ['summary' => 'Second batch item'],
                ['summary' => 'Third batch item'],
            ];

            $result = $service->upsert($ids, $vectors, [], $metadatas);
            $this->assertTrue($result, 'Batch upsert should succeed.');

            // Search and verify all documents are returned.
            $results = $service->search($vectors[0], 10);
            $this->assertGreaterThanOrEqual(3, count($results), 'Should find at least 3 batch documents.');
        } catch (Exception $e) {
            $this->fail('Batch upsert failed: ' . $e->getMessage());
        }
    }

    /**
     * Test search with where filter.
     */
    public function testSearchWithWhereFilter(): void
    {
        $this->skipIfUnavailable();

        try {
            $service = new VectorDBService();
            $service->resolveCollectionId();

            $vector = $this->buildTestVector(self::VECTOR_SIZE);

            // Upsert documents with different log_foreign_id values.
            $service->upsertOne('filter-a', $vector, null, ['log_foreign_id' => 'project-1', 'summary' => 'Doc A']);
            $service->upsertOne('filter-b', $vector, null, ['log_foreign_id' => 'project-2', 'summary' => 'Doc B']);

            // Search with filter for project-1.
            $where = ['log_foreign_id' => 'project-1'];
            $results = $service->search($vector, 10, $where);
            $this->assertNotEmpty($results);

            foreach ($results as $r) {
                $meta = $r['metadata'] ?? [];
                $this->assertSame('project-1', $meta['log_foreign_id'] ?? '', 'All results should match filter.');
            }
        } catch (Exception $e) {
            $this->fail('Search with where filter failed: ' . $e->getMessage());
        }
    }

    /**
     * Test that the VectorDBService class can be instantiated.
     */
    public function testVectorDBServiceClassInstantiation(): void
    {
        // Config is set in setUp, so this should work.
        $service = new VectorDBService();
        $this->assertInstanceOf(VectorDBService::class, $service);
    }

    /**
     * Test delete collection.
     */
    public function testDeleteCollection(): void
    {
        $this->skipIfUnavailable();

        try {
            $service = new VectorDBService();
            $service->resolveCollectionId();

            // Create a new service instance to verify deletion works independently.
            $deleteResult = $service->deleteCollection();
            $this->assertTrue($deleteResult, 'Delete collection should succeed.');
        } catch (Exception $e) {
            $this->fail('Delete collection failed: ' . $e->getMessage());
        }
    }

    /**
     * Test upsert with document content.
     */
    public function testUpsertWithDocument(): void
    {
        $this->skipIfUnavailable();

        try {
            $service = new VectorDBService();
            $service->resolveCollectionId();

            $vector = $this->buildTestVector(self::VECTOR_SIZE);

            $id = 'doc-with-content';
            $result = $service->upsertOne($id, $vector, 'This is the document content', ['summary' => 'Has doc']);
            $this->assertTrue($result, 'Upsert with document should succeed.');

            // Search and verify document is returned.
            $results = $service->search($vector, 5);
            $found = false;
            foreach ($results as $r) {
                if (($r['id'] ?? null) === $id) {
                    $this->assertSame('This is the document content', $r['document'] ?? '');
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Document should be found in search results.');
        } catch (Exception $e) {
            $this->fail('Upsert with document failed: ' . $e->getMessage());
        }
    }

    private function skipIfUnavailable(): void
    {
        $ch = curl_init($this->buildUrl('/api/v2/heartbeat'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $response = curl_exec($ch);

        if ($response === false) {
            $this->markTestSkipped('ChromaDB server is not available at ' . $this->host);
        }
    }

    /**
     * Build a deterministic test vector.
     *
     * @param int $size Vector dimension.
     * @param float $fillValue Value to fill remaining elements with.
     * @return array<int, float>
     */
    private function buildTestVector(int $size, float $fillValue = 0.0): array
    {
        $vector = array_fill(0, $size, $fillValue);
        $vector[0] = 1.0;

        return $vector;
    }

    /**
     * Build a URL for the given path.
     *
     * @param string $path Path relative to ChromaDB host.
     * @return string
     */
    private function buildUrl(string $path): string
    {
        $scheme = (string)Configure::read('VectorDB.scheme', 'http');

        return rtrim($scheme . '://' . $this->host, '/') . $path;
    }
}
