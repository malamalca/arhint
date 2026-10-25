<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

/**
 * What a run of the knowledge base update did.
 */
class KbUpdateReport
{
    /**
     * @var int Projects with new events that were looked at.
     */
    public int $projects = 0;

    /**
     * @var int Events that were read by the model.
     */
    public int $events = 0;

    /**
     * @var int Revisions written.
     */
    public int $revisions = 0;

    /**
     * @var int Events whose analysis failed and will be tried again.
     */
    public int $failed = 0;

    /**
     * @var array<int, string> Lines for the console and the log.
     */
    public array $messages = [];

    /**
     * @var string|null Why the run was stopped early.
     */
    public ?string $stopped = null;

    /**
     * @param string $message Line to add.
     * @return void
     */
    public function note(string $message): void
    {
        $this->messages[] = $message;
    }
}
