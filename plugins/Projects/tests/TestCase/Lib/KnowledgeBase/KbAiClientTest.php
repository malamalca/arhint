<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\TestSuite\TestCase;
use Projects\Lib\KnowledgeBase\KbAiClient;
use Projects\Lib\KnowledgeBase\KbAiException;

class KbAiClientTest extends TestCase
{
    /**
     * @return void
     */
    public function testExtractsJsonObjectFromWhateverTheModelWrote(): void
    {
        $object = ['operations' => [], 'changes_summary' => 'x'];
        $json = (string)json_encode($object);

        $this->assertSame($object, KbAiClient::extractJson($json));
        $this->assertSame($object, KbAiClient::extractJson("```json\n$json\n```"));
        $this->assertSame($object, KbAiClient::extractJson("Tukaj je odgovor:\n$json\nLep pozdrav"));
        $this->assertSame($object, KbAiClient::extractJson("  \n$json\n  "));
    }

    /**
     * @return void
     */
    public function testAnythingElseIsNotAnAnswer(): void
    {
        $this->assertNull(KbAiClient::extractJson(''));
        $this->assertNull(KbAiClient::extractJson('Ne razumem'));
        $this->assertNull(KbAiClient::extractJson('[1, 2, 3]'));
        $this->assertNull(KbAiClient::extractJson('{"operations": '));
    }

    /**
     * @return void
     */
    public function testUnreachableServerThrowsAfterRetries(): void
    {
        // Nothing listens on this port.
        $client = new KbAiClient(['url' => 'http://127.0.0.1:1/v1/chat/completions', 'timeout' => 1], []);

        $this->expectException(KbAiException::class);
        $client->json('system', 'user');
    }
}
