<?php
declare(strict_types=1);

use App\Lib\DAV\ArhintBasicAuth;
use App\Lib\DAV\ArhintPrincipalBackend;
use Cake\Core\Configure;
use Calendar\Lib\SabreDAVSyncCalendars;
use Crm\Lib\SabreDAVSyncContacts;
use Sabre\CalDAV;
use Sabre\CalDAV\ICSExportPlugin;
use Sabre\CardDAV;
use Sabre\CardDAV\AddressBookRoot;
use Sabre\DAV;
use Sabre\DAVACL;

/**
 * Mapping PHP errors to exceptions.
 *
 * @param int    $errno   Error level
 * @param string $errstr  Error message
 * @param string $errfile File where error occurred
 * @param int    $errline Line number
 * @return void
 */
function exception_error_handler(int $errno, string $errstr, string $errfile, int $errline): void
{
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler('exception_error_handler');

// Files we need
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

// Backends
$authBackend = new ArhintBasicAuth();
$principalBackend = new ArhintPrincipalBackend();
$addressBookRoot = new SabreDAVSyncContacts();
$calendarBackend = new SabreDAVSyncCalendars();

// Directory tree
$tree = [
    new DAVACL\PrincipalCollection($principalBackend),
    new AddressBookRoot($principalBackend, $addressBookRoot),
    new CalDAV\CalendarRoot($principalBackend, $calendarBackend),
];

// The object tree needs in turn to be passed to the server class
$server = new DAV\Server($tree);

// You are highly encouraged to set your WebDAV server base url. Without it,
// SabreDAV will guess, but the guess is not always correct. Putting the
// server on the root of the domain will improve compatibility.
$server->setBaseUri(Configure::read('Dav.baseUrl'));

// Authentication plugin
$authPlugin = new DAV\Auth\Plugin($authBackend, 'ArhintDAV');
$server->addPlugin($authPlugin);

// CalDAV plugin
$caldavPlugin = new CalDAV\Plugin();
$server->addPlugin($caldavPlugin);

// http://arhint.localhost/dav/calendars/miha.nahtigal/default?export
$icsPlugin = new ICSExportPlugin();
$server->addPlugin($icsPlugin);

// CardDAV plugin
$carddavPlugin = new CardDAV\Plugin();
$server->addPlugin($carddavPlugin);

// ACL plugin
$aclPlugin = new DAVACL\Plugin();
$server->addPlugin($aclPlugin);

// Support for html frontend
$browser = new DAV\Browser\Plugin();
$server->addPlugin($browser);

// And off we go!
$server->exec();
