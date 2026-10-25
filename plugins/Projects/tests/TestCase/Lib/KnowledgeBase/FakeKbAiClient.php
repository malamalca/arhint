<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Projects\Lib\KnowledgeBase\KbAiClientInterface;
use Projects\Lib\KnowledgeBase\KbAiException;

/**
 * Answers with what the test queued: an array, null (not JSON) or an exception.
 */
class FakeKbAiClient implements KbAiClientInterface
{
    /**
     * @var array<int, array{system: string, user: string}>
     */
    public array $requests = [];

    /**
     * @param array<int, array<string, mixed>|\Throwable|null> $answers Answers in order; the last one repeats.
     */
    public function __construct(private array $answers = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function json(string $system, string $user): ?array
    {
        $this->requests[] = ['system' => $system, 'user' => $user];
        $answer = match (true) {
            count($this->answers) > 1 => array_shift($this->answers),
            $this->answers === [] => ['operations' => []],
            default => $this->answers[0],
        };
        if ($answer instanceof KbAiException) {
            throw $answer;
        }

        return $answer;
    }
}
