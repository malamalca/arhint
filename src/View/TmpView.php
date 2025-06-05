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
     * Set path for layout files.
     *
     * @param string $path Path for layout files.
     * @return $this
     */
    public function setLayoutPath(string $path)
    {
        //$this->layoutPath = $path;

        return $this;
    }

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
}
