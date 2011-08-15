<?php
/**
 * LilInvoicesPluginController
 *
 * This is Lil Plugin for Invoices
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilPluginController', 'Lil.LilPluginController');
/**
 * LilInvoicesPluginController class
 *
 * @uses          LilPluginController
 */
class LilInvoicesPluginController extends LilPluginController {
/**
 * name property
 *
 * @var string
 */
	public $name = 'LilInvoicesPlugin';
/**
 * handlers property
 *
 * @var array
 */
	public $handlers = array(
		'after_construct_view'   => array('function' => '_addScripts', 'params' => array()),
		'admin_sidebar'          => array('function' => '_setAdminSidebar', 'params' => array()),
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
		if ($this->request->params['plugin'] == 'lil_invoices') {
			App::uses('HtmlHelper', 'View/Helper');
			$Html = new HtmlHelper($view);
			$view->addScript($Html->css('/lil_invoices/css/lil_invoices.css'));
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
		$invoices['title'] = __d('lil_invoices', 'Invoices');
		$invoices['visible'] = true;
		$invoices['active'] = $this->request->params['plugin'] == 'lil_invoices';
		$invoices['url'] = array(
			'admin'      => true,
			'plugin'     => 'lil_invoices',
			'controller' => 'invoices',
			'action'     => 'index',
		);
		
		$invoices['items'] = array(
			'invoices_received' => array(
				'visible' => true,
				'title' => __d('lil_invoices', 'Received Invoices'),
				'url'   => false,
				'params' => array(),
				'active' => false,
				'expand' => null,
				'submenu' => array()
			),
			'invoices_issued' => array(
				'visible' => true,
				'title' => __d('lil_invoices', 'Issued Invoices'),
				'url'   => false,
				'params' => array(),
				'active' => false,
				'expand' => null,
				'submenu' => array()
			),
			'invoices_archive' => array(
				'visible' => true,
				'title' => __d('lil_invoices', 'Archived Invoices'),
				'url'   => false,
				'expandable' => true,
				'params' => array(),
				'active' => false,
				'expand' => null,
				'submenu' => array()
			),
			'invoices_items' => array(
				'visible' => true,
				'title' => __d('lil_invoices', 'Items'),
				'url'   => array('controller' => 'items'),
				'expandable' => true,
				'params' => array(),
				'active' => $this->request->controller == 'items',
				'expand' => null,
				'submenu' => array()
			),
			'invoices_counter' => array(
				'visible' => $this->currentUser->role('admin'),
				'title' => __d('lil_invoices', 'Counters'),
				'url'   => array('controller' => 'counters', 'action' => 'index'),
				'expandable' => true,
				'params' => array(),
				'active' => $this->request->controller == 'counters',
				'expand' => null,
				'submenu' => array()
			),
		);
		
		if ($invoices['active']) {
			// fetch counters
			$Counter = ClassRegistry::init('LilInvoices.Counter');
			$counters = $Counter->find('all', array(
				'conditions' => array(
					'kind' => array('issued', 'received')
				),
				'order' => array('active', 'kind DESC'),
				'recursive' => -1
			));
			
			// build submenus
			$archived_counters = array(); $first_counter = null;
			foreach ($counters as $c) {
				if ($c['Counter']['active']) {
					$target = 'invoices_' . $c['Counter']['kind'];
					if (empty($first_counter)) $first_counter = $c['Counter']['id'];
				} else {
					$target = 'invoices_archive';
					$archived_counters[] = $c['Counter']['id'];
				}
				$invoices['items'][$target]['submenu'][$c['Counter']['id']] = array(
					'visible' => true,
					'title'   => $c['Counter']['title'],
					'url'   => array(
						'plugin'     => 'lil_invoices',
						'controller' => 'invoices',
						'action'     => 'index',
						'admin'      => true,
						'?'          => array('filter' => array('counter' => $c['Counter']['id']))
					),
					'active' =>
						in_array($this->request->params['controller'], array('invoices')) &&
						(
							(isset($this->request->query['filter']['counter']) && ($this->request->query['filter']['counter'] == $c['Counter']['id']))
							||
							(empty($this->request->query['filter']['counter']) && ($first_counter == $c['Counter']['id']))
						)
				);
			}
			
			if (empty($invoices['items']['invoices_archive']['submenu'])) {
				unset($invoices['items']['invoices_archive']);
			} else {
				// is archive expanded
				$invoices['items']['invoices_archive']['expand'] =
					in_array($this->request->params['controller'], array('invoices')) &&
					isset($this->request->query['filter']['counter']) &&
					in_array($this->request->query['filter']['counter'], $archived_counters);
				// dont allow folding when active archive project
				if ($invoices['items']['invoices_archive']['expand']) $invoices['items']['invoices_archive']['expand'] = null;
			}
		}
		
		// insert into sidebar right after welcome panel
		$this->sidebarInsertPanel($sidebar, array('after' => 'welcome'), array('invoices' => $invoices));
		
		return $sidebar;
	}
}