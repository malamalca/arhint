<?php
/**
 * Activesync server file
 */

use Cake\Auth\PasswordHasherFactory;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Syncroton"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Please authenticate!';
    exit;
}

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__DIR__) . '/vendor/zendframework/zendframework1/library');


// Use composer to load the autoloader.
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

$Hasher = PasswordHasherFactory::build(['className' => 'Default', 'hashType' => PASSWORD_BCRYPT, 'hashOptions' => ['salt' => Security::getSalt()]]);

$Users = TableRegistry::getTableLocator()->get('Users');
$user = $Users->find()
    ->select()
    ->where(['username' => $_SERVER['PHP_AUTH_USER']])
    ->first();

if (!$user) {
    header('HTTP/1.0 401 Unauthorized');
    echo 'Invalid user name!';
    exit;
}

if (!$Hasher->check($_SERVER['PHP_AUTH_PW'], $user->passwd)) {
    header('HTTP/1.0 401 Unauthorized');
    echo 'Invalid password!';
    exit;
}

Syncroton_Registry::set('user', $user);

function timezone_offset_string( $offset )
{
	return sprintf( "%s%02d:%02d", ( $offset >= 0 ) ? '+' : '-', abs( $offset / 3600 ), abs( $offset % 3600 ) );
}
$offset = timezone_offset_get(new DateTimeZone(Configure::read('App.defaultTimezone')), new DateTime());

$conf = ConnectionManager::get('default')->config();
$db = Zend_Db::factory('PDO_MYSQL', [
    'host' => $conf['host'],
    'username' => $conf['username'],
    'password' => $conf['password'],
    'dbname' => $conf['database'],
    'driver_options' => [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8;SET time_zone = "' . timezone_offset_string($offset) . '";',
    ],
]);
Syncroton_Registry::setDatabase($db);

if (Configure::read('debug')) {
    $writer = new Zend_Log_Writer_Stream(LOGS . 'syncroton.log');
    $writer->addFilter(new Zend_Log_Filter_Priority(Zend_Log::DEBUG));
    Syncroton_Registry::set('loggerBackend', new Zend_Log($writer));
}

Syncroton_Registry::setContactsDataClass('\Crm\Lib\ActiveSyncContacts');
Syncroton_Registry::setTasksDataClass('\Tasks\Lib\ActiveSyncTasks');
Syncroton_Registry::setCalendarDataClass('\Calendar\Lib\ActiveSyncCalendar');
//Syncroton_Registry::setEmailDataClass('Syncroton_Data_Email');

$server = new Syncroton_Server($_SERVER['PHP_AUTH_USER']);

$server->handle();
