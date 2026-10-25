<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * AI client of the periodic knowledge base update.
 *
 * Uses the system-wide settings `Projects.KnowledgeBase.ai` (provider `local` or `openai`, url, model, api_key,
 * timeout), not the settings of a user, because the update runs without one. Transient failures are retried.
 */
class KbAiClient implements KbAiClientInterface
{
    private const DEFAULT_LOCAL_URL = 'http://192.168.68.58:8080/v1/chat/completions';
    private const OPENAI_URL = 'https://api.openai.com/v1/chat/completions';

    /** Pause in seconds before each retry. */
    private const RETRY_DELAYS = [1, 2, 4];

    /**
     * @param array<string, mixed>|null $config Settings, `Projects.KnowledgeBase.ai` when empty.
     * @param array<int, int> $retryDelays Pause in seconds before each retry.
     */
    public function __construct(private ?array $config = null, private array $retryDelays = self::RETRY_DELAYS)
    {
    }

    /**
     * @inheritDoc
     */
    public function json(string $system, string $user): ?array
    {
        $settings = $this->settings();
        $payload = (string)json_encode([
            'model' => $settings['model'],
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ]);

        $error = '';
        foreach ([0, ...$this->retryDelays] as $delay) {
            if ($delay > 0) {
                sleep($delay);
            }

            $content = $this->request($settings, $payload, $error);
            if ($content === null) {
                continue;
            }

            return self::extractJson($content);
        }

        Log::error('Knowledge base AI request failed: ' . $error, ['scope' => 'ai']);

        throw new KbAiException($error);
    }

    /**
     * The JSON object in the answer of a model, which may wrap it in a code fence or add text around it.
     *
     * @param string $content Answer of the model.
     * @return array<string, mixed>|null
     */
    public static function extractJson(string $content): ?array
    {
        $content = trim($content);
        $candidates = [$content];
        if (preg_match('/```(?:json)?\s*(.*?)```/s', $content, $match)) {
            $candidates[] = trim($match[1]);
        }
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidates[] = substr($content, $start, $end - $start + 1);
        }

        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);
            if (is_array($decoded) && !array_is_list($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return array{url: string, model: string, api_key: string, timeout: int}
     */
    private function settings(): array
    {
        $config = $this->config ?? (array)Configure::read('Projects.KnowledgeBase.ai');
        $openai = ($config['provider'] ?? 'local') === 'openai';

        return [
            'url' => $openai
                ? self::OPENAI_URL
                : (string)($config['url'] ?? '' ?: self::DEFAULT_LOCAL_URL),
            'model' => (string)($config['model'] ?? '' ?: ($openai ? 'gpt-5-mini' : 'qwen')),
            'api_key' => (string)($config['api_key'] ?? ''),
            'timeout' => (int)($config['timeout'] ?? 0 ?: 180),
        ];
    }

    /**
     * One request.
     *
     * @param array{url: string, model: string, api_key: string, timeout: int} $settings Settings.
     * @param string $payload JSON request.
     * @param string $error Output: what went wrong.
     * @return string|null The text of the answer, null on failure.
     */
    private function request(array $settings, string $payload, string &$error): ?string
    {
        $ch = curl_init($settings['url']);
        if ($ch === false) {
            $error = 'curl_init failed';

            return null;
        }

        $headers = ['Content-Type: application/json'];
        if ($settings['api_key'] !== '') {
            $headers[] = 'Authorization: Bearer ' . $settings['api_key'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $settings['timeout'],
        ]);

        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            $error = 'Connection failed: ' . $curlError;

            return null;
        }
        if ($code !== 200) {
            $error = sprintf('HTTP %d: %s', $code, mb_substr(trim((string)$response), 0, 300));

            return null;
        }

        $decoded = json_decode((string)$response, true);
        $message = $decoded['choices'][0]['message'] ?? [];
        $content = trim((string)($message['content'] ?? '' ?: ($message['reasoning_content'] ?? '')));
        if ($content === '') {
            $error = 'The answer has no content: ' . mb_substr(trim((string)$response), 0, 300);

            return null;
        }

        return $content;
    }
}
