<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\AIAssistant;
use App\Lib\AITool;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use ReflectionClass;

/**
 * @group live
 */
class AIAssistantLiveTest extends TestCase
{
    private const DEFAULT_LIVE_URL = 'http://192.168.68.55:8080/v1/chat/completions';
    private const DEFAULT_MODEL = 'qwen';

    private string $liveUrl;
    private string $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->liveUrl = (string)(getenv('ARHINT_AI_LIVE_URL') ?: self::DEFAULT_LIVE_URL);
        $this->model = (string)(getenv('ARHINT_AI_LIVE_MODEL') ?: self::DEFAULT_MODEL);
    }

    public function testDoRequestAgainstLiveServer(): void
    {
        $this->skipIfLiveServerUnavailable();

        $assistant = new AIAssistant();
        $reflection = new ReflectionClass($assistant);
        $method = $reflection->getMethod('doRequest');
        $method->setAccessible(true);

        $response = $method->invoke($assistant, [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Reply with the single word OK.',
                ],
            ],
        ]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('content', $response);
        $this->assertNotSame('', trim((string)$response['content']));
        $this->assertArrayHasKey('finish_reason', $response);
    }

    public function testGetResponseAgainstLiveServer(): void
    {
        $this->skipIfLiveServerUnavailable();

        $assistant = new AIAssistant();
        $response = $assistant->getResponse('Answer briefly: say hello in two words. Do not call any tool.');

        $this->assertIsString($response);
        $this->assertNotSame('', trim($response));

        $history = $assistant->getHistory();
        $this->assertNotEmpty($history);
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame('assistant', $history[array_key_last($history)]['role']);
    }

    public function testGetResponseUsesStubbedToolResultAgainstLiveServer(): void
    {
        $this->skipIfLiveServerUnavailable();

        $eventManager = EventManager::instance();
        $toolExecuted = false;

        $toolsListener = function (Event $event, ArrayObject $toolsList): void {
            $toolsList->append(new AITool(
                name: 'LiveTest.echo_items',
                arguments: [
                    'topic' => [
                        'type' => 'string',
                        'description' => 'Topic for the fixed live-test response.',
                    ],
                ],
                description: 'Returns fixed titles Alpha Stub Project and Beta Stub Project for live test verification.',
            ));
        };
        $executeListener = function (Event $event, string $tool, array $arguments) use (&$toolExecuted): void {
            if ($tool !== 'LiveTest.echo_items') {
                return;
            }

            $toolExecuted = true;
            $event->setResult([
                [
                    'id' => 'stub-1',
                    'no' => 'T-001',
                    'title' => 'Alpha Stub Project',
                    'active' => true,
                ],
                [
                    'id' => 'stub-2',
                    'no' => 'T-002',
                    'title' => 'Beta Stub Project',
                    'active' => true,
                ],
            ]);
        };

        $eventManager->on('App.AIAssistant.tools', $toolsListener);
        $eventManager->on('App.AIAssistant.executeTool', $executeListener);

        try {
            $assistant = new AIAssistant();
            $response = $assistant->getResponse(
                'Use the LiveTest.echo_items tool for active projects and reply using the returned titles only, one per line.',
            );

            $this->assertTrue($toolExecuted, 'Expected the live model to call the temporary LiveTest.echo_items tool.');
            $this->assertStringContainsString('Alpha Stub Project', $response);
            $this->assertStringContainsString('Beta Stub Project', $response);
        } finally {
            $eventManager->off($toolsListener);
            $eventManager->off($executeListener);
        }
    }

    private function skipIfLiveServerUnavailable(): void
    {
        $probe = $this->probeLiveServer();
        if ($probe === null) {
            $this->markTestSkipped('Live AI server is not available at ' . $this->liveUrl);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function probeLiveServer(): ?array
    {
        $payload = json_encode([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Reply with OK.',
                ],
            ],
            'max_tokens' => 8,
        ]);

        if ($payload === false) {
            $this->fail('Failed to encode live-server probe payload.');
        }

        $ch = curl_init($this->liveUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded) || !isset($decoded['choices'][0]['message'])) {
            $this->fail('Live AI server returned an unexpected response: ' . (string)$response);
        }

        return $decoded;
    }
}
