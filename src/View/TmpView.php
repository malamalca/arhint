<?php
declare(strict_types=1);

namespace App\View;

use Generator;

/**
 * A view class that is used for layouts from variable
 */
class TmpView extends AppView
{
    public string $layoutPath = '';

    /**
     * Get an iterator for layout paths.
     *
     * @param string|null $plugin The plugin to fetch paths for.
     * @return \Generator
     */
    protected function getLayoutPaths(?string $plugin): Generator
    {
        yield TMP;
    }

    /**
     * Fetch the content for a block. If a block is
     * empty or undefined '' will be returned.
     *
     * @param string $name Name of the block
     * @param string $default Default text
     * @return string The block content or $default if the block does not exist.
     * @see \Cake\View\ViewBlock::get()
     */
    public function fetch(string $name, string $default = ''): string
    {
        $varValue = $this->get('_' . $name . 'Block');
        if (is_string($varValue)) {
            return $varValue;
        } else {
            return $this->Blocks->get($name, $default);
        }
    }
}
