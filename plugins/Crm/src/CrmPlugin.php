<?php
declare(strict_types=1);

namespace Crm;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManagerInterface;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Hash;
use Crm\Event\CrmEvents;

class CrmPlugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        Configure::load('Crm.config');

        $defaults = require CONFIG . 'app_local.php';
        if (isset($defaults['Crm'])) {
            Configure::write(
                'Crm',
                Hash::merge((array)Configure::read('Crm'), (array)$defaults['Crm']),
            );
        }
    }

    /**
     * Add routes for the plugin.
     *
     * If your plugin has many routes and you would like to isolate them into a separate file,
     * you can create `$plugin/config/routes.php` and delete this method.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'Crm',
            ['path' => '/crm'],
            function (RouteBuilder $builder): void {
                // Add custom routes here
                $builder->fallbacks();
            },
        );
        parent::routes($routes);
    }

    /**
     * Register custom event listeners here
     *
     * @param \Cake\Event\EventManagerInterface $eventManager
     * @return \Cake\Event\EventManagerInterface
     * @link https://book.cakephp.org/5/en/core-libraries/events.html#registering-listeners
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        $eventManager->on(new CrmEvents());

        return $eventManager;
    }
}
