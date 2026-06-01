<?php
declare(strict_types=1);

namespace App\Lib;

use App\Model\Entity\User;
use Exception;

/**
 * Semantic intelligence tool that queries Qdrant for project log summaries,
 * feeds them to an LLM for analysis, and returns a synthesized answer.
 */
class QdrantSearchTool
{
    /**
     * Constructor.
     *
     * @param \App\Model\Entity\User|null $currentUser Current user
     */
    public function __construct(
        private readonly ?User $currentUser = null,
    ) {
    }

    /**
     * Search Qdrant for semantic matches and synthesize an intelligent answer.
     *
     * @param string $query Natural language question (e.g. "What is going on with the project?").
     * @param array<string, mixed> $filter Optional Qdrant payload filter (e.g. ['must' => [['key' => 'log_foreign_id', 'match' => ['value' => 'project-uuid']]]]).
     * @param int $limit Maximum number of log entries to retrieve from Qdrant.
     * @return string Synthesized answer or error message.
     */
    public function searchAndAnalyze(string $query, array $filter = [], int $limit = 5): string
    {
        try {
            $embeddingService = new EmbeddingService();
            $qdrantService = new QdrantService();
        } catch (Exception $e) {
            return "Intelligence search unavailable: {$e->getMessage()}";
        }

        // 1. Embed the user's query
        try {
            $vector = $embeddingService->embed($query);
        } catch (Exception) {
            return 'Failed to process your question. Please try rephrasing.';
        }

        if ($vector === []) {
            return 'Please provide a meaningful question to search for insights.';
        }

        // 2. Query Qdrant with semantic similarity + optional filters
        $results = $qdrantService->search($vector, $filter, $limit);
        if (empty($results)) {
            return 'No relevant intelligence found for this query in the project logs.';
        }

        // 3. Extract and format payloads for LLM context
        $contextEntries = [];
        foreach ($results as $point) {
            $payload = $point['payload'] ?? [];
            $summary = (string)($payload['summary'] ?? '');
            if ($summary === '') {
                continue;
            }

            $action = (string)($payload['log_action'] ?? 'unknown');
            $priorityLabel = match ((int)($payload['priority'] ?? 0)) {
                1 => 'High',
                2 => 'Medium',
                3 => 'Low',
                default => 'N/A',
            };

            $contextEntries[] = "• [$action | Priority: $priorityLabel] $summary";
        }

        if ($contextEntries === []) {
            return 'Found log entries but no actionable summaries were available.';
        }

        $context = implode("\n", $contextEntries);

        // 4. Synthesize answer using LLM (direct API call to avoid tool-calling recursion)
        return $this->synthesizeAnswer($query, $context);
    }

    /**
     * Call the AI API directly to synthesize an answer from the Qdrant context.
     */
    private function synthesizeAnswer(string $userQuestion, string $qdrantContext): string
    {
        $aiUrl = $this->getAiApiUrl();
        $model = $this->getAiModel();
        $apiKey = (string)($this->currentUser?->get('ai_assistant.api_key') ?? '');

        $messages = [
            ['role' => 'system', 'content' => 'You are a project intelligence analyst. '
                . "Answer the user's question using ONLY the provided log context. "
                . 'Be concise, factual, and highlight risks or blockers if present.'],
            ['role' => 'user', 'content' => "Question: $userQuestion\n\nLog Context:\n$qdrantContext"],
        ];

        $payload = json_encode([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => 1024,
        ]);

        if ($payload === false) {
            return 'Failed to prepare intelligence query.';
        }

        $ch = curl_init($aiUrl);
        if ($ch === false) {
            return 'Failed to initialize connection to AI service.';
        }

        $headers = ['Content-Type: application/json'];
        if ($apiKey !== '') {
            $headers[] = "Authorization: Bearer $apiKey";
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false || $httpCode !== 200) {
            return "Failed to get intelligence analysis from AI service (HTTP $httpCode: $curlError).";
        }

        $decoded = json_decode((string)$response, true);
        $message = $decoded['choices'][0]['message'] ?? null;
        $content = trim((string)($message['content'] ?? ''));

        // Fallback to reasoning_content for models like Qwen reasoning variants
        if ($content === '' && !empty($message['reasoning_content'])) {
            $content = trim((string)$message['reasoning_content']);
        }

        return $content !== '' ? $content : "AI returned an empty analysis. Context:\n$qdrantContext";
    }

    /**
     * Get the AI API URL from user config or fallback default.
     *
     * @return string AI API URL
     */
    private function getAiApiUrl(): string
    {
        $config = $this->currentUser?->get('ai_assistant');
        if ($config) {
            return (string)$config->url ?: 'http://192.168.68.58:8080/v1/chat/completions';
        }

        return 'http://192.168.68.58:8080/v1/chat/completions';
    }

    /**
     * Get the AI model name from user config or fallback default.
     *
     * @return string AI model name
     */
    private function getAiModel(): string
    {
        $config = $this->currentUser?->get('ai_assistant');
        if ($config) {
            return (string)$config->model ?: 'qwen';
        }

        return 'qwen';
    }
}
