<?php
declare(strict_types=1);

use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\TestSuite\ConnectionHelper;
use Migrations\TestSuite\Migrator;

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

require dirname(__DIR__) . '/config/bootstrap.php';

$_SERVER['PHP_SELF'] = '/';

if (empty($_SERVER['HTTP_HOST']) && !Configure::read('App.fullBaseUrl')) {
    Configure::write('App.fullBaseUrl', 'http://localhost');
}

// Fixate now to avoid one-second-leap-issues
Chronos::setTestNow(Chronos::now());

// Fixate sessionid early on, as php7.2+
// does not allow the sessionid to be set after stdout
// has been written to.
session_id('cli');

// Connection aliasing needs to happen before migrations are run.
// Otherwise, table objects inside migrations would use the default datasource
ConnectionHelper::addTestAliases();

define('USER_ADMIN', '048acacf-d87c-4088-a3a7-4bab30f6a040');
define('USER_COMMON', '048acacf-d87c-4088-a3a7-4bab30f6a041');
define('COMPANY_FIRST', '8155426d-2302-4fa5-97de-e33cefb9d704');

$migrator = new Migrator();
$migrator->runMany([
    ['connection' => 'test'],
    ['plugin' => 'Crm'],
    ['plugin' => 'Documents'],
    ['plugin' => 'Expenses'],
    ['plugin' => 'Projects'],
    ['plugin' => 'Tasks'],
    ['plugin' => 'Calendar'],
]);
