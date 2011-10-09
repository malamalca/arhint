<?php
/**
 * LilTasksPluginController
 *
 * This is Lil Plugin for Tasks
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * LilTasksPluginController class
 *
 * @uses          LilPluginController
 */
class LilTasksPluginController extends LilPluginController {
/**
 * name property
 *
 * @var string
 */
	public $name = 'LilTasksPlugin';
/**
 * components property
 *
 * @var array
 */
	var $components = array('Session', 'Cookie', 'Email');
/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Html', 'Form', 'Time', 'Text', 'Lil.Lil', 'Lil.LilFloat', 'Lil.LilDate');
/**
 * handlers property
 *
 * @var array
 */
	public $handlers = array(
		'before_construct_model'   => array('function' => '_beforeConstructModel', 'params' => array()),
		
		'after_construct_view'     => array('function' => '_addScripts', 'params' => array()),
		'admin_sidebar'            => array('function' => '_setAdminSidebar', 'params' => array()),
		'admin_dashboard'          => array('function' => '_modifyDashboard', 'params' => array()),
		'admin_main_menu'          => array('function' => '_modifyMainMenu', 'params' => array()),
		
		'invoice_before_save'      => array('function' => '_beforeSaveInvoice', 'params' => array()),
		'travel_order_before_save' => array('function' => '_beforeSaveTravelOrder', 'params' => array()),
		
		'form_edit_invoice'        => array('function' => '_modifyInvoiceForm', 'params' => array()),
		'form_edit_travel_order'   => array('function' => '_modifyTravelOrderForm', 'params' => array()),
		
		'heartbeat_daily'          => array('function' => '_HeartbeatDaily', 'params' => array()),
		'heartbeat_10min'          => array('function' => '_Heartbeat10min', 'params' => array()),
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
	public function _beforeConstructModel($model) {
		if ($model->name == 'Invoice') {
			$model->hasOne['Task'] = array(
				'className' => 'LilTasks.Task',
				'foreignKey' => 'foreign_id',
				'conditions'   => array('Task.model' => 'Invoice'),
			);
		}
		if ($model->name == 'TravelOrder') {
			$model->hasOne['Task'] = array(
				'className' => 'LilTasks.Task',
				'foreignKey' => 'foreign_id',
				'conditions'   => array('Task.model' => 'TravelOrder'),
			);
		}
		return true;
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
		if ($this->request->params['plugin'] == 'lil_tasks' || (
			$this->request->params['plugin'] == 'lil' && $this->request->params['action'] == 'admin_dashboard'
		)) {
			App::uses('HtmlHelper', 'View/Helper');
			$Html = new HtmlHelper($view);
			$view->addScript($Html->css('/lil_tasks/css/lil_tasks.css'));
			$view->addScript($Html->script('/lil_tasks/js/lil_tasks.js'));
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
		App::uses('LilTasksSidebar', 'LilTasks.Lib');
		$tasks = LilTasksSidebar::generate($this->request);
		
		// insert into sidebar right after welcome panel
		$this->sidebarInsertPanel($sidebar, array('after' => 'welcome'), array('tasks' => $tasks));
		
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
		$this->autoRender = false;
		$this->autoLayout = false;
		
		$Task = ClassRegistry::init('LilTasks.Task');
		if (!empty($this->request->query['filter'])) $filter = $this->request->query['filter'];
		if (!isset($filter['date']) && !$filter['date'] = $this->Cookie->read('lil_tasks_index')) {
			$filter['date'] = strftime('%Y-%m-%d');
		}
		$date = $filter['date'];
		$date_next = strftime('%Y-%m-%d', strtotime($date)+24*60*60);
		$date_prev = strftime('%Y-%m-%d', strtotime($date)-24*60*60);
		
		$params = array_merge(
			array('order' => 'Task.completed, Task.modified DESC'),
			$Task->filter($filter)
		);
		$tasks = $Task->find('all', $params);
		$view->set(compact('tasks', 'filter', 'date', 'date_next', 'date_prev'));
		
		
		$tsk = array(
			'params' => array('class' => 'no-margin'),
			'html' => $view->element('tasks_admin_index', array(), array('plugin' => 'LilTasks'))
		);
		$dashboard['panels']['tasks'] = $tsk;
		
		// modify main menu
		$dashboard['menu']['lil_tasks_add'] =  array(
			'title' => __d('lil_tasks', 'Add Task', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_tasks',
				'controller' => 'tasks',
				'action'     => 'add',
			),
			'params' => array(
				'onclick' => sprintf(
					'popup("%s", $(this).attr("href"), 580); return false;',
					__d('lil_expenses', 'Add Task')
				)
			)
		);
		
		return $dashboard;
	}

/**
 * _beforeSaveInvoice method
 *
 * Update expense
 *
 * @param mixed $controller
 * @param array $data
 * @return array
 */
	public function _beforeSaveInvoice($controller, $data) {
		return $this->__beforeSaveAssociated($data, 'Invoice', 'dat_expire');
	}
/**
 * _beforeSaveTravelOrder method
 *
 * Update expense
 *
 * @param mixed $controller
 * @param array $data
 * @return array
 */
	public function _beforeSaveTravelOrder($controller, $data) {
		return $this->__beforeSaveAssociated($data, 'TravelOrder', 'dat_order');
	}
/**
 * __beforeSaveAssociated method
 *
 * Helper function for upper two methods
 *
 * @param array $data
 * @return array
 */
	private function __beforeSaveAssociated($data, $model_name, $date_field) {
		// remove empty task
		if (isset($data['data']['Task'])) {
			if (empty($data['data']['Task']['exists'])) {
				// delete task
				if (!empty($data['data']['Task']['id'])) {
					$data['items_to_delete']['Task'] = $data['data']['Task']['id'];
				}
				unset($data['data']['Task']);
			} else {
				if (!empty($data['data'][$model_name]['project_id'])) {
					$data['data']['Task']['project_id'] = $data['data'][$model_name]['project_id'];
				}
				if (empty($data['data']['Task']['deadline'])) {
					$data['data']['Task']['deadline'] = $data['data'][$model_name][$date_field];
				}
				if (empty($data['data']['Task']['title'])) {
					$data['data']['Task']['title'] = $data['data'][$model_name]['title'];
				}
			}
		}
		return $data;
	}
/**
 * _modifyInvoiceForm method
 *
 * Adds fields to invoice form
 *
 * @param mixed $view
 * @param mixed $form
 * @return array
 */
	public function _modifyInvoiceForm($view, $form) {
		return $this->__injectForm($view, $form, 'Invoice');
	}
/**
 * _modifyInvoiceForm method
 *
 * Adds fields to travel order form
 *
 * @param mixed $view
 * @param mixed $form
 * @return array
 */
	public function _modifyTravelOrderForm($view, $form) {
		return $this->__injectForm($view, $form, 'TravelOrder');
	}
/**
 * __injectForm method
 *
 * Helper function for upper two methods
 *
 * @param array $view
 * @param array $form
 * @param string $model_name
 * @return array
 */
	function __injectForm($view, $form, $model_name) {
		App::uses('LilTasksFormInject', 'LilTasks.Lib');
		$tasks = LilTasksFormInject::generate($view, $model_name);
		
		foreach ($tasks['javascript'] as $js_line) $view->Lil->jsReady($js_line);
		unset($tasks['javascript']);
		
		$this->insertIntoArray($form['form']['lines'], $tasks, array('before' => 'submit'));
		
		return $form;
	}
/**
 * _Heartbeat method
 *
 * Daily periodic rotine
 *
 * @param mixed $console
 * @return array
 */
	function _HeartbeatDaily($console) {
		$this->__HeartbeatNotifications($console);
	}
	
	function _Heartbeat10min($console) {
		$this->__HeartbeatImport($console);
	}
	
	function __HeartbeatImport($console)
	{
		App::uses('LilTasksParseEmail', 'LilTasks.Lib');
		if ($emails = LilTasksParseEmail::fetch(array(
			//'server' => 'www.nahtigal.com',
			//'port' => 110,
			//'settings' => array('pop3', 'novalidate-cert'),
			//'user' => 'info@malamalca.com',
			//'pass' => 'vuego3869'
			
			'server' => 'pop.gmail.com',
			'port' => 995,
			'settings' => array('imap', 'ssl', 'novalidate-cert'),
			'user' => 'tmn@arhim.si',
			'pass' => 'miha3869'
		))) {
			$Task = ClassRegistry::init('LilTasks.Task');
			$Task->TasksAttachment->setSafeUpload(false);
			foreach ($emails as $data) {
				$Task->create();
				$Task->saveAll($data);
			}
		}
	}
	
	function __HeartbeatNotifications($console) {
		$User = ClassRegistry::init('Lil.User');
		$User->bindModel(
			array('hasOne' => array(
				'PrimaryEmail' => array(
					'className'  => 'LilCrm.ContactsEmail',
					'type'       => 'INNER',
					'foreignKey' => 'contact_id'
				)
			)), false
		);
		$users = $User->find('all', array(
			'contain' => 'PrimaryEmail',
		));
		
		if (!empty($users)) {
			App::uses('CakeEmail', 'Network/Email');
			$email = new CakeEmail();
			$email->template('LilTasks.notification_daily');
			$email->from(Configure::read('LilTasks.from.email'), Configure::read('LilTasks.from.name'));
			$email->subject(__d('lil_tasks', 'Daily notification'));
			
			App::uses('LilDateEngine', 'Lil.Lib');
			$LilDate = LilDateEngine::getInstance();
			$Task = ClassRegistry::init('LilTasks.Task');
			
			foreach ($users as $user) {
				if ($tasks = $Task->find('all', array(
					'conditions' => array(
						'Task.completed'   => null,
						'Task.deadline <=' => strftime('%Y-%m-%d'),
						'Task.user_id'     => $user['User']['id']
					),
					'order' => array('Task.completed', 'Task.deadline DESC', 'Task.modified DESC'),
					'recursive' => -1
				))) {
					if ($LilDate->isToday($tasks[0]['Task']['deadline'])) {
						$email->to($user['PrimaryEmail']['email']);
						$email->viewVars(compact('tasks'));
						if ($email->send()) {
							$console->out($user['PrimaryEmail']['email']);
						} else {
							$console->out('Error sending email');
						}
					}
				}
			}
		}
	}
}