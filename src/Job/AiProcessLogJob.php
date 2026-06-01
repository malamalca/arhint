<?php
declare(strict_types=1);

namespace App\Job;

use App\Lib\EmbeddingService;
use App\Lib\QdrantService;
use App\Model\Entity\User;
use Authorization\AuthorizationService;
use Authorization\Policy\OrmResolver;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\JobInterface;
use Cake\Log\Log;
use Cake\Queue\Job\Message;
use Cake\Utility\Text;
use Exception;
use Interop\Queue\Processor;

class AiProcessLogJob implements JobInterface
{
    /** Max retries for transient AI failures (empty response, invalid JSON, HTTP errors). */
    private const MAX_RETRIES = 3;

    /** Backoff delay in seconds between retries: [1, 2, 4]. */
    private const RETRY_DELAYS = [1, 2, 4];

    /**
     * Processes the AI log analysis request from the queue.
     *
     * Reads user_id, entity (log data), and job_id from the message body,
     * calls AIAssistant::getResponse() with a custom system prompt designed
     * for project intelligence analysis, and saves the result to logs_analysis table.
     *
     * @param \Cake\Queue\Job\Message $message Queue message.
     * @return string|null Processor::ACK on success, REQUEUE on transient failure, REJECT on permanent failure.
     */
    public function execute(Message $message): ?string
    {
        $userId = (string)$message->getArgument('user_id', '');
        $entity = $message->getArgument('entity');
        $jobId = (string)$message->getArgument('job_id', '');

        if ($userId === '' || $entity === false || $entity === null || $jobId === '') {
            Log::warning('AiProcessLogJob: invalid input', [
                'job_id' => $jobId,
                'user_id' => $userId,
                'entity_type' => is_object($entity) ? get_class($entity) : gettype($entity),
                'entity_is_null' => $entity === null,
                'entity_is_false' => $entity === false,
            ], 'ai');
            return Processor::REJECT;
        }

        try {
            /** @var \App\Model\Entity\User $user */
            $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        } catch (Exception $e) {
            Log::error('AiProcessLogJob: user lookup failed', [
                'job_id' => $jobId,
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ], 'ai');
            return Processor::REJECT;
        }

        $user->setAuthorization(new AuthorizationService(new OrmResolver()));

        // Convert entity to a readable text representation
        $entityText = is_object($entity) && method_exists($entity, '__toString')
            ? (string)$entity
            : print_r($entity, true);

        // Call AI API directly with retry logic for transient failures
        try {
            $responseData = $this->analyzeWithAI($user, $entityText, $jobId);

            // analyzeWithAI returns decoded JSON array on success, or null after all retries exhausted
            if ($responseData === null) {
                // Transient failure — requeue for later retry
                return Processor::REQUEUE;
            }

            // Save response to logs_analysis table
            $logsAnalysisTable = TableRegistry::getTableLocator()->get('LogsAnalysis');

            // Derive event_id from the entity or generate a UUID as fallback.
            $eventId = null;
            if ($entity instanceof EntityInterface) {
                $eventId = (string)$entity->get('id');
            } elseif (is_array($entity)) {
                $eventId = $entity['id'] ?? null;
            }
            if (empty($eventId)) {
                $eventId = (string)Text::uuid();
            }

            // Normalize AI string priority to integer.
            $priority = null;
            if (!empty($responseData['priority'])) {
                $norm = strtolower((string)$responseData['priority']);
                $priority = match ($norm) {
                    'high', 'urgent', 'critical' => 1,
                    'medium' => 2,
                    'low' => 3,
                    default => is_numeric($responseData['priority']) ? (int)$responseData['priority'] : null,
                };
            }

            $analysisData = [
                'event_id' => $eventId,
                'summary' => $responseData['summary'] ?? null,
                'risks' => isset($responseData['risks'])
                    ? json_encode($responseData['risks'], JSON_THROW_ON_ERROR)
                    : null,
                'blockers' => isset($responseData['blockers'])
                    ? json_encode($responseData['blockers'], JSON_THROW_ON_ERROR)
                    : null,
                'priority' => $priority,
            ];

            $logsAnalysis = $logsAnalysisTable->newEntity($analysisData);
            if (!$logsAnalysisTable->save($logsAnalysis)) {
                Log::error('AiProcessLogJob: failed to save LogsAnalysis', [
                    'job_id' => $jobId,
                    'user_id' => $userId,
                    'errors' => $logsAnalysis->getErrors(),
                ], 'ai');
                return Processor::REJECT;
            }

            // Embed and index the analysis summary in Qdrant for semantic search.
            $this->storeInQdrant($entity, $logsAnalysis, $responseData);

            return Processor::ACK;
        } catch (Exception $e) {
            Log::error('AiProcessLogJob: execution failed', [
                'job_id' => $jobId,
                'user_id' => $userId,
                'entity_type' => is_object($entity) ? get_class($entity) : gettype($entity),
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ], 'ai');
            return Processor::REJECT;
        }
    }

    /**
     * Call the AI API directly (bypassing tool-calling/routing) to analyze a log entity.
     *
     * Retries up to MAX_RETRIES times with exponential backoff for transient failures
     * (empty response, HTTP errors, invalid JSON from server).
     *
     * @param \App\Model\Entity\User $user The user whose AI config to use.
     * @param string $entityText Text representation of the entity.
     * @param string $jobId Job identifier for logging.
     * @return array<string, mixed>|null Decoded JSON response on success, null after all retries exhausted.
     */
    private function analyzeWithAI(User $user, string $entityText, string $jobId = ''): ?array
    {
        $aiConfig = $this->getAiConfig($user);
        $messages = [
            ['role' => 'system', 'content' => <<<TXT
You are a project intelligence system.

Return ONLY valid JSON:

{
  "summary": "",
  "risks": [],
  "blockers": [],
  "next_steps": [],
  "priority": "",
  "sentiment": ""
}

Event:
$entityText
TXT],
            ['role' => 'user', 'content' => 'Analyze this event and provide intelligence.'],
        ];

        $payload = json_encode([
            'model' => $aiConfig['model'],
            'messages' => $messages,
            'max_tokens' => 2048,
        ]);
        if ($payload === false) {
            return null;
        }

        $lastHttpCode = 0;
        $lastError = '';
        $lastResponse = '';

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                $delay = self::RETRY_DELAYS[$attempt - 1] ?? self::RETRY_DELAYS[count(self::RETRY_DELAYS) - 1];
                Log::warning('AiProcessLogJob: retrying AI call', [
                    'job_id' => $jobId,
                    'attempt' => $attempt,
                    'delay_seconds' => $delay,
                    'last_http_code' => $lastHttpCode,
                    'last_error' => $lastError,
                    'last_response_preview' => mb_substr($lastResponse, 0, 200),
                ], 'ai');
                sleep($delay);
            }

            $raw = $this->callAiApi($aiConfig, $payload, $lastHttpCode, $lastError);

            // Empty response — transient failure (HTTP error, connection issue)
            if ($raw === '') {
                continue;
            }

            $lastResponse = $raw;

            // Try to decode as JSON
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            // Got content but not valid JSON — log and retry
            Log::warning('AiProcessLogJob: AI returned non-JSON content', [
                'job_id' => $jobId,
                'attempt' => $attempt + 1,
                'json_error' => json_last_error_msg(),
                'response_length' => strlen($raw),
                'response_preview' => mb_substr($raw, 0, 300),
            ], 'ai');
        }

        Log::error('AiProcessLogJob: AI call failed after all retries', [
            'job_id' => $jobId,
            'total_attempts' => self::MAX_RETRIES + 1,
            'last_http_code' => $lastHttpCode,
            'last_error' => $lastError,
            'last_response_preview' => mb_substr($lastResponse, 0, 300),
        ], 'ai');

        return null;
    }

    /**
     * Single HTTP call to the AI API.
     *
     * @param array{url: string, model: string, api_key: string} $aiConfig AI config.
     * @param string $payload JSON-encoded request payload.
     * @param int $lastHttpCode Output: last HTTP status code.
     * @param string $lastError Output: last cURL error message.
     * @return string Raw AI response content, or empty string on failure.
     */
    private function callAiApi(
        array $aiConfig,
        string $payload,
        int &$lastHttpCode = 0,
        string &$lastError = '',
    ): string {
        $ch = curl_init($aiConfig['url']);
        if ($ch === false) {
            $lastError = 'curl_init failed';
            return '';
        }

        $headers = ['Content-Type: application/json'];
        if ($aiConfig['api_key'] !== '') {
            $headers[] = "Authorization: Bearer {$aiConfig['api_key']}";
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($ch);
        $lastHttpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $lastError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $lastHttpCode !== 200) {
            return '';
        }

        $decoded = json_decode((string)$response, true);
        if (isset($decoded['choices'][0]['message']['content'])) {
            return trim((string)$decoded['choices'][0]['message']['content']);
        }

        // Fallback to reasoning_content for models like Qwen reasoning variants
        if (isset($decoded['choices'][0]['message']['reasoning_content'])) {
            return trim((string)$decoded['choices'][0]['message']['reasoning_content']);
        }

        return '';
    }

    /**
     * Get AI API configuration from user settings.
     *
     * @param \App\Model\Entity\User $user The user to get config from.
     * @return array{url: string, model: string, api_key: string}
     */
    private function getAiConfig(User $user): array
    {
        $config = $user->get('ai_assistant');
        if (is_object($config)) {
            /** @var object{url: string, model: string, api_key: string} $config */
            return [
                'url' => (string)($config->url ?: 'http://192.168.68.58:8080/v1/chat/completions'),
                'model' => (string)($config->model ?: 'qwen'),
                'api_key' => (string)($config->{'api_key'} ?? ''),
            ];
        }

        return [
            'url' => 'http://192.168.68.58:8080/v1/chat/completions',
            'model' => 'qwen',
            'api_key' => '',
        ];
    }

    /**
     * Embed the analysis summary and upsert it into Qdrant for vector search.
     *
     * This step is best-effort: a failure here does not cause the whole job to
     * fail — only logs an error so the main ACK result is preserved.
     *
     * @param mixed                         $entity       The original log entity.
     * @param \Cake\Datasource\EntityInterface $logsAnalysis Saved analysis record.
     * @param array<string, mixed>          $responseData Decoded AI response data.
     * @return void
     */
    private function storeInQdrant(mixed $entity, EntityInterface $logsAnalysis, array $responseData): void
    {
        // Best-effort: skip silently if services are not configured.
        try {
            $embeddingService = new EmbeddingService();
            $qdrant = new QdrantService();
        } catch (Exception) {
            return;
        }

        $summary = (string)($responseData['summary'] ?? '');
        if ($summary === '') {
            return;
        }

        try {
            $vector = $embeddingService->embed($summary);
        } catch (Exception) {
            return;
        }

        $logModel = null;
        $logForeignId = null;
        $logUserId = null;
        $logAction = null;
        if ($entity instanceof EntityInterface) {
            $logModel = (string)$entity->get('model');
            $logForeignId = (string)$entity->get('foreign_id');
            $logUserId = (string)$entity->get('user_id');
            $logAction = (string)$entity->get('action');
        } elseif (is_array($entity)) {
            $logModel = (string)($entity['model'] ?? '');
            $logForeignId = (string)($entity['foreign_id'] ?? '');
            $logUserId = (string)($entity['user_id'] ?? '');
            $logAction = (string)($entity['action'] ?? '');
        }

        $payload = [
            'log_id' => (string)$logsAnalysis->get('event_id'),
            'log_model' => $logModel,
            'log_foreign_id' => $logForeignId,
            'log_user_id' => $logUserId,
            'log_action' => $logAction,
            'summary' => $summary,
            'priority' => (int)$logsAnalysis->get('priority') ?: null,
        ];

        // Optional: filter by related entity model / project during search.
        if ($logModel !== '') {
            $payload['model'] = $logModel;
        }

        try {
            $qdrant->upsert((string)$logsAnalysis->get('id'), $vector, $payload);
        } catch (Exception) {
            // Logged inside QdrantService.
        }
    }
}
