<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use Exception;

/**
 * Service for generating text embeddings via an external API.
 *
 * Configuration is read from Configure::read('Embedding') with keys:
 * - url (string)   The embedding API endpoint, e.g. "http://localhost:8001/embed"
 * - timeout (int)  cURL timeout in seconds (default 30).
 */
class EmbeddingService
{
    /**
     * @var string API endpoint URL.
     */
    private string $url;

    /**
     * @var int cURL timeout in seconds.
     */
    private int $timeout;

    /**
     * Constructor.
     *
     * Reads embedding configuration from CakePHP Configure.
     *
     * @throws \Exception If the 'Embedding.url' config key is missing or empty.
     */
    public function __construct()
    {
        $url = (string)Configure::read('Embedding.url', '');
        if ($url === '') {
            throw new Exception('Embedding service not configured. Set Embedding.url in your app configuration.');
        }
        $this->url = $url;

        $this->timeout = (int)Configure::read('Embedding.timeout', 30);
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

        $payload = json_encode(['text' => $text]);
        if ($payload === false) {
            throw new Exception('Failed to encode request data: ' . json_last_error_msg());
        }

        $ch = curl_init($this->url);
        if ($ch === false) {
            throw new Exception('Failed to initialize cURL for: ' . $this->url);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

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
