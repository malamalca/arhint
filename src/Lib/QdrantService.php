<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use Exception;

/**
 * Service for interacting with a Qdrant vector database.
 *
 * Configuration is read from Configure::read('Qdrant') with keys:
 * - host (string)     Host and port, e.g. "127.0.0.1:6333"
 * - scheme (string)   HTTP or HTTPS (default "http")
 * - collection (string) Default collection name (default "events")
 * - api_key (string)  Optional API key for authenticated clusters
 * - timeout (int)     cURL timeout in seconds (default 30).
 */
class QdrantService
{
    /**
     * @var string Full base URL.
     */
    private string $baseUrl;

    /**
     * @var string Collection name.
     */
    private string $collection;

    /**
     * @var string|null Optional API key.
     */
    private ?string $apiKey;

    /**
     * @var int cURL timeout in seconds.
     */
    private int $timeout;

    /**
     * Constructor.
     *
     * Reads Qdrant configuration from CakePHP Configure.
     *
     * @throws \Exception If the 'Qdrant.host' config key is missing or empty.
     */
    public function __construct()
    {
        $host = (string)Configure::read('Qdrant.host', '');
        if ($host === '') {
            throw new Exception(
                'Qdrant service not configured. Set Qdrant.host in your app configuration.',
            );
        }

        $scheme = (string)Configure::read('Qdrant.scheme', 'http');
        $this->baseUrl = rtrim($scheme . '://' . $host, '/') . '/collections';
        $this->collection = (string)Configure::read('Qdrant.collection', 'events');
        $this->apiKey = (string)Configure::read('Qdrant.api_key', '');
        $this->timeout = (int)Configure::read('Qdrant.timeout', 30);
    }

    /**
     * Insert or update a single point in the collection.
     *
     * @param string               $id      Point ID (UUID string).
     * @param array<int, float>    $vector  Embedding vector.
     * @param array<string, mixed> $payload Metadata attached to the point.
     * @return bool True on success.
     * @throws \Exception If the API call fails.
     */
    public function upsert(string $id, array $vector, array $payload): bool
    {
        $url = $this->baseUrl . '/' . urlencode($this->collection) . '/points?wait=true';

        $body = json_encode([
            'points' => [
                [
                    'id' => $id,
                    'vector' => $vector,
                    'payload' => $payload,
                ],
            ],
        ]);
        if ($body === false) {
            throw new Exception('Failed to encode Qdrant request: ' . json_last_error_msg());
        }

        return $this->put($url, $body);
    }

    /**
     * Search for points similar to the given vector.
     *
     * @param array<int, float>    $vector  Query embedding vector.
     * @param array<string, mixed> $filter  Qdrant filter object (optional).
     * @param int                  $limit   Maximum number of results (default 10).
     * @return array<int, mixed> Search results, or empty array on failure.
     */
    public function search(array $vector, array $filter = [], int $limit = 10): array
    {
        $url = $this->baseUrl . '/' . urlencode($this->collection) . '/points/search';

        $body = json_encode([
            'vector' => $vector,
            'limit' => $limit,
            'filter' => $filter !== [] ? $filter : null,
            'with_payload' => true,
        ]);
        if ($body === false) {
            throw new Exception('Failed to encode Qdrant search request: ' . json_last_error_msg());
        }

        $decoded = $this->send($url, $body, 'POST');
        if (!is_array($decoded)) {
            return [];
        }

        // Handle the "result" key pattern from some Qdrant response wrappers.
        return $decoded['result'] ?? $decoded;
    }

    /**
     * HTTP PUT a JSON payload and return success status.
     *
     * @param string $url   Target URL.
     * @param string $body  JSON-encoded request body.
     * @return bool True on success (HTTP 200/201).
     */
    private function put(string $url, string $body): bool
    {
        return $this->send($url, $body, 'PUT') !== null;
    }

    /**
     * Send a JSON request and return the decoded response.
     *
     * @param string $url  Target URL.
     * @param string $body JSON-encoded request body.
     * @param string $method HTTP method (POST or PUT).
     * @return mixed|null Decoded JSON response, or null on failure.
     */
    private function send(string $url, string $body, string $method): mixed
    {
        $ch = curl_init($url);
        if ($ch === false) {
            Log::error('QdrantService: failed to initialize cURL', ['scope' => ['ai']]);

            return null;
        }

        $headers = ['Content-Type: application/json'];
        if ($this->apiKey !== '') {
            $headers[] = 'api-key: ' . $this->apiKey;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            Log::error(sprintf(
                'QdrantService: cURL failed [errno %d] to %s: %s',
                $curlErrno,
                $url,
                $curlError,
            ), ['scope' => ['ai']]);

            return null;
        }

        $decoded = json_decode((string)$response, true);
        if (is_array($decoded) && isset($decoded['status']['error'])) {
            Log::error(sprintf(
                'QdrantService: API error [HTTP %d] from %s: %s',
                $httpCode,
                $url,
                $decoded['status']['error'],
            ), ['scope' => ['ai']]);

            return null;
        }

        return $decoded;
    }
}
