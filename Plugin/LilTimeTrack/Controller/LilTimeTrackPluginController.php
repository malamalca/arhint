<?php
/**
 * LilTimeTrackPluginController
 *
 * This is Lil Plugin for Time Tracking
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * LilTimeTrackPluginController class
 *
 * @uses          LilPluginController
 */
class LilTimeTrackPluginController extends LilPluginController {
/**
 * name property
 *
 * @var string
 */
	public $name = 'LilTimeTrackPlugin';
/**
 * handlers property
 *
 * @var array
 */
	public $handlers = array(
		'after_construct_view'     => array('function' => '_addScripts', 'params' => array()),
		'admin_sidebar'            => array('function' => '_setAdminSidebar', 'params' => array()),
	);
/**
 * _addScripts method
 *
 * Adds properties
 *
 * @param mixed $view
 * @return boolean
 */
	public function _addScripts($view) {
		if ($this->request->params['plugin'] == 'lil_time_track') {
			App::uses('HtmlHelper', 'View/Helper');
			$Html = new HtmlHelper($view);
			$view->addScript($Html->css('/lil_time_track/css/lil_time_track.css'));
			//$view->addScript($Html->script('/lil_tasks/js/lil_tasks.js'));
		}
		
		return true;
	}
/**
 * _setAdminSidebar method
 *
 * Add admin sidebar elements.
 *
 * @param mixed $controller
 * @param mixed $sidebar
 * @return array
 */
	public function _setAdminSidebar($controller, $sidebar) {
		App::uses('LilTimeTrackSidebar', 'LilTimeTrack.Lib');
		$tmtr = LilTimeTrackSidebar::generate($this->request);
		
		// insert into sidebar right after welcome panel
		$this->sidebarInsertPanel($sidebar, array('after' => 'welcome'), array('tmtr' => $tmtr));
		
		return $sidebar;
	}
}