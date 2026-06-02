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
    private const MAX_TOOL_CALLS = 5;
    private const MAX_TOOLS_PER_REQUEST = 8;
    private const MAX_HISTORY_MESSAGES = 8;
    private const MAX_HISTORY_SUMMARY_CHARS = 1500;
    private const MAX_MESSAGE_SNIPPET_CHARS = 180;
    private const MAX_TOOL_DESCRIPTION_CHARS = 220;
    private const MAX_TOOL_ARGUMENT_DESCRIPTION_CHARS = 80;
    // Maximum items in a tool-result list stored in conversation history.
    private const MAX_HISTORY_TOOL_RESULT_ITEMS = 10;
    // Maximum scalar fields per item when compacting tool results.
    // Entities typically have 15-18 columns (Documents has 18), so cap covers all.
    private const MAX_COMPACT_ITEM_FIELDS = 20;
    // Maximum items in a tool-result list injected into the LLM prompt (native tool calls).
    private const MAX_PROMPT_TOOL_RESULT_ITEMS = 20;
    private const MAX_TOOL_RESULT_CHARS = 800;

    public const SYSTEM_PROMPT = "You are an assistant for a business management system.\n" .
        "Proceed with great speed and accuracy. Do not show UUIDs in your responses.\n" .
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
        "- Current user ID: %s\n" .
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
     * AI provider. 'openai' uses native tool_calls format by default; other providers can
     * also use native tool calling when configured.
     *
     * @var string
     */
    private string $provider = 'local';

    /**
     * Custom system prompt to override the default SYSTEM_PROMPT.
     *
     * @var string|null
     */
    private ?string $customSystemPrompt = null;

    /**
     * Constructor to initialize the AIAssistant with available tools.
     * Tools can be added by listening to the 'AIAssistant.tools' event and modifying the provided tools list.
     */
    public function __construct(?User $currentUser = null)
    {
        $this->currentUser = $currentUser;

        $aiConfig = $currentUser?->getProperty('ai_assistant');
        $this->provider = $aiConfig?->provider ?? 'local';

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
     * Returns true when native tool calling should be used for the current AI config.
     * OpenAI enables native tool calls by default; other providers can enable it with
     * an explicit ai_assistant.native_tool_calls configuration property.
     *
     * @return bool
     */
    private function shouldUseNativeToolCalls(): bool
    {
        $aiConfig = $this->currentUser?->getProperty('ai_assistant');
        if ($aiConfig !== null && isset($aiConfig->native_tool_calls)) {
            return (bool)$aiConfig->native_tool_calls;
        }

        return $this->provider === 'openai';
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
     * Sets a custom system prompt to override the default SYSTEM_PROMPT.
     *
     * @param string $customSystemPrompt The custom system prompt to use.
     * @return void
     */
    public function setSystemPrompt(string $customSystemPrompt): void
    {
        $this->customSystemPrompt = $customSystemPrompt;
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

        Log::debug(
            'AI getResponse: ' . $this->trimText($userInput, 200),
            [
                'scope' => ['ai'],
                'history_length' => count($this->conversationHistory),
                'provider' => $this->provider,
            ],
        );

        // Select tools once before the loop — calling detectModulesViaAI() on every
        // iteration wastes API calls, tokens, and time (the routing prompt is stateless
        // and always returns the same result for a given user input).
        $activeTools = $this->selectToolsForRequest($userInput);

        Log::debug(
            'AI selected tools: ' . implode(', ', array_map(fn($t) => $t->name, $activeTools)),
            [
                'scope' => ['ai'],
                'tool_count' => count($activeTools),
            ],
        );

        while (true) {

            if ($this->shouldUseNativeToolCalls()) {
                $summary = null;
                $historyWithoutSummary = $this->conversationHistory;
                if ($this->provider === 'openai') {
                    // Strip the rolling summary from conversation history (it is stored as a fake assistant
                    // message for serialization purposes) and inject it into the system prompt instead,
                    // avoiding an invalid assistant-before-user turn order in the OpenAI API.
                    $historyWithoutSummary = array_values(array_filter(
                        $this->conversationHistory,
                        function (array $msg) use (&$summary): bool {
                            $content = (string)($msg['content'] ?? '');
                            if (
                                ($msg['role'] ?? '') === 'assistant'
                                && str_starts_with($content, self::HISTORY_SUMMARY_PREFIX)
                            ) {
                                $summary = trim(substr($content, strlen(self::HISTORY_SUMMARY_PREFIX)));

                                return false;
                            }

                            return true;
                        },
                    ));
                }
                if ($this->customSystemPrompt !== null) {
                    $systemContent = $this->customSystemPrompt;
                } else {
                    $systemContent = 'You are an assistant for a business management system. '
                        . 'Proceed with great speed and accuracy. Do not show UIDs in your responses. '
                        . 'Current date and time: ' . date('Y-m-d H:i:s') . '. '
                        . 'Current user ID: ' . (string)($this->currentUser?->get('id') ?? '') . '.';
                }
                if ($summary !== null && $summary !== '') {
                    $systemContent .= "\n\nConversation context: " . $summary;
                }
                $data = [
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $systemContent]],
                        $historyWithoutSummary,
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
                if ($this->customSystemPrompt !== null) {
                    $systemPrompt = $this->customSystemPrompt;
                } else {
                    $promptTools = array_map(
                        fn($tool) => $this->formatPromptTool($tool),
                        $activeTools,
                    );
                    $systemPrompt = sprintf(
                        self::SYSTEM_PROMPT,
                        date('Y-m-d H:i:s'),
                        (string)($this->currentUser?->get('id') ?? ''),
                        implode("\n", $promptTools),
                    );
                }
                $data = [
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $systemPrompt]],
                        $this->conversationHistory,
                    ),
                ];
            }

            Log::debug(
                'AI >>> MAIN REQUEST START <<< tools=' . count($data['tools'] ?? []) . ' messages=' . count($data['messages'] ?? []),
                ['scope' => ['ai']],
            );

            $requestStart = microtime(true);
            $message = $this->doRequest($data);
            $requestDuration = round((microtime(true) - $requestStart) * 1000, 2);

            Log::debug(
                'AI >>> MAIN REQUEST END <<< finish=' . ($message['finish_reason'] ?? '?') . ' toolCalls=' . count($message['tool_calls'] ?? []) . ' contentLen=' . strlen((string)$message['content']),
                ['scope' => ['ai']],
            );

            Log::debug(
                'AI response received in ' . $requestDuration . 'ms',
                [
                    'scope' => ['ai'],
                    'duration_ms' => $requestDuration,
                    'finish_reason' => $message['finish_reason'] ?? 'unknown',
                    'has_tool_calls' => !empty($message['tool_calls']),
                    'iteration' => $toolCallCount,
                ],
            );

            // Log the main request response for diagnostics (tools provided but model chose not to call any).
            if ($this->shouldUseNativeToolCalls() && empty($message['tool_calls']) && !empty($activeTools)) {
                Log::debug(
                    'AI model returned no tool_calls despite tools being available',
                    [
                        'scope' => ['ai'],
                        'finish_reason' => $message['finish_reason'] ?? 'unknown',
                        'content_empty' => trim((string)$message['content']) === '',
                        'content_snippet' => $this->trimText((string)$message['content'], 200),
                        'tools_available' => array_map(fn($t) => $t->name, $activeTools),
                    ],
                );
            }

            // Native tool_calls support for configured providers.
            if (
                $this->shouldUseNativeToolCalls()
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

                    Log::debug(
                        "AI executing tool $tool with arguments " . json_encode($arguments),
                        [
                            'scope' => ['ai'],
                            'tool' => $tool,
                            'arguments' => $arguments,
                        ],
                    );

                    try {
                        $event = new Event(
                            'App.AIAssistant.executeTool',
                            $this,
                            [$tool, $arguments, $this->currentUser],
                        );
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
                    } catch (Exception $e) {
                        $errorResult = ['error' => $e->getMessage()];
                        Log::error(
                            'AI tool error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine(),
                            [
                                'scope' => ['ai'],
                                'tool' => $tool,
                                'arguments' => $arguments,
                                'trace' => $e->getTraceAsString(),
                            ],
                        );

                        $this->appendConversationMessage([
                            'role' => 'tool',
                            'tool_call_id' => $toolCall['id'],
                            'content' => $this->encodeToolResultForHistory($tool, $errorResult),
                        ]);
                    }
                }

                $toolCallCount++;
                continue;
            }

            // Prompt-based tool calls for providers without native tool_calls enabled.
            if (!$this->shouldUseNativeToolCalls()) {
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

                    Log::debug(
                        "AI executing tool $tool with arguments " . json_encode($arguments),
                        [
                            'scope' => ['ai'],
                            'tool' => $tool,
                            'arguments' => $arguments,
                        ],
                    );

                    try {
                        $event = new Event(
                            'App.AIAssistant.executeTool',
                            $this,
                            [$tool, $arguments, $this->currentUser],
                        );
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
                    } catch (Exception $e) {
                        $errorResult = ['error' => $e->getMessage()];
                        Log::error(
                            'AI tool error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine(),
                            [
                                'scope' => ['ai'],
                                'tool' => $tool,
                                'arguments' => $arguments,
                                'trace' => $e->getTraceAsString(),
                            ],
                        );

                        $this->appendConversationMessage(['role' => 'assistant', 'content' => $message['content']]);
                        $this->appendConversationMessage([
                            'role' => 'user',
                            'content' => $this->buildToolResultSummaryMessage($tool, $errorResult),
                        ]);
                    }

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

        // Ensure no orphaned 'tool' messages remain at the head of recentMessages.
        // This happens when the overflow cut falls between an assistant(tool_calls) message and its
        // paired tool-response messages. Collect all tool_call IDs that are present in recentMessages
        // and drop any tool message whose ID is not among them.
        $seenToolCallIds = [];
        foreach ($recentMessages as $msg) {
            if (($msg['role'] ?? '') === 'assistant' && !empty($msg['tool_calls']) && is_array($msg['tool_calls'])) {
                foreach ($msg['tool_calls'] as $tc) {
                    if (isset($tc['id'])) {
                        $seenToolCallIds[(string)$tc['id']] = true;
                    }
                }
            }
        }
        $recentMessages = array_values(array_filter(
            $recentMessages,
            fn(array $msg): bool => ($msg['role'] ?? '') !== 'tool'
                || isset($seenToolCallIds[(string)($msg['tool_call_id'] ?? '')]),
        ));

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
        // App tools are always included and do not count toward MAX_TOOLS_PER_REQUEST.
        $appTools = array_values(
            array_filter($this->tools, fn(AITool $t): bool => str_starts_with($t->name, 'App.')),
        );
        $otherTools = array_values(
            array_filter($this->tools, fn(AITool $t): bool => !str_starts_with($t->name, 'App.')),
        );

        if (count($otherTools) <= self::MAX_TOOLS_PER_REQUEST) {
            return array_merge($appTools, $otherTools);
        }

        // Group non-App tools by their module prefix (e.g. "Projects", "Crm").
        $moduleGroups = [];
        foreach ($otherTools as $tool) {
            $module = strtok($tool->name, '.') ?: 'misc';
            $moduleGroups[$module][] = $tool;
        }

        $recentModule = $this->getRecentToolModule();

        // Build context from recent history for routing follow-up requests like "delete #4".
        $historyForContext = array_values(array_filter(
            $this->conversationHistory,
            fn(array $msg): bool => !str_starts_with((string)($msg['content'] ?? ''), self::HISTORY_SUMMARY_PREFIX),
        ));
        // Exclude the last entry (the current user input already appended to history).
        $priorMessages = array_slice($historyForContext, -4, 3);
        $priorContext = !empty($priorMessages) ? $this->summarizeMessages($priorMessages) : '';

        $detectedModules = $this->detectModulesViaAI(
            array_keys($moduleGroups),
            array_filter($this->moduleDescriptions, fn(string $k): bool => $k !== 'App', ARRAY_FILTER_USE_KEY),
            $userInput,
            $priorContext,
        );

        Log::debug(
            'AI module detection: ' . (empty($detectedModules) ? 'none' : implode(', ', $detectedModules)),
            [
                'scope' => ['ai'],
                'detected_modules' => $detectedModules,
                'recent_module' => $recentModule,
            ],
        );

        // Always include the module used most recently in conversation so short follow-ups
        // like "delete #4" after a calendar listing get the correct toolset.
        if ($recentModule !== null) {
            array_unshift($detectedModules, $recentModule);
            $detectedModules = array_values(array_unique($detectedModules));
        }

        if ($detectedModules !== []) {
            $selected = [];
            foreach ($detectedModules as $module) {
                foreach ($moduleGroups[$module] ?? [] as $tool) {
                    $selected[] = $tool;
                }
            }

            return array_merge($appTools, $selected);
        }

        // AI detection failed — fall back to keyword scoring so that tools whose
        // name or description match words from the user's input are preferred over
        // an arbitrary slice of the tool list.
        $queryWords = array_filter(preg_split('/\W+/', strtolower($userInput)) ?: []);
        usort($otherTools, function (AITool $a, AITool $b) use ($queryWords): int {
            $scoreA = 0;
            $scoreB = 0;
            foreach ($queryWords as $word) {
                if (str_contains(strtolower($a->name), $word) || str_contains(strtolower($a->description), $word)) {
                    $scoreA++;
                }
                if (str_contains(strtolower($b->name), $word) || str_contains(strtolower($b->description), $word)) {
                    $scoreB++;
                }
            }

            return $scoreB <=> $scoreA;
        });

        return array_merge($appTools, array_slice($otherTools, 0, self::MAX_TOOLS_PER_REQUEST));
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
     * @param string $userInput The current user message.
     * @param string $priorContext Summary of recent conversation turns (may be empty).
     * @return array<int, string> Matched module names, or [] on failure.
     */
    private function detectModulesViaAI(
        array $modules,
        array $moduleDescriptions,
        string $userInput,
        string $priorContext = '',
    ): array {
        $moduleLines = array_map(function (string $module) use ($moduleDescriptions): string {
            $desc = $moduleDescriptions[$module] ?? '';

            return $desc !== '' ? "- {$module}: {$desc}" : "- {$module}";
        }, $modules);
        $moduleList = implode("\n", $moduleLines);

        // @cs-ignore Generic.Files.LineLength.TooLong
        $prompt = 'You are a routing assistant. Your job is to identify which module(s) a user request targets.' . "\n"
            . 'Available modules:' . "\n" . $moduleList . "\n\n"
            . 'Rules:' . "\n"
            . '- If the current request clearly names a new domain (e.g. "projects", "tasks", "calendar"), ' .
            'return that module.' . "\n"
            . '- If the current request is ambiguous (e.g. "delete #3", "show details") and prior context exists, ' .
            'use the prior context to infer the module.' . "\n"
            . '- If nothing can be determined, return [].' . "\n"
            . 'Respond with ONLY a JSON array, e.g. ["Projects"] or ["Crm"] or [].' . "\n\n";

        if ($priorContext !== '') {
            $prompt .= 'Prior conversation context: ' . $priorContext . "\n";
        }
        $prompt .= 'Current request: ' . $userInput;

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
     * Detect the most recent tool module used in conversation history.
     *
     * @return string|null
     */
    private function getRecentToolModule(): ?string
    {
        for ($index = count($this->conversationHistory) - 1; $index >= 0; $index--) {
            $message = $this->conversationHistory[$index];
            $role = (string)($message['role'] ?? '');
            $content = (string)($message['content'] ?? '');

            // Local provider: tool result stored as user message with a known prefix.
            if ($role === 'user' && str_starts_with($content, 'Tool result for ')) {
                if (preg_match('/^Tool result for ([A-Za-z0-9_.-]+)/', $content, $matches) === 1) {
                    return strtok($matches[1], '.') ?: null;
                }
            }

            // OpenAI provider: tool result stored as role=tool with JSON payload containing a 'tool' key.
            if ($role === 'tool' && str_starts_with(ltrim($content), '{')) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['tool']) && is_string($decoded['tool'])) {
                    return strtok($decoded['tool'], '.') ?: null;
                }
            }
        }

        return null;
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
    private function compactToolResult(
        string $tool,
        mixed $result,
        int $maxItems = self::MAX_HISTORY_TOOL_RESULT_ITEMS,
    ): array {
        if (is_array($result) && isset($result['error'])) {
            Log::debug(
                "Tool error $tool" . (is_string($result['error']) ? ': ' . $result['error'] : ''),
                [
                    'scope' => ['ai'],
                    'tool' => $tool,
                    'result' => $result,
                ],
            );

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
            if ($item instanceof AISerializableInterface) {
                return $item->toAIArray();
            }
            if (method_exists($item, 'toArray')) {
                $item = $item->toArray();
            } else {
                $item = get_object_vars($item);
            }
        }

        if (!is_array($item)) {
            return $this->trimText((string)$item, self::MAX_TOOL_RESULT_CHARS);
        }

        $data = [];
        foreach ($item as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $data[(string)$key] = $value;
            }
            if (count($data) >= self::MAX_COMPACT_ITEM_FIELDS) {
                break;
            }
        }

        // Always carry through view_url even when the field cap was hit.
        if (!isset($data['view_url']) && isset($item['view_url']) && is_scalar($item['view_url'])) {
            $data['view_url'] = $item['view_url'];
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
        $provider = $userConfig?->provider ?? 'local';

        if ($provider === 'openai') {
            $url = 'https://api.openai.com';
        } else {
            $rawUrl = $userConfig?->url ?? 'http://192.168.68.58:8080/v1/chat/completions';
            $parsed = parse_url($rawUrl);
            $url = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost');
            if (isset($parsed['port'])) {
                $url .= ':' . $parsed['port'];
            }
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
        $result = curl_exec($ch);
        $curlError = curl_error($ch);

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
        $provider = $userConfig?->provider ?? 'local';
        $apiKey = $userConfig?->api_key ?? '';

        if ($provider === 'openai') {
            $url = 'https://api.openai.com/v1/chat/completions';
            $model = $userConfig?->model ?? 'gpt-4o';
        } else {
            $url = $userConfig?->url ?? 'http://192.168.68.58:8080/v1/chat/completions';
            $model = $userConfig?->model ?? 'qwen';
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

        // Log the request being sent to the AI (without API keys).
        $logContext = [
            'scope' => ['ai'],
            'provider' => $provider,
            'model' => $model,
            'url' => $url,
            'message_count' => count($data['messages'] ?? []),
            'tool_count' => count($data['tools'] ?? []),
        ];

        // Build a safe log payload (strip Authorization header / API key from the log).
        $logData = $data;
        // Remove tool definitions from the log to keep it concise; log counts instead.
        if (isset($logData['tools']) && is_array($logData['tools'])) {
            $logData['tools'] = array_map(function (array $tool) {
                return [
                    'type' => $tool['type'] ?? 'function',
                    'function' => [
                        'name' => $tool['function']['name'] ?? '',
                        'description' => $tool['function']['description'] ?? '',
                        'parameter_count' => isset($tool['function']['parameters']['properties'])
                            ? count($tool['function']['parameters']['properties'])
                            : 0,
                    ],
                ];
            }, $logData['tools']);
        }

        Log::debug(
            'AI request: ' . json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $logContext,
        );

        $ch = curl_init($url);
        if ($ch === false) {
            throw new Exception('Failed to initialize cURL for: ' . $url);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

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

        // Check for model refusal (OpenAI may refuse tool calls or the request itself).
        if (!empty($responseMessage['refusal'])) {
            throw new Exception(sprintf(
                'Model refused: %s',
                $this->trimText((string)$responseMessage['refusal'], self::MAX_TOOL_RESULT_CHARS),
            ));
        }

        $content = $responseMessage['content'] ?? '';

        // Some models (e.g. Qwen reasoning) put all output in reasoning_content.
        // Fall back to reasoning_content when content is empty so the response
        // is still usable for both chat and tool-calling flows.
        if (trim((string)$content) === '' && !empty($responseMessage['reasoning_content'])) {
            $content = $responseMessage['reasoning_content'];
        }

        return [
            'id' => $result['id'] ?? '',
            'content' => $content,
            'finish_reason' => $finishReason,
            'tool_calls' => $responseMessage['tool_calls'] ?? [],
        ];
    }
}
