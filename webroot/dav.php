<?php

use App\Lib\DAV\ArhintBasicAuth;
use App\Lib\DAV\ArhintPrincipalBackend;
use Cake\Core\Configure;
use Crm\Lib\SabreDAVSyncContacts;
use Sabre\DAV;
use Sabre\CalDAV;
use Sabre\DAVACL;

//Mapping PHP errors to exceptions
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

// Files we need
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

// Backends
$authBackend = new ArhintBasicAuth();
$principalBackend = new ArhintPrincipalBackend();
$addressBookRoot = new SabreDAVSyncContacts();
//$calendarBackend = new CalDAV\Backend\PDO($pdo);

// Directory tree
$tree = [
    new DAVACL\PrincipalCollection($principalBackend),
    new Sabre\CardDAV\AddressBookRoot($principalBackend, $addressBookRoot)
//    new CalDAV\CalendarRoot($principalBackend, $calendarBackend)
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
//$caldavPlugin = new CalDAV\Plugin();
//$server->addPlugin($caldavPlugin);

// CardDAV plugin
$carddavPlugin = new \Sabre\CardDAV\Plugin();
$server->addPlugin($carddavPlugin);

// ACL plugin
$aclPlugin = new DAVACL\Plugin();
$server->addPlugin($aclPlugin);

// Support for html frontend
$browser = new DAV\Browser\Plugin();
$server->addPlugin($browser);

// And off we go!
$server->exec();