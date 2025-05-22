<?php
declare(strict_types=1);

namespace Projects;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Hash;
use Projects\Event\ProjectsEvents;

class Plugin extends BasePlugin
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
        Configure::load('Projects.config');

        $defaults = require CONFIG . 'app_local.php';
        if (isset($defaults['Projects'])) {
            Configure::write(
                'Projects',
                Hash::merge((array)Configure::read('Projects'), (array)$defaults['Projects']),
            );
        }

        $ProjectsEvents = new ProjectsEvents();
        EventManager::instance()->on($ProjectsEvents);
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
            'Projects',
            ['path' => '/projects'],
            function (RouteBuilder $builder): void {
                // Add custom routes here
                $builder->fallbacks();
            },
        );
        parent::routes($routes);
    }

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Add your middlewares here
        return $middlewareQueue;
    }
}
