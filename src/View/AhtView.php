<?php
declare(strict_types=1);

namespace App\View;

/**
 * A view class that is used for AHT responses.
 */
class AhtView extends AppView
{
    /**
     * The name of the layout file to render the view inside of. The name
     * specified is the filename of the layout in /templates/Layout without
     * the .php extension.
     *
     * @var string
     */
    public string $layout = 'ajax';

    /**
     * AHT views are located in the 'xml' sub directory for controllers' views.
     *
     * @var string
     */
    protected string $subDir = 'aht';

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
    }
}
