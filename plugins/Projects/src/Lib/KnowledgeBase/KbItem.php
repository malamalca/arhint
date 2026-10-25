<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

/**
 * A warning, deadline or open question of the knowledge base.
 */
class KbItem
{
    public const SEVERITIES = ['high', 'medium', 'low'];

    /**
     * @param string $id Stable identifier, e.g. `w-7f3a`.
     * @param string $section Section the item belongs to.
     * @param string $text One-line text.
     * @param string|null $date Deadline date (Y-m-d), deadlines only.
     * @param string $severity One of SEVERITIES, warnings only.
     * @param string $status Status, see KbDocument::STATUSES.
     * @param array<int, string> $src References to the events the item comes from.
     */
    public function __construct(
        public string $id,
        public string $section,
        public string $text,
        public ?string $date = null,
        public string $severity = 'medium',
        public string $status = 'open',
        public array $src = [],
    ) {
    }

    /**
     * Whether the item still needs attention.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
