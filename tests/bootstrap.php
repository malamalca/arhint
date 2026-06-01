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
define('BOOKING_ORDER_1', '00000001-0000-0000-0000-000000000001');
define('BOOKING_ORDER_2', '00000002-0000-0000-0000-000000000002');
define('BOOKING_ORDER_ENTRY_1', '00000001-0000-0001-0000-000000000001');
define('BOOKING_ORDER_ENTRY_2', '00000002-0000-0001-0000-000000000002');
define('PARTNER_1', '00000001-0000-0002-0000-000000000001');
define('PARTNER_2', '00000002-0000-0002-0000-000000000002');
define('BANK_STATEMENT_1', '00000001-0000-0003-0000-000000000001');
define('BANK_STATEMENT_2', '00000002-0000-0003-0000-000000000002');
define('BANK_STATEMENT_ENTRY_1', '00000001-0000-0004-0000-000000000001');
define('BANK_STATEMENT_ENTRY_2', '00000002-0000-0004-0000-000000000002');
define('BOOKING_RULE_1', '00000001-0000-0005-0000-000000000001');
define('BOOKING_RULE_2', '00000002-0000-0005-0000-000000000002');
define('BOOKING_RULE_FILTER_1', '00000001-0000-0006-0000-000000000001');
define('BOOKING_RULE_FILTER_2', '00000002-0000-0006-0000-000000000002');
define('BOOKING_RULE_FILTER_3', '00000003-0000-0006-0000-000000000003');
define('BOOKING_RULE_ACCOUNT_ENTRY_1', '00000001-0000-0007-0000-000000000001');
define('BOOKING_RULE_ACCOUNT_ENTRY_2', '00000002-0000-0007-0000-000000000002');

// Load Crm plugin configuration so table validators and callbacks work correctly in tests.
Configure::write(require dirname(__DIR__) . '/plugins/Crm/config/config.php');

// Use a separate ChromaDB collection for tests to avoid polluting production data.
Configure::write('VectorDB.collection', 'events_test');

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
