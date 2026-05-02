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
     * @var \App\Model\Entity\User|null The current user, which can be used for personalized responses or permission checks when executing tools.
     */
    private ?User $currentUser;

    /**
     * Conversation history (user, assistant, and tool messages), excluding the system prompt.
     *
     * @var array<array{role: string, content: string}>
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
     * @return array<array{role: string, content: string}>
     */
    public function getHistory(): array
    {
        return $this->conversationHistory;
    }

    /**
     * Replaces the conversation history (e.g. when restoring from session).
     *
     * @param array<array{role: string, content: string}> $history
     */
    public function setHistory(array $history): void
    {
        $this->conversationHistory = $history;
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
        $this->conversationHistory[] = ['role' => 'user', 'content' => $userInput];

        $this->redirectUrl = null;
        $maxToolCalls = 5;
        $toolCallCount = 0;
        $message = [];

        while (true) {
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
                if (!empty($this->tools)) {
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
                                'description' => $tool->description,
                                'parameters' => [
                                    'type' => 'object',
                                    'properties' => empty($tool->arguments) ? new stdClass() : $tool->arguments,
                                ],
                            ],
                        ];
                    }, $this->tools);
                    $data['tool_choice'] = 'auto';
                }
            } else {
                $promptTools = array_map(
                    fn($tool) => "- {$tool->name}(" . implode(', ', array_keys($tool->arguments)) . '): ' .
                        "{$tool->description} Arguments: " . json_encode($tool->arguments),
                    $this->tools,
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
            if ($this->provider === 'openai' && $toolCallCount < $maxToolCalls && !empty($message['tool_calls'])) {
                $this->conversationHistory[] = [
                    'role' => 'assistant',
                    'content' => $message['content'],
                    'tool_calls' => $message['tool_calls'],
                ];

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

                    $this->conversationHistory[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => (string)json_encode($result ?? []),
                    ];

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
            if ($this->provider !== 'openai' && $toolCallCount < $maxToolCalls) {
                // Strip markdown code fences that some models add despite instructions
                $content = trim($message['content']);
                $content = (string)preg_replace('/^```(?:json)?\s*/i', '', $content);
                $content = (string)preg_replace('/\s*```$/', '', $content);
                $message['content'] = trim($content);

                $json = json_decode($message['content'], true);
                $isToolCall = str_starts_with($message['content'], '{')
                    && json_last_error() === JSON_ERROR_NONE
                    && isset($json['tool']);
                if ($isToolCall) {
                    $tool = $json['tool'];
                    $arguments = $json['arguments'] ?? [];

                    $event = new Event('App.AIAssistant.executeTool', $this, [$tool, $arguments, $this->currentUser]);
                    EventManager::instance()->dispatch($event);

                    $result = $event->getResult();
                    if (is_array($result) && isset($result['redirect_url'])) {
                        $this->redirectUrl = $result['redirect_url'];
                    }

                    $this->conversationHistory[] = ['role' => 'assistant', 'content' => $message['content']];
                    $this->conversationHistory[] = [
                        'role' => 'user',
                        'content' => 'Tool result for ' . $tool . ': ' . json_encode($result ?? []),
                    ];

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
            }

            $this->conversationHistory[] = ['role' => 'assistant', 'content' => $message['content']];
            break;
        }

        return $message['content'];
    }

    /**
     * Makes the API request to the AI model and returns the response.
     * Provider, URL, model and API key are read from the user's 'ai_assistant' property.
     *
     * @param array<mixed> $data The data to send in the API request.
     * @return array<mixed> The decoded response from the API, containing at least 'id' and 'content' keys.
     * @throws \Exception If the API call fails or returns an error
     */
    private function doRequest(array $data): array
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
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('cURL request failed: ' . $curlError);
        }

        $result = json_decode((string)$response, true);

        Log::debug('AI raw response: ' . $response, ['scope' => ['ai']]);

        if (isset($result['error'])) {
            throw new Exception('API Error: ' . $result['error']['message']);
        } elseif (!isset($result['choices'][0]['message'])) {
            throw new Exception('Unexpected API response: ' . $response);
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
