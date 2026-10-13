<?php
declare(strict_types=1);

namespace Projects\Lib;

use RuntimeException;

/**
 * The submitted form was rendered from a procedure state that has since changed.
 */
class StaleSnapshotException extends RuntimeException
{
}
