<?php
/**
 * This file is loaded automatically by the app/webroot/index.php file after core.php
 *
 * This file should load/create any application wide configuration settings, such as 
 * Caching, Logging, loading additional configuration files.
 *
 * You should also use this file to include any files that provide global functions/constants
 * that your application uses.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
// Setup a 'default' cache configuration for use in the application.
Cache::config('default', array('engine' => 'File'));

CakePlugin::load('Lil', array('bootstrap' => array('core'), 'routes' => true));
foreach ($plugins = Configure::read('Lil.plugins') as $plugin) {
	CakePlugin::load('Lil' . $plugin, array('bootstrap' => array('core'), 'routes' => true));
}
CakePlugin::load('Arhim', array('routes' => true));

Configure::write('datepickerFormat', 'yy-mm-dd');
Configure::write('Lil.languages', array('eng', 'slv'));

Configure::write('Lil.areasTable',       'projects');
Configure::write('Lil.usersTable',       'contacts');
Configure::write('Lil.areasUsersTable',  'projects_users');
Configure::write('Lil.userDisplayField', 'title');
Configure::write('Lil.areaSlugField',    'slug');

Configure::write('Lil.userAssociation', array(
	'className'             => 'Lil.User',
	'foreignKey'            => 'user_id',
	'associationForeignKey' => 'project_id',
	'joinTable'             => 'projects_users',
));
/**
 * User config
 */
$user_config = dirname(__FILE__) . DS . 'config.php';
if (file_exists($user_config)) {
	include $user_config;
}