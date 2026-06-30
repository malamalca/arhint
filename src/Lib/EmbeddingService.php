<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use Exception;

/**
 * Service for generating text embeddings via an external API.
 *
 * Supports two providers, selected via Configure::read('Embedding.provider'):
 *
 * - "local" (default): a custom JSON endpoint that accepts {"text": ...} and
 *   returns a vector. Reads `url` and `timeout`.
 * - "openai": the OpenAI-compatible embeddings API. Sends {"input": ..., "model": ...}
 *   with a Bearer token. Reads `url` (defaults to the OpenAI endpoint), `model`,
 *   `api_key` and `timeout`.
 *
 * Configuration keys (Configure::read('Embedding')):
 * - provider (string) "local" | "openai" (default "local").
 * - url (string)      The embedding API endpoint. Required for "local"; for
 *                     "openai" defaults to "https://api.openai.com/v1/embeddings".
 * - model (string)    Model name (openai only, default "text-embedding-3-small").
 * - api_key (string)  Bearer token (openai only).
 * - timeout (int)     cURL timeout in seconds (default 30).
 */
class EmbeddingService
{
    /**
     * @var string Provider identifier ("local" or "openai").
     */
    private string $provider;

    /**
     * @var string API endpoint URL.
     */
    private string $url;

    /**
     * @var string Model name (openai provider only).
     */
    private string $model;

    /**
     * @var string Bearer API key (openai provider only).
     */
    private string $apiKey;

    /**
     * @var int cURL timeout in seconds.
     */
    private int $timeout;

    /**
     * Constructor.
     *
     * Reads embedding configuration from CakePHP Configure.
     *
     * @throws \Exception If the configured provider is unknown or required keys are missing.
     */
    public function __construct()
    {
        $this->provider = strtolower((string)Configure::read('Embedding.provider', 'local'));
        $this->timeout = (int)Configure::read('Embedding.timeout', 30);
        $this->model = (string)Configure::read('Embedding.model', 'text-embedding-3-small');
        $this->apiKey = (string)Configure::read('Embedding.api_key', '');

        if ($this->provider === 'openai') {
            $this->url = (string)Configure::read('Embedding.url', 'https://api.openai.com/v1/embeddings');
            if ($this->apiKey === '') {
                throw new Exception(
                    'OpenAI embedding provider requires an API key. Set Embedding.api_key in your app configuration.',
                );
            }
        } elseif ($this->provider === 'local') {
            $url = (string)Configure::read('Embedding.url', '');
            if ($url === '') {
                throw new Exception('Embedding service not configured. Set Embedding.url in your app configuration.');
            }
            $this->url = $url;
        } else {
            throw new Exception(sprintf(
                'Unknown embedding provider "%s". Set Embedding.provider to "local" or "openai".',
                $this->provider,
            ));
        }
    }

    /**
     * Generate an embedding vector for the given text.
     *
     * @param string $text The input text to embed.
     * @return array<int, float> The embedding vector as a numeric-indexed array of floats.
     * @throws \Exception If the API call fails or the response cannot be parsed.
     */
    public function embed(string $text): array
    {
        if (trim($text) === '') {
            Log::warning('EmbeddingService: empty text passed to embed()', ['scope' => ['ai']]);

            return [];
        }

        if ($this->provider === 'openai') {
            $data = ['input' => $text, 'model' => $this->model];
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ];
        } else {
            $data = ['text' => $text];
            $headers = ['Content-Type: application/json'];
        }

        $payload = json_encode($data);
        if ($payload === false) {
            throw new Exception('Failed to encode request data: ' . json_last_error_msg());
        }

        $ch = curl_init($this->url);
        if ($ch === false) {
            throw new Exception('Failed to initialize cURL for: ' . $this->url);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new Exception(sprintf(
                'cURL request failed [errno %d] to %s: %s',
                $curlErrno,
                $this->url,
                $curlError,
            ));
        }

        $result = json_decode((string)$response, true);
        if (!is_array($result)) {
            throw new Exception(sprintf(
                'Invalid JSON response [HTTP %d] from %s',
                $httpCode,
                $this->url,
            ));
        }

        // Support both "vector" and top-level array response formats.
        $vector = null;
        if (isset($result['vector']) && is_array($result['vector'])) {
            $vector = $result['vector'];
        } elseif (isset($result['embedding']) && is_array($result['embedding'])) {
            $vector = $result['embedding'];
        } elseif (
            isset($result['data'][0])
            && is_array($result['data'][0])
            && isset($result['data'][0]['embedding'])
        ) {
            $vector = $result['data'][0]['embedding'];
        }

        if (!is_array($vector)) {
            throw new Exception(sprintf(
                'No vector found in response [HTTP %d] from %s',
                $httpCode,
                $this->url,
            ));
        }

        Log::debug(sprintf(
            'EmbeddingService: generated %d-dim vector for text length %d',
            count($vector),
            mb_strlen($text),
        ), ['scope' => ['ai']]);

        return array_map('floatval', $vector);
    }
}
