<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use Exception;

/**
 * Service for interacting with a Chroma vector database via the v2 HTTP API.
 *
 * ChromaDB v2 uses a tenant/database/collection hierarchy. This service defaults
 * to tenant "default_tenant" and database "default_database".
 *
 * Configuration is read from Configure::read('VectorDB') with keys:
 * - host (string)     Host and port, e.g. "192.168.88.30:8001"
 * - scheme (string)   HTTP or HTTPS (default "http")
 * - tenant (string)   ChromaDB tenant name (default "default_tenant")
 * - database (string) ChromaDB database name (default "default_database")
 * - collection (string) Default collection name (default "events")
 * - timeout (int)     cURL timeout in seconds (default 30).
 */
class VectorDBService
{
    /**
     * @var string Full base URL for the v2 API.
     */
    private string $baseUrl;

    /**
     * @var string Tenant name.
     */
    private string $tenant;

    /**
     * @var string Database name.
     */
    private string $database;

    /**
     * @var string Collection name.
     */
    private string $collection;

    /**
     * @var string|null Cached collection UUID (resolved once per request).
     */
    private ?string $collectionId = null;

    /**
     * @var int cURL timeout in seconds.
     */
    private int $timeout;

    /**
     * Constructor.
     *
     * Reads VectorDB configuration from CakePHP Configure.
     *
     * @throws \Exception If the 'VectorDB.host' config key is missing or empty.
     */
    public function __construct()
    {
        $host = (string)Configure::read('VectorDB.host', '');
        if ($host === '') {
            throw new Exception(
                'VectorDB service not configured. Set VectorDB.host in your app configuration.',
            );
        }

        $scheme = (string)Configure::read('VectorDB.scheme', 'http');
        $this->baseUrl = rtrim($scheme . '://' . $host, '/') . '/api/v2';
        $this->tenant = (string)Configure::read('VectorDB.tenant', 'default_tenant');
        $this->database = (string)Configure::read('VectorDB.database', 'default_database');
        $this->collection = (string)Configure::read('VectorDB.collection', 'events');
        $this->timeout = (int)Configure::read('VectorDB.timeout', 30);
    }

    /**
     * Build the collections list URL.
     *
     * @return string Full URL for listing/creating collections.
     */
    private function collectionsUrl(): string
    {
        return $this->baseUrl
            . '/tenants/' . urlencode($this->tenant)
            . '/databases/' . urlencode($this->database)
            . '/collections';
    }

    /**
     * Build a collection-specific endpoint URL.
     *
     * @param string $collectionId The ChromaDB collection UUID.
     * @param string|null $endpoint Optional sub-endpoint (e.g. "add", "query", "upsert").
     * @return string Full URL for the collection endpoint.
     */
    private function collectionEndpointUrl(string $collectionId, ?string $endpoint = null): string
    {
        $url = $this->collectionsUrl() . '/' . urlencode($collectionId);

        if ($endpoint !== null && $endpoint !== '') {
            $url .= '/' . ltrim($endpoint, '/');
        }

        return $url;
    }

    /**
     * Get metadata for the current collection (without creating it).
     *
     * Returns null if the collection does not exist or the request fails.
     *
     * @return array<string, mixed>|null Collection info including id, name, metadata, dimension, etc.
     */
    public function getCollectionInfo(): ?array
    {
        $url = $this->collectionsUrl();
        $response = $this->send($url, '', 'GET');

        if (!is_array($response)) {
            return null;
        }

        foreach ($response as $col) {
            if (isset($col['name']) && $col['name'] === $this->collection) {
                return $col;
            }
        }

        return null;
    }

    /**
     * Resolve the collection name to its ChromaDB UUID.
     * Creates the collection if it does not exist.
     *
     * @return string Collection UUID.
     */
    public function resolveCollectionId(): string
    {
        // Return cached ID if available.
        if ($this->collectionId !== null) {
            return $this->collectionId;
        }

        $url = $this->collectionsUrl();
        $response = $this->send($url, '', 'GET');

        if (is_array($response)) {
            foreach ($response as $col) {
                if (isset($col['name']) && $col['name'] === $this->collection) {
                    $this->collectionId = (string)$col['id'];

                    return $this->collectionId;
                }
            }
        }

        // Collection not found — create it.
        $body = json_encode(['name' => $this->collection]);
        if ($body === false) {
            throw new Exception('Failed to encode create collection request: ' . json_last_error_msg());
        }

        $response = $this->send($url, $body, 'POST');
        if (!is_array($response) || !isset($response['id'])) {
            throw new Exception(sprintf(
                'Failed to create collection "%s" in ChromaDB',
                $this->collection,
            ));
        }

        $this->collectionId = (string)$response['id'];
        Log::debug(sprintf(
            'VectorDBService: created collection "%s" (id: %s)',
            $this->collection,
            $this->collectionId,
        ), ['scope' => ['ai']]);

        return $this->collectionId;
    }

    /**
     * Delete the collection. Best effort — never throws.
     * Returns true if the collection was deleted or did not exist.
     *
     * @return bool True on success (deleted or already absent).
     */
    public function deleteCollection(): bool
    {
        // ChromaDB v2 DELETE endpoint requires the collection name, not UUID.
        // A 404 here just means the collection is already absent, which is a
        // successful outcome for a delete — allow it through without an error log.
        $url = $this->collectionsUrl() . '/' . urlencode($this->collection);
        $response = $this->send($url, '', 'DELETE', [404]);

        // ChromaDB returns {} on success or an error object.
        if (!is_array($response)) {
            return false;
        }

        // Not found is acceptable — collection was already gone.
        if (isset($response['error']) && str_contains((string)($response['message'] ?? ''), 'does not exist')) {
            return true;
        }

        return !isset($response['error']);
    }

    /**
     * Insert or update documents in the collection.
     *
     * @param array<int, string>               $ids      Document IDs (UUID strings).
     * @param array<int, array<int, float>>    $vectors  Embedding vectors (one per document).
     * @param array<int, string|null>          $documents Optional document text content.
     * @param array<int, array<string, mixed>> $metadata Metadata arrays (one per document).
     * @return bool True on success.
     */
    public function upsert(
        array $ids,
        array $vectors,
        array $documents = [],
        array $metadata = [],
    ): bool {
        try {
            $id = $this->resolveCollectionId();
        } catch (Exception) {
            return false;
        }

        $url = $this->collectionEndpointUrl($id, 'upsert');

        $body = json_encode([
            'ids' => $ids,
            'embeddings' => $vectors,
            'documents' => $documents !== [] ? $documents : null,
            'metadatas' => $metadata !== [] ? $metadata : null,
        ]);
        if ($body === false) {
            Log::error('VectorDBService: failed to encode upsert request', ['scope' => ['ai']]);

            return false;
        }

        $response = $this->send($url, $body, 'POST');

        return is_array($response) && !isset($response['error']);
    }

    /**
     * Upsert a single document (convenience wrapper).
     *
     * @param string                     $id       Document ID (UUID string).
     * @param array<int, float>          $vector   Embedding vector.
     * @param string|null                $document Optional document text content.
     * @param array<string, mixed>       $metadata Metadata attached to the document.
     * @return bool True on success.
     */
    public function upsertOne(
        string $id,
        array $vector,
        ?string $document = null,
        array $metadata = [],
    ): bool {
        return $this->upsert([$id], [$vector], [$document], [$metadata]);
    }

    /**
     * Search for documents similar to the given vectors.
     *
     * @param array<int, float>          $queryVector Query embedding vector.
     * @param int                        $nResults   Maximum number of results (default 10).
     * @param array<string, mixed>|null  $where      ChromaDB where filter (optional).
     *                                               Simple equality: ['key' => 'value']
     *                                               Operators: ['key' => ['$eq' => 'value']]
     * @return array<int, mixed> Search results with ids, distances, and metadatas.
     */
    public function search(
        array $queryVector,
        int $nResults = 10,
        ?array $where = null,
    ): array {
        try {
            $id = $this->resolveCollectionId();
        } catch (Exception) {
            return [];
        }

        $url = $this->collectionEndpointUrl($id, 'query');

        $body = json_encode([
            'query_embeddings' => [$queryVector],
            'n_results' => $nResults,
            'where' => $where ?? null,
            'include' => ['documents', 'metadatas', 'distances'],
        ]);
        if ($body === false) {
            Log::error('VectorDBService: failed to encode search request', ['scope' => ['ai']]);

            return [];
        }

        $response = $this->send($url, $body, 'POST');
        if (!is_array($response) || isset($response['error'])) {
            return [];
        }

        // Chroma returns results per query embedding in arrays.
        // Flatten the first (and only) query result set.
        $ids = $response['ids'][0] ?? [];
        $distances = $response['distances'][0] ?? [];
        $metadatas = $response['metadatas'][0] ?? [];
        $documents = $response['documents'][0] ?? [];

        if (empty($ids)) {
            return [];
        }

        $results = [];
        $count = count($ids);
        for ($i = 0; $i < $count; $i++) {
            $results[] = [
                'id' => $ids[$i] ?? null,
                'distance' => $distances[$i] ?? null,
                'metadata' => $metadatas[$i] ?? [],
                'document' => $documents[$i] ?? null,
            ];
        }

        return $results;
    }

    /**
     * HTTP request helper. Sends a JSON payload and returns the decoded response.
     *
     * @param string $url    Target URL.
     * @param string $body   JSON-encoded request body (empty string for GET/DELETE).
     * @param non-empty-string $method HTTP method.
     * @param array<int, int> $okCodes HTTP status codes >= 400 that should NOT be treated
     *                                 as errors (no error log, response still decoded).
     * @return mixed|null Decoded JSON response, or null on failure.
     */
    private function send(string $url, string $body, string $method, array $okCodes = []): mixed
    {
        $ch = curl_init($url);
        if ($ch === false) {
            Log::error('VectorDBService: failed to initialize cURL', ['scope' => ['ai']]);

            return null;
        }

        $headers = [];
        if ($body !== '') {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            Log::error(sprintf(
                'VectorDBService: cURL failed [errno %d] to %s: %s',
                $curlErrno,
                $url,
                $curlError,
            ), ['scope' => ['ai']]);

            return null;
        }

        if ($httpCode >= 400 && !in_array($httpCode, $okCodes, true)) {
            Log::error(sprintf(
                'VectorDBService: HTTP error [%d] from %s: %s',
                $httpCode,
                $url,
                mb_substr((string)$response, 0, 500),
            ), ['scope' => ['ai']]);

            return null;
        }

        // Empty response is okay for DELETE operations.
        if (trim((string)$response) === '') {
            return [];
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded)) {
            Log::error(sprintf(
                'VectorDBService: invalid JSON response [HTTP %d] from %s',
                $httpCode,
                $url,
            ), ['scope' => ['ai']]);

            return null;
        }

        return $decoded;
    }
}
