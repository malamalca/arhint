<?php

spl_autoload_register(function ($class_name) {
	$path = explode('_', $class_name);
	if (!empty($path[0]) && $path[0] == 'Syncroton') {
		unset($path[0]);
		$file_name = App::pluginPath('LilActiveSync') . implode(DS, $path) . '.php';
		if (file_exists($file_name)) {
			require_once $file_name;
		}
	}
	
	if (!empty($path[0]) && $path[0] == 'Zend' && sizeof($path) > 1) {
		$file_name = 'D:/xampp/htdocs/forbidden/zend1/library/' . implode(DS, $path) . '.php';
//		if (file_exists($file_name)) {
			require_once $file_name;
//		}
	}
});

/**
 * Syncroton
 *
 * Example server file
 *
 * @package     doc
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Syncroton"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Please authenticate!';
    exit;
}

/**
 * Use the DS to separate the directories in other defines
 */
	if (!defined('DS')) {
		define('DS', DIRECTORY_SEPARATOR);
	}
/**
 * Include local path configuration file which must not be commited to repository
 */
	$local_paths = dirname(__FILE__) . DS . 'paths.php';
	if (file_exists($local_paths)) include $local_paths;
/**
 * These defines should only be edited if you have cake installed in
 * a directory layout other than the way it is distributed.
 * When using custom settings be sure to use the DS and do not add a trailing DS.
 */

/**
 * The full path to the directory which holds "app", WITHOUT a trailing DS.
 *
 */
if (!defined('ROOT')) {
	define('ROOT', dirname(dirname(dirname(__FILE__))));
}
/**
 * The actual directory name for the "app".
 *
 */
if (!defined('APP_DIR')) {
	define('APP_DIR', basename(dirname(dirname(__FILE__))));
}

/**
 * The absolute path to the "cake" directory, WITHOUT a trailing DS.
 *
 * Un-comment this line to specify a fixed path to CakePHP.
 * This should point at the directory containing `Cake`.
 *
 * For ease of development CakePHP uses PHP's include_path.  If you
 * cannot modify your include_path set this value.
 *
 * Leaving this constant undefined will result in it being defined in Cake/bootstrap.php
 */
	//define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'lib');

/**
 * Editing below this line should NOT be necessary.
 * Change at your own risk.
 *
 */
if (!defined('WEBROOT_DIR')) {
	define('WEBROOT_DIR', basename(dirname(__FILE__)));
}
if (!defined('WWW_ROOT')) {
	define('WWW_ROOT', dirname(__FILE__) . DS);
}

if (!defined('CAKE_CORE_INCLUDE_PATH')) {
	if (function_exists('ini_set')) {
		ini_set('include_path', ROOT . DS . 'lib' . PATH_SEPARATOR . ini_get('include_path'));
	}
	if (!include ('Cake' . DS . 'bootstrap.php')) {
		$failed = true;
	}
} else {
	if (!include (CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'bootstrap.php')) {
		$failed = true;
	}
}
if (!empty($failed)) {
	trigger_error("CakePHP core could not be found.  Check the value of CAKE_CORE_INCLUDE_PATH in APP/webroot/index.php.  It should point to the directory containing your " . DS . "cake core directory and your " . DS . "vendors root directory.", E_USER_ERROR);
}


App::uses('ConnectionManager', 'Model');
Syncroton_Registry::setDatabase(ConnectionManager::getDataSource('default'));
Syncroton_Registry::set('loggerBackend', new Syncroton_Log());
Syncroton_Registry::setContactsDataClass('Syncroton_Data_Contacts');
Syncroton_Registry::setCalendarDataClass('Syncroton_Data_Calendar');
Syncroton_Registry::setTasksDataClass('Syncroton_Data_Tasks');
Syncroton_Registry::setEmailDataClass('Syncroton_Data_Email');
Syncroton_Registry::setNotesDataClass('Syncroton_Data_Notes');

$server = new Syncroton_Server($_SERVER['PHP_AUTH_USER']);

$server->handle();