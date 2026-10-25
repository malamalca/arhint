<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use RuntimeException;

/**
 * The knowledge base was changed since the version the caller worked with was read.
 */
class StaleKnowledgeBaseException extends RuntimeException
{
}
