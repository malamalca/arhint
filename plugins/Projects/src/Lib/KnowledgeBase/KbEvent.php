<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

/**
 * Something that happened in a project, as the knowledge base update reads it.
 */
class KbEvent
{
    /**
     * @param string $model Kind of the source: Log, AuthorityEvent, Task, TaskComment or Milestone.
     * @param string $id Id of the source row.
     * @param string $modified Modification time of the source row (Y-m-d H:i:s); the key of the event together with the id.
     * @param string $time When it happened (Y-m-d H:i:s), the order of the events.
     * @param string $text What happened, as plain text for the model.
     */
    public function __construct(
        public string $model,
        public string $id,
        public string $modified,
        public string $time,
        public string $text,
    ) {
    }

    /**
     * Reference that the model quotes as the source of an item, e.g. `log:6b1e…`.
     *
     * @return string
     */
    public function ref(): string
    {
        return match ($this->model) {
            'Log' => 'log',
            'AuthorityEvent' => 'authority_event',
            'Task' => 'task',
            'TaskComment' => 'task_comment',
            'Milestone' => 'milestone',
            default => strtolower($this->model),
        } . ':' . $this->id;
    }

    /**
     * Key that tells whether the event was dealt with.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->model . '|' . $this->id . '|' . $this->modified;
    }
}
