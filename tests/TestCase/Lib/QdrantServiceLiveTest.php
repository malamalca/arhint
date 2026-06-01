<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\QdrantService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * @group live
 */
class QdrantServiceLiveTest extends TestCase
{
    private const DEFAULT_HOST = '192.168.88.30:6333';
    // Collection name is set in setUp() — no runtime expression in constants.
    private const VECTOR_SIZE = 1024;

    /**
     * @var string Live Qdrant host from config or env.
     */
    private string $host;

    /**
     * @var string Test collection name (unique per run).
     */
    private string $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = (string)(getenv('ARHINT_QDRANT_HOST') ?: Configure::read('Qdrant.host', self::DEFAULT_HOST));
        // Use a deterministic collection name for this run.
        $this->collection = 'arhint_test_' . md5((string)time());

        // Temporarily override Qdrant config so the service uses our test host.
        $scheme = (string)Configure::read('Qdrant.scheme', 'http');
        Configure::write('Qdrant.host', $this->host);
        Configure::write('Qdrant.scheme', $scheme);
        Configure::write('Qdrant.collection', $this->collection);
    }

    protected function tearDown(): void
    {
        // Clean up: delete test collection if it exists.
        $this->deleteCollection($this->collection);
        parent::tearDown();
    }

    /**
     * Test that the live Qdrant server is reachable.
     */
    public function testServerIsReachable(): void
    {
        $this->skipIfUnavailable();
        $this->assertTrue(true, 'Qdrant server is reachable.');
    }

    /**
     * Test creating a collection with correct dimensions.
     */
    public function testCreateCollection(): void
    {
        $this->skipIfUnavailable();

        $result = $this->createCollection($this->collection, self::VECTOR_SIZE);
        $this->assertTrue($result, 'Collection should be created.');
    }

    /**
     * Test upserting a single point.
     */
    public function testUpsert(): void
    {
        $this->skipIfUnavailable();

        // Ensure collection exists first.
        $this->createCollection($this->collection, self::VECTOR_SIZE);

        try {
            $service = new QdrantService();
            $vector = $this->buildTestVector(self::VECTOR_SIZE);
            $payload = [
                'summary' => 'Test upsert event',
                'priority' => 1,
            ];

            $id = '00000001-0000-0000-0000-000000000001';
            $result = $service->upsert($id, $vector, $payload);
            $this->assertTrue($result, 'Upsert should succeed.');
        } catch (Exception $e) {
            $this->fail('Upsert failed: ' . $e->getMessage());
        }
    }

    /**
     * Test searching for an upserted point returns a perfect match.
     */
    public function testSearchReturnsUpseartedPoint(): void
    {
        $this->skipIfUnavailable();

        // Ensure collection exists first.
        $this->createCollection($this->collection, self::VECTOR_SIZE);

        $service = new QdrantService();
        $vector = $this->buildTestVector(self::VECTOR_SIZE);
        $payload = [
            'summary' => 'Search test event',
            'priority' => 3,
        ];

        // Upsert first.
        $searchId = '00000002-0000-0000-0000-000000000002';
        $upserted = $service->upsert($searchId, $vector, $payload);
        $this->assertTrue($upserted, 'Upsert should succeed before search.');

        // Search with same vector — should get score of 1.0 (cosine).
        $results = $service->search($vector, [], 5);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results, 'Search should return results.');

        $firstResult = $results[0] ?? null;
        $this->assertNotNull($firstResult, 'First result should not be null.');
        $this->assertArrayHasKey('id', $firstResult);
        $this->assertSame($searchId, $firstResult['id']);
        $this->assertGreaterThanOrEqual(0.99, $firstResult['score'] ?? 0, 'Score should be near 1.0 for identical vector.');
    }

    /**
     * Test upsert with payload fields matches expected structure.
     */
    public function testUpsertWithPayloadFields(): void
    {
        $this->skipIfUnavailable();

        // Ensure collection exists first.
        $this->createCollection($this->collection, self::VECTOR_SIZE);

        try {
            $service = new QdrantService();
            $vector = $this->buildTestVector(self::VECTOR_SIZE);
            $payload = [
                'log_id' => 'abc-123',
                'log_model' => 'Projects.Project',
                'summary' => 'Project log entry test',
                'priority' => 5,
            ];

            $id3 = '00000003-0000-0000-0000-000000000003';
            $result = $service->upsert($id3, $vector, $payload);
            $this->assertTrue($result, 'Upsert with full payload should succeed.');
        } catch (Exception $e) {
            $this->fail('Upsert failed: ' . $e->getMessage());
        }
    }

    /**
     * Test that the QdrantService class can be instantiated.
     */
    public function testQdrantServiceClassInstantiation(): void
    {
        // Config is set in setUp, so this should work.
        $service = new QdrantService();
        $this->assertInstanceOf(QdrantService::class, $service);
    }

    private function skipIfUnavailable(): void
    {
        $ch = curl_init($this->buildUrl('/'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            $this->markTestSkipped('Qdrant server is not available at ' . $this->host);
        }
    }

    /**
     * Build a deterministic test vector.
     *
     * @param int $size Vector dimension.
     * @return array<int, float>
     */
    private function buildTestVector(int $size): array
    {
        return [1.0] + array_fill(1, $size - 1, 0.0);
    }

    /**
     * Build a URL for the given path.
     *
     * @param string $path Path relative to Qdrant host.
     * @return string
     */
    private function buildUrl(string $path): string
    {
        $scheme = (string)Configure::read('Qdrant.scheme', 'http');

        return rtrim($scheme . '://' . $this->host, '/') . $path;
    }

    /**
     * Create a collection.
     *
     * @param string $name Collection name.
     * @param int    $size Vector dimension.
     * @return bool
     */
    private function createCollection(string $name, int $size): bool
    {
        $url = $this->buildUrl('/collections/' . urlencode($name));

        $body = json_encode([
            'vectors' => [
                'size' => $size,
                'distance' => 'Cosine',
            ],
        ]);
        if ($body === false) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response !== false && $httpCode === 200;
    }

    /**
     * Delete a collection. Best effort — never fails the test.
     *
     * @param string $name Collection name.
     * @return void
     */
    private function deleteCollection(string $name): void
    {
        $url = $this->buildUrl('/collections/' . urlencode($name));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
    }
}
