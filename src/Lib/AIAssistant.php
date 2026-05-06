<?php
declare(strict_types=1);

namespace App\Lib;

use App\Model\Entity\User;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Log\Log;
use Exception;
use stdClass;

class AIAssistant
{
    private const HISTORY_SUMMARY_PREFIX = 'Conversation summary: ';
    private const MAX_TOOL_CALLS = 3;
    private const MAX_TOOLS_PER_REQUEST = 8;
    private const MAX_HISTORY_MESSAGES = 8;
    private const MAX_HISTORY_SUMMARY_CHARS = 1500;
    private const MAX_MESSAGE_SNIPPET_CHARS = 180;
    private const MAX_TOOL_DESCRIPTION_CHARS = 220;
    private const MAX_TOOL_ARGUMENT_DESCRIPTION_CHARS = 80;
    private const MAX_TOOL_RESULT_ITEMS = 3;
    private const MAX_PROMPT_TOOL_RESULT_ITEMS = 20;
    private const MAX_TOOL_RESULT_CHARS = 800;

    public const SYSTEM_PROMPT = "You are an assistant for a business management system.\n" .
        "Proceed with great speed and accuracy.\n" .
        "\n" .
        "You can use tools. When needed, respond ONLY in plain JSON, without other text, markdown or explanations.\n" .
        "Do not prefix the JSON with any text. The JSON should have the following format:\n" .
        "{\n" .
        "\"tool\": \"tool_name\", \n" .
        "\"arguments\": { ... } \n" .
        "}\n" .
        "\n" .

        "Available data:\n" .
        "- Current date and time: %s\n" .
        "\n" .

        "Available tools:\n %s\n" .

        "When the user asks for help, determine which tool is most appropriate and provide a response.\n" .
        "Always respond with valid JSON when calling a tool. Do not include explanations.\n" .
        "If no tool is needed, answer normally.\n";

    /**
     * List of tools available to the AI assistant. This can be populated by listening to the 'AIAssistant.tools' event.
     *
     * @var array<\App\Lib\AITool>
     */
    private array $tools = [];

    /**
     * Module name => description map, populated by the 'App.AIAssistant.registerModule' event.
     *
     * @var array<string, string>
     */
    private array $moduleDescriptions = [];

    /**
     * @var \App\Model\Entity\User|null The current user, which can be used for personalized responses or permission checks when executing tools.
     */
    private ?User $currentUser;

    /**
     * Conversation history (user, assistant, and tool messages), excluding the system prompt.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $conversationHistory = [];

    /**
     * A redirect URL requested by a tool during the last getResponse() call.
     *
     * @var string|null
     */
    private ?string $redirectUrl = null;

    /**
     * AI provider. 'openai' uses native tool_calls format; anything else uses prompt-based JSON.
     *
     * @var string
     */
    private string $provider = 'local';

    /**
     * Constructor to initialize the AIAssistant with available tools.
     * Tools can be added by listening to the 'AIAssistant.tools' event and modifying the provided tools list.
     */
    public function __construct(?User $currentUser = null)
    {
        $this->currentUser = $currentUser;

        $aiConfig = $currentUser?->getProperty('ai_assistant');
        $this->provider = $aiConfig->provider ?? 'local';

        $modulesList = new ArrayObject();
        $registerEvent = new Event('App.AIAssistant.registerModule', $this, [$modulesList]);
        EventManager::instance()->dispatch($registerEvent);
        $this->moduleDescriptions = $modulesList->getArrayCopy();

        $toolsList = new ArrayObject();

        $event = new Event('App.AIAssistant.tools', $this, [$toolsList]);
        EventManager::instance()->dispatch($event);

        $this->tools = $toolsList->getArrayCopy();
    }

    /**
     * Returns a redirect URL requested by a tool during the last getResponse() call, or null.
     *
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * Clears the conversation history, starting a fresh session.
     */
    public function clearHistory(): void
    {
        $this->conversationHistory = [];
    }

    /**
     * Returns the current conversation history.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(): array
    {
        return $this->conversationHistory;
    }

    /**
     * Replaces the conversation history (e.g. when restoring from session).
     *
     * @param array<int, array<string, mixed>> $history
     */
    public function setHistory(array $history): void
    {
        $this->conversationHistory = $this->normalizeHistory($history);
        $this->compactConversationHistory();
    }

    /**
     * Gets a response from the AI assistant based on user input.
     * Maintains conversation history across calls and supports multiple sequential tool calls.
     *
     * @param string $userInput The user's input or question.
     * @return string The AI assistant's response.
     * @throws \Exception If the API call fails or returns an error.
     */
    public function getResponse(string $userInput): string
    {
        $this->appendConversationMessage(['role' => 'user', 'content' => $userInput]);

        $this->redirectUrl = null;
        $toolCallCount = 0;
        $message = [];

        while (true) {
            $activeTools = $this->selectToolsForRequest($userInput);

            if ($this->provider === 'openai') {
                $data = [
                    'messages' => array_merge(
                        [['role' => 'system', 'content' =>
                            'You are an assistant for a business management system. ' .
                            'Proceed with great speed and accuracy. ' .
                            'Current date and time: ' . date('Y-m-d H:i:s'),
                        ]],
                        $this->conversationHistory,
                    ),
                ];
                if (!empty($activeTools)) {
                    // OpenAI tool names must match ^[a-zA-Z0-9_-]+$, so replace dots with double underscores.
                    // Build a reverse map from sanitized name back to original name.
                    $toolNameMap = [];
                    $data['tools'] = array_map(function ($tool) use (&$toolNameMap) {
                        $safeName = str_replace('.', '__', $tool->name);
                        $toolNameMap[$safeName] = $tool->name;

                        return [
                            'type' => 'function',
                            'function' => [
                                'name' => $safeName,
                                'description' => $this->trimText($tool->description, self::MAX_TOOL_DESCRIPTION_CHARS),
                                'parameters' => [
                                    'type' => 'object',
                                    'properties' => empty($tool->arguments)
                                        ? new stdClass()
                                        : $this->compactToolArguments($tool->arguments),
                                ],
                            ],
                        ];
                    }, $activeTools);
                    $data['tool_choice'] = 'auto';
                }
            } else {
                $promptTools = array_map(
                    fn($tool) => $this->formatPromptTool($tool),
                    $activeTools,
                );
                $data = [
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => sprintf(
                            self::SYSTEM_PROMPT,
                            date('Y-m-d H:i:s'),
                            implode("\n", $promptTools),
                        )]],
                        $this->conversationHistory,
                    ),
                ];
            }

            $message = $this->doRequest($data);

            // OpenAI native tool_calls
            if (
                $this->provider === 'openai'
                && $toolCallCount < self::MAX_TOOL_CALLS
                && !empty($message['tool_calls'])
            ) {
                $this->appendConversationMessage([
                    'role' => 'assistant',
                    'content' => $message['content'],
                    'tool_calls' => $message['tool_calls'],
                ]);

                foreach ($message['tool_calls'] as $toolCall) {
                    $safeName = $toolCall['function']['name'];
                    $tool = $toolNameMap[$safeName] ?? $safeName;
                    $arguments = json_decode($toolCall['function']['arguments'], true) ?? [];

                    $event = new Event('App.AIAssistant.executeTool', $this, [$tool, $arguments, $this->currentUser]);
                    EventManager::instance()->dispatch($event);

                    $result = $event->getResult();
                    if (is_array($result) && isset($result['redirect_url'])) {
                        $this->redirectUrl = $result['redirect_url'];
                    }

                    $this->appendConversationMessage([
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => $this->encodeToolResultForHistory($tool, $result),
                    ]);

                    Log::debug(
                        "Executed tool $tool with arguments " . json_encode($arguments),
                        [
                            'scope' => ['ai'],
                            'tool' => $tool,
                            'arguments' => $arguments,
                            'result' => $result,
                        ],
                    );
                }

                $toolCallCount++;
                continue;
            }

            // Prompt-based tool calls (local provider)
            if ($this->provider !== 'openai') {
                // Strip markdown code fences that some models add despite instructions
                $content = trim($message['content']);
                $content = (string)preg_replace('/^```(?:json)?\s*/i', '', $content);
                $content = (string)preg_replace('/\s*```$/', '', $content);
                $content = trim($content);

                // Some models prefix the JSON with a sentence; extract the JSON object when that happens.
                if (!str_starts_with($content, '{') && preg_match('/(\{[\s\S]+\})/', $content, $fenceMatches)) {
                    $candidate = trim($fenceMatches[1]);
                    $candidateJson = json_decode($candidate, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($candidateJson['tool'])) {
                        $content = $candidate;
                    }
                }

                $message['content'] = $content;

                $json = json_decode($content, true);
                $isToolCall = str_starts_with($content, '{')
                    && json_last_error() === JSON_ERROR_NONE
                    && isset($json['tool']);

                if ($isToolCall && $toolCallCount < self::MAX_TOOL_CALLS) {
                    $tool = $json['tool'];
                    $arguments = $json['arguments'] ?? [];

                    $event = new Event('App.AIAssistant.executeTool', $this, [$tool, $arguments, $this->currentUser]);
                    EventManager::instance()->dispatch($event);

                    $result = $event->getResult();
                    if (is_array($result) && isset($result['redirect_url'])) {
                        $this->redirectUrl = $result['redirect_url'];
                    }

                    $this->appendConversationMessage(['role' => 'assistant', 'content' => $message['content']]);
                    $this->appendConversationMessage([
                        'role' => 'user',
                        'content' => $this->buildToolResultSummaryMessage($tool, $result),
                    ]);

                    Log::debug(
                        "Executed tool $tool with arguments " . json_encode($arguments),
                        [
                            'scope' => ['ai'],
                            'tool' => $tool,
                            'arguments' => $arguments,
                            'result' => $result,
                        ],
                    );

                    $toolCallCount++;
                    continue;
                }

                // Tool call limit reached or unexpected tool JSON — never expose raw JSON to the user.
                if ($isToolCall) {
                    $message['content'] = 'Done.';
                }
            }

            $this->appendConversationMessage(['role' => 'assistant', 'content' => $message['content']]);
            break;
        }

        return $message['content'];
    }

    /**
     * Normalize externally provided history entries into an internal message format.
     *
     * @param array<int, array<string, mixed>> $history
     * @return array<int, array<string, mixed>>
     */
    private function normalizeHistory(array $history): array
    {
        $normalized = [];

        foreach ($history as $message) {
            if (!is_array($message) || empty($message['role'])) {
                continue;
            }
            $normalizedMessage = [
                'role' => (string)$message['role'],
                'content' => (string)($message['content'] ?? ''),
            ];
            if (isset($message['tool_call_id'])) {
                $normalizedMessage['tool_call_id'] = (string)$message['tool_call_id'];
            }
            if (isset($message['tool_calls']) && is_array($message['tool_calls'])) {
                $normalizedMessage['tool_calls'] = $message['tool_calls'];
            }
            $normalized[] = $normalizedMessage;
        }

        return $normalized;
    }

    /**
     * Append one message and immediately enforce history compaction limits.
     *
     * @param array<string, mixed> $message
     * @return void
     */
    private function appendConversationMessage(array $message): void
    {
        $this->conversationHistory[] = $message;
        $this->compactConversationHistory();
    }

    /**
     * Collapse older conversation entries into a rolling summary message.
     *
     * @return void
     */
    private function compactConversationHistory(): void
    {
        $summary = null;
        $messages = [];

        foreach ($this->conversationHistory as $message) {
            $content = (string)($message['content'] ?? '');
            if (($message['role'] ?? '') === 'assistant' && str_starts_with($content, self::HISTORY_SUMMARY_PREFIX)) {
                $summary = trim(substr($content, strlen(self::HISTORY_SUMMARY_PREFIX)));
                continue;
            }
            $messages[] = $message;
        }

        if (count($messages) <= self::MAX_HISTORY_MESSAGES) {
            $this->conversationHistory = $summary !== null && $summary !== ''
                ? array_merge(
                    [['role' => 'assistant', 'content' => self::HISTORY_SUMMARY_PREFIX . $summary]],
                    $messages,
                )
                : $messages;

            return;
        }

        $overflowCount = count($messages) - self::MAX_HISTORY_MESSAGES;
        $overflowMessages = array_slice($messages, 0, $overflowCount);
        $recentMessages = array_slice($messages, -self::MAX_HISTORY_MESSAGES);

        $summaryParts = array_filter([$summary, $this->summarizeMessages($overflowMessages)]);
        $summaryText = $this->trimText(implode(' | ', $summaryParts), self::MAX_HISTORY_SUMMARY_CHARS);

        $this->conversationHistory = array_merge(
            [['role' => 'assistant', 'content' => self::HISTORY_SUMMARY_PREFIX . $summaryText]],
            $recentMessages,
        );
    }

    /**
     * Build a compact textual summary from a list of conversation messages.
     *
     * @param array<int, array<string, mixed>> $messages
     * @return string
     */
    private function summarizeMessages(array $messages): string
    {
        $parts = [];

        foreach ($messages as $message) {
            $role = (string)($message['role'] ?? 'assistant');
            if ($role === 'assistant' && !empty($message['tool_calls']) && is_array($message['tool_calls'])) {
                $toolNames = array_map(
                    fn($toolCall) => (string)($toolCall['function']['name'] ?? 'tool'),
                    $message['tool_calls'],
                );
                $parts[] = 'Assistant requested ' . implode(', ', $toolNames);

                continue;
            }

            $prefix = match ($role) {
                'user' => 'User',
                'tool' => 'Tool',
                default => 'Assistant',
            };
            $content = $this->trimText((string)($message['content'] ?? ''), self::MAX_MESSAGE_SNIPPET_CHARS);
            if ($content === '') {
                continue;
            }
            $parts[] = $prefix . ': ' . $content;
        }

        return implode(' | ', $parts);
    }

    /**
     * Pick the most relevant tool subset for the current user request.
     *
     * @param string $userInput Latest user input.
     * @return array<int, \App\Lib\AITool>
     */
    private function selectToolsForRequest(string $userInput): array
    {
        if (count($this->tools) <= self::MAX_TOOLS_PER_REQUEST) {
            return $this->tools;
        }

        // Group tools by their module prefix (e.g. "Projects", "Crm").
        $moduleGroups = [];
        foreach ($this->tools as $tool) {
            $module = strtok($tool->name, '.') ?: 'misc';
            $moduleGroups[$module][] = $tool;
        }

        $recentModule = $this->getRecentToolModule();
        $context = trim($userInput . ' ' . $this->getRecentConversationText());

        // Ask the AI which module(s) best match the request; fall back to keyword scoring on failure.
        // Use only the current user input for module detection to avoid old questions from history
        // biasing the routing decision.
        $detectedModules = $this->detectModulesViaAI(array_keys($moduleGroups), $this->moduleDescriptions, $userInput);

        if ($detectedModules !== []) {
            // Prefer the module used most recently in conversation if it is among the detected ones.
            if ($recentModule !== null && in_array($recentModule, $detectedModules, true)) {
                array_unshift($detectedModules, $recentModule);
                $detectedModules = array_values(array_unique($detectedModules));
            }

            $selected = [];
            foreach ($detectedModules as $module) {
                foreach ($moduleGroups[$module] ?? [] as $tool) {
                    $selected[] = $tool;
                }
            }

            return $selected;
        }

        // Fallback: keyword scoring when the AI detection call fails.
        $context = mb_strtolower($context);
        $moduleScores = [];
        foreach ($moduleGroups as $module => $moduleTools) {
            $best = 0;
            foreach ($moduleTools as $tool) {
                $score = $this->scoreToolAgainstContext($tool, $context);
                if ($score > $best) {
                    $best = $score;
                }
            }
            if ($recentModule !== null && strcasecmp($module, $recentModule) === 0) {
                $best += 15;
            }
            $moduleScores[$module] = $best;
        }

        arsort($moduleScores);
        $topScore = (int)reset($moduleScores);

        if ($topScore <= 0) {
            return array_slice($this->tools, 0, self::MAX_TOOLS_PER_REQUEST);
        }

        if ($topScore >= 40) {
            $threshold = $topScore - 20;
            $selected = [];
            foreach ($moduleScores as $module => $score) {
                if ($score < $threshold) {
                    break;
                }
                foreach ($moduleGroups[$module] as $tool) {
                    $selected[] = $tool;
                }
            }

            return $selected;
        }

        $scoredTools = [];
        foreach ($this->tools as $index => $tool) {
            $score = $this->scoreToolAgainstContext($tool, $context);
            if ($recentModule !== null && str_starts_with($tool->name, $recentModule . '.')) {
                $score += 15;
            }
            $scoredTools[] = ['tool' => $tool, 'score' => $score, 'index' => $index];
        }

        usort($scoredTools, function (array $left, array $right): int {
            if ($left['score'] === $right['score']) {
                return $left['index'] <=> $right['index'];
            }

            return $right['score'] <=> $left['score'];
        });

        $selected = array_values(array_filter($scoredTools, fn(array $entry): bool => $entry['score'] > 0));

        return array_values(array_map(
            fn(array $entry): AITool => $entry['tool'],
            array_slice($selected, 0, self::MAX_TOOLS_PER_REQUEST),
        ));
    }

    /**
     * Ask the AI to identify which module(s) a request targets.
     *
     * Sends a lightweight stateless prompt (no conversation history) listing the
     * available module names and asks the model to return a JSON array. Returns the
     * matched module names on success, or an empty array if the call fails or the
     * response cannot be parsed.
     *
     * @param array<int, string> $modules All available module names.
     * @param array<string, string> $moduleDescriptions Module name => description map.
     * @param string $context User input plus recent conversation text.
     * @return array<int, string> Matched module names, or [] on failure.
     */
    private function detectModulesViaAI(array $modules, array $moduleDescriptions, string $context): array
    {
        $moduleLines = array_map(function (string $module) use ($moduleDescriptions): string {
            $desc = $moduleDescriptions[$module] ?? '';

            return $desc !== '' ? "- {$module}: {$desc}" : "- {$module}";
        }, $modules);
        $moduleList = implode("\n", $moduleLines);
        $prompt = 'You are a routing assistant. Given a user request, identify which module(s) it belongs to.' . "\n"
            . 'Available modules:' . "\n" . $moduleList . "\n"
            . 'Respond with ONLY a JSON array of matching module names, e.g. ["Projects"] or ["Crm"].' . "\n"
            . 'If nothing matches, respond with [].' . "\n\n"
            . 'User request: ' . $context;

        $data = [
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        try {
            $message = $this->doRequest($data);
        } catch (Exception) {
            return [];
        }

        $content = trim((string)($message['content'] ?? ''));
        // Strip markdown fences some models add.
        $content = (string)preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = (string)preg_replace('/\s*```$/', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [];
        }

        // Keep only values that match a known module name (case-insensitive).
        $modulesLower = array_combine(
            array_map('strtolower', $modules),
            $modules,
        );
        $matched = [];
        foreach ($decoded as $item) {
            $key = strtolower((string)$item);
            if (isset($modulesLower[$key])) {
                $matched[] = $modulesLower[$key];
            }
        }

        return array_values(array_unique($matched));
    }

    /**
     * Collect recent conversation text as additional context for tool selection.
     *
     * @return string
     */
    private function getRecentConversationText(): string
    {
        $recentMessages = array_slice($this->conversationHistory, -4);
        $parts = [];

        foreach ($recentMessages as $message) {
            $content = trim((string)($message['content'] ?? ''));
            if ($content !== '') {
                $parts[] = $content;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Detect the most recent tool module used in conversation history.
     *
     * @return string|null
     */
    private function getRecentToolModule(): ?string
    {
        for ($index = count($this->conversationHistory) - 1; $index >= 0; $index--) {
            $message = $this->conversationHistory[$index];
            $content = (string)($message['content'] ?? '');
            if (!str_starts_with($content, 'Tool result for ')) {
                continue;
            }
            if (preg_match('/^Tool result for ([A-Za-z0-9_.-]+)/', $content, $matches) !== 1) {
                continue;
            }

            return strtok($matches[1], '.') ?: null;
        }

        return null;
    }

    /**
     * Score one tool against request context to rank selection relevance.
     *
     * @param \App\Lib\AITool $tool Tool metadata.
     * @param string $context Request context.
     * @return int
     */
    private function scoreToolAgainstContext(AITool $tool, string $context): int
    {
        if ($context === '') {
            return 0;
        }

        $score = 0;
        $toolName = mb_strtolower(str_replace(['.', '_'], ' ', $tool->name));
        $toolDescription = mb_strtolower($tool->description);

        $module = strtok($tool->name, '.');
        if ($module !== false) {
            $moduleToken = mb_strtolower($module);
            if (str_contains($context, $moduleToken)) {
                $score += 40;
            }
            $singularModule = rtrim($moduleToken, 's');
            if ($singularModule !== $moduleToken && str_contains($context, $singularModule)) {
                $score += 20;
            }
        }

        $keywords = $this->extractToolKeywords($tool);
        foreach ($keywords as $keyword) {
            if (str_contains($context, $keyword)) {
                $score += 8;
            }
        }

        // Strong intent boosts to avoid dropping critical tools under tool-cap limits.
        if (
            preg_match('/\blogs?\b/u', $context) === 1
            && str_contains($context, 'project')
            && str_contains($toolName, 'project logs')
        ) {
            $score += 80;
        }
        if (
            preg_match('/\blogs?\b/u', $context) === 1
            && (str_contains($toolName, 'log') || str_contains($toolDescription, 'log'))
        ) {
            $score += 25;
        }
        if (
            preg_match('/\busers?\b|\bteam\b|\bmembers?\b/u', $context) === 1
            && (str_contains($toolName, 'user') || str_contains($toolDescription, 'user'))
        ) {
            $score += 25;
        }
        if (
            preg_match('/\bdocuments?\b|\binvoices?\b|\btravel\b/u', $context) === 1
            && (
                str_contains($toolName, 'document')
                || str_contains($toolName, 'invoice')
                || str_contains($toolName, 'travel')
            )
        ) {
            $score += 25;
        }

        return $score;
    }

    /**
     * Extract weighted keyword candidates from tool metadata.
     *
     * @return array<int, string>
     */
    private function extractToolKeywords(AITool $tool): array
    {
        $source = $tool->name . ' ' . $tool->description . ' ' . implode(' ', array_keys($tool->arguments));
        preg_match_all('/[a-z]{4,}/i', $source, $matches);
        $keywords = array_map('mb_strtolower', $matches[0]);
        $keywords = array_values(array_unique(array_filter($keywords, fn(string $keyword): bool => !in_array($keyword, [
            'arguments', 'description', 'include', 'returns', 'using', 'current', 'false', 'true', 'with', 'from',
        ], true))));

        return array_slice($keywords, 0, 12);
    }

    /**
     * Reduce tool argument schema verbosity before sending to the model.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    private function compactToolArguments(array $arguments): array
    {
        $properties = [];

        foreach ($arguments as $name => $schema) {
            $schema = is_array($schema) ? $schema : [];
            $property = [
                'type' => $schema['type'] ?? 'string',
            ];
            if (!empty($schema['description'])) {
                $property['description'] = $this->trimText(
                    (string)$schema['description'],
                    self::MAX_TOOL_ARGUMENT_DESCRIPTION_CHARS,
                );
            }
            $properties[$name] = $property;
        }

        return $properties;
    }

    /**
     * Render one compact prompt line describing an available tool.
     *
     * @param \App\Lib\AITool $tool Tool metadata.
     * @return string
     */
    private function formatPromptTool(AITool $tool): string
    {
        $arguments = [];
        foreach ($tool->arguments as $name => $schema) {
            $type = is_array($schema) ? (string)($schema['type'] ?? 'string') : 'string';
            $arguments[] = $name . ':' . $type;
        }

        return '- ' . $tool->name . '(' . implode(', ', $arguments) . '): '
            . $this->trimText($tool->description, self::MAX_TOOL_DESCRIPTION_CHARS);
    }

    /**
     * Encode compact tool output for tool-role history storage.
     *
     * @param mixed $result
     * @return string
     */
    private function encodeToolResultForHistory(string $tool, mixed $result): string
    {
        $payload = $this->compactToolResult($tool, $result);
        $encoded = json_encode($payload);

        if ($encoded !== false) {
            return $encoded;
        }

        $fallback = json_encode(['tool' => $tool, 'summary' => 'Tool completed']);

        return $fallback !== false ? $fallback : '{}';
    }

    /**
     * Build a prompt-visible tool result message for local provider loops.
     *
     * @param mixed $result
     * @return string
     */
    private function buildToolResultSummaryMessage(string $tool, mixed $result): string
    {
        $payload = $this->compactToolResult($tool, $result, self::MAX_PROMPT_TOOL_RESULT_ITEMS);
        $encoded = json_encode($payload);

        if ($encoded !== false) {
            return 'Tool result for ' . $tool . ': ' . $encoded;
        }

        return 'Tool result for ' . $tool . ': ' . ($payload['summary'] ?? 'Tool completed');
    }

    /**
     * Reduce raw tool output to a bounded, model-friendly payload.
     *
     * @param mixed $result
     * @param int $maxItems Maximum items to include for list results.
     * @return array<string, mixed>
     */
    private function compactToolResult(string $tool, mixed $result, int $maxItems = self::MAX_TOOL_RESULT_ITEMS): array
    {
        if (is_array($result) && isset($result['error'])) {
            return [
                'tool' => $tool,
                'summary' => 'Error: ' . $this->trimText((string)$result['error'], self::MAX_TOOL_RESULT_CHARS),
            ];
        }

        if (is_array($result) && !$this->isAssociativeArray($result)) {
            $sample = array_map(
                fn($item) => $this->compactResultItem($item),
                array_slice($result, 0, $maxItems),
            );

            return [
                'tool' => $tool,
                'count' => count($result),
                'summary' => 'Returned ' . count($result) . ' items',
                'items' => $sample,
            ];
        }

        if (is_array($result)) {
            $data = $this->compactResultItem($result);

            return [
                'tool' => $tool,
                'summary' => $this->trimText(
                    $this->stringifyResultItem($data),
                    self::MAX_TOOL_RESULT_CHARS,
                ),
                'data' => $data,
            ];
        }

        if (is_object($result)) {
            $data = $this->compactResultItem($result);

            return [
                'tool' => $tool,
                'summary' => $this->trimText($this->stringifyResultItem($data), self::MAX_TOOL_RESULT_CHARS),
                'data' => $data,
            ];
        }

        return [
            'tool' => $tool,
            'summary' => $this->trimText((string)$result, self::MAX_TOOL_RESULT_CHARS),
        ];
    }

    /**
     * Extract key scalar fields from one result item for compact transport.
     *
     * @param mixed $item
     * @return mixed
     */
    private function compactResultItem(mixed $item): mixed
    {
        if (is_scalar($item) || $item === null) {
            return $item;
        }

        if (is_object($item)) {
            if (method_exists($item, 'toArray')) {
                $item = $item->toArray();
            } else {
                $item = get_object_vars($item);
            }
        }

        if (!is_array($item)) {
            return $this->trimText((string)$item, self::MAX_TOOL_RESULT_CHARS);
        }

        $priorityKeys = [
            'id',
            'no',
            'title',
            'name',
            'status',
            'active',
            'milestones_open',
            'milestones_done',
            'count',
            'total',
            'duration',
            'view_url',
            'project_view_url',
            'redirect_url',
        ];
        $data = [];
        foreach ($priorityKeys as $key) {
            if (array_key_exists($key, $item) && (is_scalar($item[$key]) || $item[$key] === null)) {
                $data[$key] = $item[$key];
            }
        }

        if ($data === []) {
            foreach ($item as $key => $value) {
                if (is_scalar($value) || $value === null) {
                    $data[(string)$key] = $value;
                }
                if (count($data) >= 5) {
                    break;
                }
            }
        }

        return $data === []
            ? ['summary' => $this->trimText($this->stringifyResultItem($item), self::MAX_TOOL_RESULT_CHARS)]
            : $data;
    }

    /**
     * Convert a value into a printable string for summaries and fallbacks.
     *
     * @param mixed $item
     * @return string
     */
    private function stringifyResultItem(mixed $item): string
    {
        if (is_scalar($item) || $item === null) {
            return (string)$item;
        }

        $encoded = json_encode($item);

        return $encoded !== false ? $encoded : '[unserializable result]';
    }

    /**
     * Determine whether an array uses non-sequential keys.
     *
     * @param array<mixed> $value
     * @return bool
     */
    private function isAssociativeArray(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * Normalize whitespace and truncate text to a maximum character length.
     *
     * @param string $text Input text.
     * @param int $maxChars Maximum text length.
     * @return string
     */
    private function trimText(string $text, int $maxChars): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxChars - 3)) . '...';
    }

    /**
     * Checks whether the configured AI provider host is reachable.
     *
     * @param int $timeoutSeconds Connection timeout in seconds.
     * @return bool True if the host responds, false otherwise.
     */
    public function isAvailable(int $timeoutSeconds = 3): bool
    {
        $userConfig = $this->currentUser !== null ? $this->currentUser->getProperty('ai_assistant') : null;
        $provider = $userConfig->provider ?? 'local';

        if ($provider === 'openai') {
            $url = 'https://api.openai.com';
        } else {
            $rawUrl = $userConfig->url ?? 'http://192.168.68.55:8080/v1/chat/completions';
            $parsed = parse_url($rawUrl);
            $url = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost');
            if (isset($parsed['port'])) {
                $url .= ':' . $parsed['port'];
            }
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        return $result !== false && $curlError === '';
    }

    /**
     * Makes the API request to the AI model and returns the response.
     * Provider, URL, model and API key are read from the user's 'ai_assistant' property.
     *
     * @param array<mixed> $data The data to send in the API request.
     * @return array<mixed> The decoded response from the API, containing at least 'id' and 'content' keys.
     * @throws \Exception If the API call fails or returns an error
     */
    protected function doRequest(array $data): array
    {
        $userConfig = $this->currentUser !== null ? $this->currentUser->getProperty('ai_assistant') : null;
        $provider = $userConfig->provider ?? 'local';
        $apiKey = $userConfig->api_key ?? '';

        if ($provider === 'openai') {
            $url = 'https://api.openai.com/v1/chat/completions';
            $model = $userConfig->model ?? 'gpt-4o';
        } else {
            $url = $userConfig->url ?? 'http://192.168.68.55:8080/v1/chat/completions';
            $model = $userConfig->model ?? 'qwen';
        }

        $data['model'] = $model;

        $headers = ['Content-Type: application/json'];
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $payload = json_encode($data);
        if ($payload === false) {
            throw new Exception('Failed to encode request data: ' . json_last_error_msg());
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new Exception(sprintf(
                'cURL request failed [errno %d] to %s: %s',
                $curlErrno,
                $url,
                $curlError,
            ));
        }

        $result = json_decode((string)$response, true);

        Log::debug('AI raw response: ' . $response, ['scope' => ['ai']]);

        if (isset($result['error'])) {
            $errorDetail = is_array($result['error'])
                ? (string)json_encode($result['error'])
                : (string)$result['error'];
            throw new Exception(sprintf(
                'API Error [HTTP %d] from %s: %s',
                $httpCode,
                $url,
                $errorDetail,
            ));
        } elseif (!isset($result['choices'][0]['message'])) {
            throw new Exception(sprintf(
                'Unexpected API response [HTTP %d] from %s: %s',
                $httpCode,
                $url,
                $response,
            ));
        }

        $choice = $result['choices'][0];
        $responseMessage = $choice['message'];
        $finishReason = $choice['finish_reason'] ?? 'stop';

        if ($finishReason !== 'tool_calls' && !isset($responseMessage['content'])) {
            throw new Exception('Unexpected API response: ' . $response);
        }

        return [
            'id' => $result['id'] ?? '',
            'content' => $responseMessage['content'] ?? '',
            'finish_reason' => $finishReason,
            'tool_calls' => $responseMessage['tool_calls'] ?? [],
        ];
    }
}
