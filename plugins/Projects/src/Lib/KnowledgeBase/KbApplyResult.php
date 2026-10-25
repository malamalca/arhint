<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

/**
 * Outcome of applying operations to a document.
 */
class KbApplyResult
{
    /**
     * @param int $applied Number of operations that changed the document.
     * @param array<int, string> $descriptions Short Slovenian description of each applied operation.
     * @param array<int, string> $rejected Reasons for the operations that were left out.
     */
    public function __construct(
        public int $applied = 0,
        public array $descriptions = [],
        public array $rejected = [],
    ) {
    }

    /**
     * Whether the document was changed.
     *
     * @return bool
     */
    public function changed(): bool
    {
        return $this->applied > 0;
    }
}
