<?php
/**

 */
App::import('Controller', 'Lil.LilPlugin');
/**
 * LilCrmPluginController class
 *
 * @uses          LilPluginController
 */
class LilCrmPluginController extends LilPluginController {
	var $name = 'LilCrmPlugin';
/**
 * handlers property
 *
 * @var array
 * @access public
 */
	var $handlers = array(
		'after_construct_view'   => array('function' => '_addScripts', 'params' => array()),
		'admin_sidebar'   => array('function' => '_setAdminSidebar', 'params' => array()),
		'user_email'   => array('function' => '_getUserEmail', 'params' => array()),
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
		if ($this->request->params['plugin'] == 'lil_crm') {
			App::uses('HtmlHelper', 'View/Helper');
			$Html = new HtmlHelper($view);
			$view->addScript($Html->css('/lil_crm/css/lil_crm.css'));
		}
		
		return true;
	}
/**
 * _getUserEmail method
 *
 * Fetch default email for user
 *
 * @param mixed $model
 * @access public
 * @return void
 */
	function _getUserEmail($model, $user_id = null) {
		if (empty($user_id)) $user_id = $this->currentUser->get('id');
		
		if (!empty($user_id)) {
			$Email = ClassRegistry::init('Lil.ContactsEmail');
			return $Email->field('email', array(
				'contact_id' => $user_id,
				'primary' => 1
			));
		} else return false;
	}
/**
 * _setAdminSidebar method
 *
 * Add admin sidebar elements.
 *
 * @param mixed $controller
 * @access public
 * @return void
 */
	function _setAdminSidebar($controller, $sidebar) {
		$crm['title'] = __d('lil_crm', 'Costumers', true);
		$crm['visible'] = true;
		$crm['active'] = $this->request->params['plugin'] == 'lil_crm';
		$crm['url'] = array(
			'admin'      => true,
			'plugin'     => 'lil_crm',
			'controller' => 'contacts',
			'action'     => 'index',
		);
		
		$crm['items'] = array(
			'app_contacts' => array(
				'visible' => true,
				'title' => __d('lil_crm', 'Contacts'),
				'url'   => array(
					'plugin'     => 'lil_crm',
					'controller' => 'contacts',
					'action'     => 'index',
					'admin'      => true,
				),
				'params' => array(),
				'active' => in_array($this->request->params['controller'], array('contacts')) &&
							(empty($this->request->params['named']['kind']) ||
							strtoupper($this->request->params['named']['kind']) == 'T') &&
							in_array($this->request->params['action'], array('admin_add', 'admin_index', 'admin_edit', 'admin_view')),
				'expand' => false,
				'submenu' => array()
			),
			'app_companies' => array(
				'visible' => true,
				'title' => __d('lil_crm', 'Companies'),
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_crm',
					'controller' => 'contacts',
					'action'     => 'index',
					'kind'       => 'c'
				),
				'params' => array(),
				'active' => in_array($this->request->params['controller'], array('contacts')) &&
							!empty($this->request->params['named']['kind']) &&
							(strtoupper($this->request->params['named']['kind']) == 'C'),
				'expand' => false,
				'submenu' => array()
			)
		);
		
		// insert into sidebar right after welcome panel
		$this->sidebarInsertPanel($sidebar, array('after' => 'welcome'), array('crm' => $crm));
		
		return $sidebar;
	}
}