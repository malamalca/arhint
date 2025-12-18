<?php
declare(strict_types=1);

namespace Calendar;

use Cake\Core\BasePlugin;
use Cake\Event\EventManagerInterface;
use Cake\Routing\RouteBuilder;
use Calendar\Event\CalendarEvents;

/**
 * Plugin for Calendar
 */
class CalendarPlugin extends BasePlugin
{
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
            'Calendar',
            ['path' => '/calendar'],
            function (RouteBuilder $builder): void {
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
        $eventManager->on(new CalendarEvents());

        return $eventManager;
    }
}
