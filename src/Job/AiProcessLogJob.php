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
use Cake\Queue\Job\Message;
use Cake\Utility\Text;
use Exception;
use Interop\Queue\Processor;

class AiProcessLogJob implements JobInterface
{
    /**
     * Processes the AI log analysis request from the queue.
     *
     * Reads user_id, entity (log data), and job_id from the message body,
     * calls AIAssistant::getResponse() with a custom system prompt designed
     * for project intelligence analysis, and saves the result to logs_analysis table.
     *
     * @param \Cake\Queue\Job\Message $message Queue message.
     * @return string|null Processor::ACK on success, Processor::REJECT on permanent failure.
     */
    public function execute(Message $message): ?string
    {
        $userId = (string)$message->getArgument('user_id', '');
        $entity = $message->getArgument('entity');
        $jobId = (string)$message->getArgument('job_id', '');

        if ($userId === '' || $entity === null || $jobId === '') {
            return Processor::REJECT;
        }

        try {
            /** @var \App\Model\Entity\User $user */
            $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        } catch (Exception $e) {
            return Processor::REJECT;
        }

        $user->setAuthorization(new AuthorizationService(new OrmResolver()));

        // Convert entity to a readable text representation
        $entityText = is_object($entity) && method_exists($entity, '__toString')
            ? (string)$entity
            : print_r($entity, true);

        // Call AI API directly to avoid tool-calling/routing system interference
        try {
            $response = $this->analyzeWithAI($user, $entityText);

            // response is a string containing JSON
            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($responseData)) {
                // JSON decode failed - reject and do not retry
                return Processor::REJECT;
            }

            // Save response to logs_analysis table
            $logsAnalysisTable = TableRegistry::getTableLocator()->get('LogsAnalysis');

            // Derive event_id from the entity or generate a UUID as fallback.
            $eventId = is_object($entity) && $entity instanceof EntityInterface
                ? (string)$entity->get('id')
                : ($entity['id'] ?? null); // plain array
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
                return Processor::REJECT;
            }

            // Embed and index the analysis summary in Qdrant for semantic search.
            $this->storeInQdrant($entity, $logsAnalysis, $responseData);

            return Processor::ACK;
        } catch (Exception $e) {
            return Processor::REJECT;
        }
    }

    /**
     * Call the AI API directly (bypassing tool-calling/routing) to analyze a log entity.
     *
     * @param \App\Model\Entity\User $user The user whose AI config to use.
     * @param string $entityText Text representation of the entity.
     * @return string Raw AI response content (expected to be JSON).
     */
    private function analyzeWithAI(User $user, string $entityText): string
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
            return '';
        }

        $ch = curl_init($aiConfig['url']);
        if ($ch === false) {
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
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
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
