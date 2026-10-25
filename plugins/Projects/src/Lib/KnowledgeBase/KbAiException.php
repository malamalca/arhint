<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use RuntimeException;

/**
 * The AI service cannot be reached, or it refused the request.
 */
class KbAiException extends RuntimeException
{
}
