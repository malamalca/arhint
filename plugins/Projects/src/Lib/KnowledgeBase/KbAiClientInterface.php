<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

/**
 * Asks AI for a JSON answer.
 */
interface KbAiClientInterface
{
    /**
     * Ask for a JSON object.
     *
     * @param string $system System instructions.
     * @param string $user The request.
     * @return array<string, mixed>|null The answer; null when the model answered, but not with a JSON object.
     * @throws \Projects\Lib\KnowledgeBase\KbAiException When the model cannot be reached or refuses the request.
     */
    public function json(string $system, string $user): ?array;
}
