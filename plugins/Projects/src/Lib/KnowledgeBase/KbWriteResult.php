<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use Projects\Model\Entity\ProjectsKbRevision;

/**
 * Outcome of writing operations to the knowledge base of a project.
 */
class KbWriteResult
{
    /**
     * @param \Projects\Model\Entity\ProjectsKbRevision|null $revision The new revision, null when nothing changed.
     * @param \Projects\Lib\KnowledgeBase\KbApplyResult $result What happened to each operation.
     */
    public function __construct(
        public ?ProjectsKbRevision $revision,
        public KbApplyResult $result,
    ) {
    }
}
