<?php
declare(strict_types=1);

namespace App\Lib;

/**
 * Marker interface for entities that can describe themselves
 * to the AI assistant in a structured, concise format.
 *
 * Implement this interface on any entity returned by AI tools
 * to control which fields are exposed and how they are serialized.
 */
interface AISerializableInterface
{
    /**
     * Return an associative array representation suitable for AI tool results.
     * Values should be scalars, null, or arrays of AISerializableInterface objects.
     *
     * @return array<string, mixed>
     */
    public function toAIArray(): array;
}
