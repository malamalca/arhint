<?php
use Cake\Routing\Router;

Router::plugin('LilTasks', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});