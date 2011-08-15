<?php
/**
 * LilTravelOrdersPluginController
 *
 * This is Lil Plugin for Travel Orders
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * LilTravelOrdersPluginController class
 *
 * @uses          LilPluginController
 */
class LilTravelOrdersPluginController extends LilPluginController {
/**
 * name property
 *
 * @var string
 */
	public $name = 'LilTravelOrdersPlugin';
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
		if ($this->request->params['plugin'] == 'lil_travel_orders') {
			App::uses('HtmlHelper', 'View/Helper');
			$Html = new HtmlHelper($view);
			$view->addScript($Html->css('/lil_travel_orders/css/lil_travel_orders.css'));
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
		$orders['title'] = __d('lil_travel_orders', 'Travel Orders');
		$orders['visible'] = true;
		$orders['active'] = $this->request->params['plugin'] == 'lil_travel_orders';
		$orders['url'] = array(
			'admin'      => true,
			'plugin'     => 'lil_travel_orders',
			'controller' => 'travel_orders',
			'action'     => 'index',
		);
		
		$orders['items'] = array(
			'travel_orders_travel' => array(
				'visible' => true,
				'title' => __d('lil_travel_orders', 'Travel Orders'),
				'url'   => false,
				'params' => array(),
				'active' => false,
				'expand' => null,
				'submenu' => array()
			),
			'travel_orders_archive' => array(
				'visible' => true,
				'title' => __d('lil_travel_orders', 'Archived Orders'),
				'url'   => false,
				'expandable' => true,
				'params' => array(),
				'active' => false,
				'expand' => null,
				'submenu' => array()
			),
			'travel_orders_counter' => array(
				'visible' => $this->currentUser->role('admin'),
				'title' => __d('lil_travel_orders', 'Counters'),
				'url'   => array('controller' => 'travel_orders_counters', 'action' => 'index'),
				'expandable' => true,
				'params' => array(),
				'active' => $this->request->controller == 'travel_orders_counters',
				'expand' => null,
				'submenu' => array()
			),
		);
		
		if ($orders['active']) {
			// fetch counters
			$Counter = ClassRegistry::init('LilTravelOrders.TravelOrdersCounter');
			$counters = $Counter->find('all', array(
				'conditions' => array('kind' => array('travel')),
				'order' => array('active', 'kind DESC'),
				'recursive' => -1
			));
			
			// build submenus
			$archived_counters = array(); $first_counter = null;
			if (is_array($counters)) foreach ($counters as $c) {
				if ($c['TravelOrdersCounter']['active']) {
					$target = 'travel_orders_' . $c['TravelOrdersCounter']['kind'];
					if (empty($first_counter)) $first_counter = $c['TravelOrdersCounter']['id'];
				} else {
					$target = 'travel_orders_archive';
					$archived_counters[] = $c['TravelOrdersCounter']['id'];
				}
				$orders['items'][$target]['submenu'][$c['TravelOrdersCounter']['id']] = array(
					'visible' => true,
					'title'   => $c['TravelOrdersCounter']['title'],
					'url'   => array(
						'plugin'     => 'lil_travel_orders',
						'controller' => 'travel_orders',
						'action'     => 'index',
						'admin'      => true,
						'?'          => array('filter' => array('counter' => $c['TravelOrdersCounter']['id']))
					),
					'active' =>
						in_array($this->request->params['controller'], array('travel_orders')) &&
						(
							(isset($this->request->query['filter']['counter']) && ($this->request->query['filter']['counter'] == $c['TravelOrdersCounter']['id']))
							||
							(empty($this->request->query['filter']['counter']) && ($first_counter == $c['TravelOrdersCounter']['id']))
						)
				);
			}
			
			if (empty($orders['items']['travel_orders_archive']['submenu'])) {
				unset($orders['items']['travel_orders_archive']);
			} else {
				// is archive expanded
				$orders['items']['travel_orders_archive']['expand'] =
					in_array($this->request->params['controller'], array('travel_orders')) &&
					isset($this->request->query['filter']['counter']) &&
					in_array($this->request->query['filter']['counter'], $archived_counters);
				// dont allow folding when active archive project
				if ($orders['items']['travel_orders_archive']['expand']) $orders['items']['travel_orders_archive']['expand'] = null;
			}
		}
		
		// insert into sidebar right after welcome panel
		$this->sidebarInsertPanel($sidebar, array('after' => 'welcome'), array('orders' => $orders));
		
		return $sidebar;
	}
}