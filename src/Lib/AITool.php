<?php
declare(strict_types=1);

namespace App\Lib;

class AITool
{
    /**
     * Constructor to initialize the AITool with its name, arguments, and description.
     *
     * @param string $name Tool name, "Module.action".
     * @param array<mixed> $arguments Argument schemas.
     * @param string $description What the tool does and how to use it.
     * @param string|null $summary One sentence for the tool catalog; defaults to the first sentence of
     *   the description. Set it when that sentence does not tell the tool apart from similar tools.
     */
    public function __construct(
        public string $name,
        public array $arguments,
        public string $description,
        public ?string $summary = null,
    ) {
    }
}
