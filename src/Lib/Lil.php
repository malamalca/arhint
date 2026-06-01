<?php
declare(strict_types=1);

namespace App\Lib;

use ArrayAccess;
use ArrayObject;

class Lil
{
    /**
     * InsertIntoArray method
     *
     * Insert a new element into array
     *
     * @param \ArrayAccess|array<string, mixed> $input Destination for insert operation.
     * @param array<string, mixed> $element Element to be inserted.
     * @param array<string, mixed> $options Insert options.
     * @return void
     */
    public static function insertIntoArray(array|ArrayAccess &$input, array $element, array $options = []): void
    {
        // Normalize to plain array for manipulation; track if we need to export back.
        $isArrayObject = ($input instanceof ArrayObject);
        /** @var array<string, mixed> $dest */
        $dest = $isArrayObject ? $input->getArrayCopy() : $input;

        if (isset($options['after']) || isset($options['replace'])) {
            $title = $options['after'] ?? $options['replace'];

            $panels = array_keys($dest);

            $i = 0;
            $panelCount = count($dest);
            for ($i = 0; $i < $panelCount; $i++) {
                if ($panels[$i] == $title) {
                    break;
                }
            }

            if (isset($panels[$i]) && $panels[$i] == $title) {
                if (isset($options['replace'])) {
                    unset($dest[$title]);
                    $i--;
                }

                if (isset($options['preserve']) && $options['preserve'] === false) {
                    $part1 = array_slice($dest, 0, $i + 1, true);
                    foreach ($element as $elk => $elv) {
                        if (is_numeric($elk)) {
                            $part1[] = $elv;
                        } else {
                            $part1[$elk] = $elv;
                        }
                    }
                    $dest = array_merge(
                        $part1,
                        array_slice(
                            $dest,
                            $i + 1,
                            count($dest) - $i,
                            true,
                        ),
                    );
                } else {
                    // do this to preserve array keys
                    $dest
                        = array_slice($dest, 0, $i + 1, true) +
                        $element +
                        array_slice($dest, $i + 1, count($dest) - $i, true);
                }
            }
        } elseif (isset($options['before'])) {
            $panels = array_keys($dest);
            $i = 0;
            $destCount = count($dest);

            for ($i = 0; $i < $destCount; $i++) {
                if ($panels[$i] == $options['before']) {
                    break;
                }
            }

            if (isset($panels[$i]) && $panels[$i] == $options['before']) {
                if (isset($options['preserve']) && $options['preserve'] === false) {
                    $part1 = array_slice($dest, 0, $i, true);

                    foreach ($element as $elk => $elv) {
                        if (is_numeric($elk)) {
                            $part1[] = $elv;
                        } else {
                            $part1[$elk] = $elv;
                        }
                    }

                    $dest = array_merge(
                        $part1,
                        array_slice(
                            $dest,
                            $i,
                            count($dest) - $i,
                            true,
                        ),
                    );
                } else {
                    // do this to preserve array keys
                    $dest = array_slice($dest, 0, $i, true) +
                        $element +
                        array_slice($dest, $i, count($dest) - $i, true);
                }
            }
        } else {
            $dest = $dest + $element;
        }

        // Export back to ArrayObject if needed; otherwise write directly.
        if ($isArrayObject && $input instanceof ArrayObject) {
            $input->exchangeArray($dest);
        } else {
            $input = $dest;
        }
    }
}
