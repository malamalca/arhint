<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\EmbeddingService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * @group live
 */
class EmbeddingServiceLiveTest extends TestCase
{
    private const DEFAULT_LIVE_URL = 'http://127.0.0.1:8000/embed';

    /**
     * @var string The live embedding URL from config or env.
     */
    private string $liveUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->liveUrl = (string)(getenv('ARHINT_EMBEDDING_URL') ?: Configure::read('Embedding.url', self::DEFAULT_LIVE_URL));
    }

    /**
     * Test that the live embedding server is reachable and returns a vector.
     */
    public function testProbeLiveServer(): void
    {
        $this->skipIfUnavailable();
        $result = $this->probe();
        $this->assertNotNull($result, 'Embedding server should be reachable.');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('vector', $result);
    }

    /**
     * Test embedding via cURL directly (no service class needed).
     */
    public function testCurlEmbedReturnsVector(): void
    {
        $this->skipIfUnavailable();
        $result = $this->probe();
        $this->assertNotNull($result);

        $vector = $result['vector'] ?? [];
        $this->assertNotEmpty($vector, 'Response should contain a non-empty vector.');
        $this->assertIsFloat($vector[0], 'Vector elements should be floats.');
    }

    /**
     * Test that the EmbeddingService class itself works against the live server.
     */
    public function testEmbeddingServiceClass(): void
    {
        $this->skipIfUnavailable();

        // Configure will already have Embedding.url set if it's configured in app.php.
        $service = new EmbeddingService();
        $vector = $service->embed('Hello, this is a live embedding test.');

        $this->assertIsArray($vector);
        $this->assertNotEmpty($vector);
        $this->assertContainsOnly('float', $vector);
    }

    /**
     * Test that different text produces different vectors.
     */
    public function testDifferentTextProducesDifferentVector(): void
    {
        $this->skipIfUnavailable();
        $resultA = $this->probeWithText('Hello world');
        $resultB = $this->probeWithText('Goodbye moon');

        $this->assertNotNull($resultA);
        $this->assertNotNull($resultB);

        $vectorA = $resultA['vector'] ?? [];
        $vectorB = $resultB['vector'] ?? [];

        // Vectors should differ at least somewhere.
        $this->assertNotSame(implode(',', $vectorA), implode(',', $vectorB));
    }

    private function skipIfUnavailable(): void
    {
        if ($this->probe() === null) {
            $this->markTestSkipped('Embedding server is not available at ' . $this->liveUrl);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function probe(): ?array
    {
        return $this->probeWithText('This is a test sentence for embedding.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function probeWithText(string $text): ?array
    {
        $payload = json_encode(['text' => $text]);
        if ($payload === false) {
            return null;
        }

        $ch = curl_init($this->liveUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        return json_decode((string)$response, true);
    }
}
