<?php
/**
 * Arhim: The Architectural practice
 *
 * This is Lil plugin file
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */

App::uses('LilPlugin', 'Lil.Controller');
/**
 * AppPluginController class
 *
 * @uses          LilPluginController
 */
class AppPluginController extends LilPluginController {
/**
 * name property
 *
 * @var string
 */
	public $name = 'AppPlugin';
/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Html', 'Form', 'Time', 'Text', 'Lil.Lil', 'Lil.LilFloat', 'Lil.LilDate', 'Lil.LilForm');
/**
 * components property
 *
 * @var array
 */
	public $components = array('Session', 'Auth');
/**
 * handlers property
 *
 * @var array
 */
	public $handlers = array(
		'after_construct_view' => array('function' => '_addScripts', 'params' => array()),
		'before_construct_model' => array('function' => '_beforeConstructModel', 'params' => array()),
		'before_save_model' => array('function' => '_savePassword', 'params' => array()),
		
		'admin_sidebar'   => array('function' => '_setAdminSidebar', 'params' => array()),
		'admin_dashboard' => array('function' => '_modifyDashboard', 'params' => array()),
		
		'form_edit_area' => array('function' => '_areaForm', 'params' => array()),
		'form_add_area' => array('function' => '_areaForm', 'params' => array()),
		'admin_index_areas' => array('function' => '_areaIndex', 'params' => array()),
		
		'before_find_model' => array('function' => '_filterUsers', 'params' => array()),
		'before_area_find_for_user' => array('function' => '_filterAreas', 'params' => array()),
		'before_area_admin_list_params' => array('function' => '_filterAreas', 'params' => array()),
		'before_area_admin_index_params' => array('function' => '_filterAreasIndex', 'params' => array()),
	);
/**
 * _beforeConstructModel method
 *
 * Filter users
 *
 * @param mixed $model
 * @param array $data
 * @return bool
 */
	public function _beforeConstructModel($model, $data) {
		if ($model->name == 'User') {
			$model->belongsTo['Company'] = array(
				'className' => 'Contact',
				'foreignKey' => 'company_id'
			);
		}
		if ($model->name == 'Area') {
			$model->validate['username'] = array(
				'rule'       => 'isUnique',
				'allowEmpty' => true,
				'message'    => __('Username must be unique.')
			);
		}
		return true;
	}
/**
 * _filterUsers method
 *
 * Filter users
 *
 * @param mixed $model
 * @param array $queryData
 * @return array
 */
	public function _filterUsers($model, $queryData) {
		if ($model->name == 'User' && empty($queryData['conditions']['User.id'])) {
			$queryData['conditions']['NOT'] = array($model->escapeField('username') => null);
		}
		return $queryData;
	}
/**
 * _filterAreas method
 *
 * Filter areas
 *
 * @param mixed $modelOrController
 * @param mixed $params
 * @return array
 */
	public function _filterAreas($modelOrController, $params) {
		if (get_parent_class($modelOrController) == 'LilAppController') {
			$Area = ClassRegistry::init('Lil.Area');
		} else {
			$Area = $modelOrController;
		}
		
		$params['params']['conditions'][$Area->escapeField('active')] = true;
		return $params;
	}
/**
 * _filterAreasIndex method
 *
 * Add an ability to filter areas in admin/areas/index
 *
 * @param mixed $modelOrController
 * @param mixed $params
 * @return array
 */
	public function _filterAreasIndex($controller, $params) {
		$filter = array();
		if (!empty($controller->request->query['filter'])) $filter = $controller->request->query['filter'];
		$Area = ClassRegistry::init('Lil.Area');
		
		if (isset($filter['active'])) {
			$params['params']['conditions'][$Area->escapeField('active')] = (bool)$filter['active'];
		}
		
		return $params;
	}
/**
 * _addScripts method
 *
 * Adds properties
 *
 * @param mixed $view
 * @return boolean
 */
	public function _addScripts($view) {
		App::uses('HtmlHelper', 'View/Helper');
		$Html = new HtmlHelper($view);
		
		$view->addScript($Html->css('arhim.css'));
		$view->addScript($Html->css('table.jui'));
		
		return true;
	}
/**
 * _areaForm method
 *
 * Add fields to area form
 *
 * @param mixed $view
 * @param mixed $form
 * @return array
 */
	public function _areaForm($view, $form) {
		$no = array(
			'no' => array(
				'class'      => $view->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'no',
					'options' => array(
						'label' => __('Project no') . ':',
					)
				)
			)
		);
		
		$active = array(
			'pph' => array(
				'class'      => $view->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'active',
					'options' => array(
						'type' => 'checkbox',
						'label' => __('This is an active project'),
					)
				)
			)
		);
		
		$inputs = array(
			'fieldset' => sprintf('<fieldset><legend>%s</legend>', __('Login')),
			'username' => array(
				'class'      => $view->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'username',
					'options' => array(
						'label' => __('Username') . ':',
					)
				)
			),
			
			'passwd' => array(
				'class'      => $view->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'passwd',
					'options' => array(
						'type'  => 'password',
						'label' => __('Password') . ':',
						'value' => ''
					)
				)
			),
			'fieldset_end' => '</fieldset>',
		);
		
		$this->insertIntoArray(
			$form['form']['lines'],
			$no,
			array('before' => 'name')
		);
		
		$this->insertIntoArray(
			$form['form']['lines'],
			$active,
			array('after' => 'description')
		);
		
		$this->insertIntoArray(
			$form['form']['lines'],
			$inputs,
			array('before' => 'submit')
		);
		
		return $form;
	}
/**
 * _savePassword method
 *
 * Changes or unsets password for project
 *
 * @param mixed $model
 * @param mixed $data
 * @return array
 */
	public function _savePassword($model, $data) {
		if ($model->name == 'Area') {
			if (isset($data['data']['Area']['passwd'])) {
				if (empty($data['data']['Area']['passwd'])) {
					unset($data['data']['Area']['passwd']);
				} else {
					$data['data']['Area']['passwd'] = Security::hash($data['data']['Area']['passwd'], null, true);
				}
			}
		}
		return $data;
	}
/**
 * _areaIndex method
 *
 * Add fields to area form
 *
 * @param mixed $view
 * @param mixed $index
 * @return array
 */
	public function _areaIndex($view, $index) {
		$filter = array();
		if (!empty($this->request->query['filter'])) $filter = $this->request->query['filter'];
		$view->set(compact('filter'));
		
		App::uses('Lil', 'Lil.View/Helper'); $Lil = new LilHelper($view);
		$index['table']['element']['head']['rows'][0]['columns']['id']['html'] = __('No');
		
		foreach ($index['table']['element']['body']['rows'] as &$row) {
			$row['columns']['id']['html'] = $row['data']['Area']['no'];
			if (!$row['data']['Area']['active']) {
				$row['parameters']['class'] = 'striked';
			}
		}
		return $index;
	}
/**
 * _setAdminSidebar method
 *
 * Add dashboard panel with latest expenses
 *
 * @param mixed $view
 * @param mixed $dashboard
 * @return array
 */
	public function _setAdminSidebar($view, $sidebar) {
		$sidebar['admin']['items']['areas']['title'] = __('Projects');
		$sidebar['admin']['items']['areas']['url'] = array(
			'admin'      => true,
			'plugin'     => 'lil',
			'controller' => 'areas',
			'action'     => 'index',
			'?' => array('filter' => array('active' => true))
		);
		$sidebar['admin']['items']['areas']['active'] = false;
		$sidebar['admin']['items']['areas']['expand'] = ($this->request->params['plugin']=='lil') &&
					in_array($this->request->params['controller'], array('areas'));
		
		$sidebar['admin']['items']['areas']['submenu'] = array(
			'active' => array(
				'visible' => true,
				'title' => __('Active', true),
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil',
					'controller' => 'areas',
					'action'     => 'index',
					'?' => array('filter' => array('active' => true))
				),
				'active' => !empty($this->params->query['filter']['active'])
			),
			'archive' => array(
				'visible' => true,
				'title' => __('Archived', true),
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil',
					'controller' => 'areas',
					'action'     => 'index',
					'?' => array('filter' => array('active' => false))
				),
				'active' => empty($this->params->query['filter']['active'])
			)
		);
		
		return $sidebar;
	}
/**
 * _modifyDashboard method
 *
 * Add dashboard panel with latest expenses
 *
 * @param mixed $view
 * @param mixed $dashboard
 * @return array
 */
	public function _modifyDashboard($view, $dashboard) {
		unset($dashboard['panels']['welcome']);
		
		$dashboard['head_for_layout'] = false;
		return $dashboard;
	}
}