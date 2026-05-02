<?php
declare(strict_types=1);

namespace App\Lib;

class AITool
{
    /**
     * Constructor to initialize the AITool with its name, arguments, and description.
     *
     * @param array<mixed> $arguments
     */
    public function __construct(
        public string $name,
        public array $arguments,
        public string $description,
    ) {
    }
}
