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

$conf = ConnectionManager::get('default')->config();
$db = Zend_Db::factory('PDO_MYSQL', [
    'host' => $conf['host'],
    'username' => $conf['username'],
    'password' => $conf['password'],
    'dbname' => $conf['database'],
]);
Syncroton_Registry::setDatabase($db);

if (Configure::read('debug')) {
    $writer = new Zend_Log_Writer_Stream(LOGS . 'syncroton.log');
    $writer->addFilter(new Zend_Log_Filter_Priority(Zend_Log::DEBUG));
    Syncroton_Registry::set('loggerBackend', new Zend_Log($writer));
}

Syncroton_Registry::setContactsDataClass('\LilCrm\Lib\ActiveSyncContacts');
Syncroton_Registry::setTasksDataClass('\Tasks\Lib\ActiveSyncTasks');
//Syncroton_Registry::setCalendarDataClass('Syncroton_Data_Calendar');
//Syncroton_Registry::setEmailDataClass('Syncroton_Data_Email');

$server = new Syncroton_Server($_SERVER['PHP_AUTH_USER']);

$server->handle();
