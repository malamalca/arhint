<?php
/**
 * Arhim: The Architectural practice
 *
 * This controller will manage travel orders
 *
 * PHP versions 4 and 5
 *
 * @copyright     Copyright 2010, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/arhim.php
 */
App::import('Controller', 'Lil.LilApp');
class TravelOrdersController extends LilAppController {
/**
 * name property
 *
 * @var string 'TravelOrders'
 * @access public
 */
	public $name = 'TravelOrders';
	
	public $uses = array('LilTravelOrders.TravelOrder', 'LilTravelOrders.TravelOrdersCounter');
/**
 * admin_index method
 *
 * @access public
 * @return void
 */
	public function admin_index() {
		$counter_params = array('fields' => array('id', 'kind', 'active', 'title'));
		
		if (empty($this->request->query['filter']['counter'])) {
			// no counter specified; find first (or default) counter
			$counter_params['conditions'] = array('active' => true, 'kind' => array('travel'));
			$counter_params['order'] = array('active', 'kind DESC');
		} else {
			$counter_params['conditions'] = array('id' => $this->request->query['filter']['counter']);
		}
		
		// fetch current counter
		if (!$counter = $this->TravelOrdersCounter->find('first', $counter_params)) return $this->error404();
		
		$this->request->query['filter']['counter'] = $counter['TravelOrdersCounter']['id']; // needed for sidebar
		$this->request->query['filter']['kind'] = $counter['TravelOrdersCounter']['kind'];
		
		// fetch invoices
		$filter = $this->TravelOrder->filter($this->request->query['filter']);
		$data = $this->TravelOrder->find('all', $filter);
		
		// get total sum regardless of pagiantion
		$total_sum = $this->TravelOrder->field('SUM(TravelOrder.total) as total_sum', $filter['conditions']);
		
		$this->set(compact('data', 'filter', 'counter', 'total_sum'));
	}
/**
 * admin_view method
 *
 * @param int $id
 * @access public
 * @return void
 */
	public function admin_view($id = null) {
		if ($data = $this->TravelOrder->find('first', array(
			'conditions' => array(
				'TravelOrder.id' => $id
			),
			'contain' => array(
				'Employee'    => 'PrimaryAddress',
				'Payer'       => 'PrimaryAddress',
				'TravelOrdersItem',
				'TravelOrdersExpense',
				'TravelOrdersCounter'
			)
		))) {
			$this->set(compact('data'));
		} else $this->error404();
	}
/**
 * admin_print method
 *
 * @param int $id
 * @access public
 * @return void
 */
	public function admin_print($id = null) {
		if ($data = $this->TravelOrder->find('first', array(
			'conditions' => array(
				'TravelOrder.id' => $id
			),
			'contain' => array(
				'Employee'    => 'PrimaryAddress',
				'Payer'       => 'PrimaryAddress',
				'TravelOrdersItem',
				'TravelOrdersExpense',
				'TravelOrdersCounter'
			)
		))) {
			$this->response->type('pdf');
			$this->set(compact('data'));
		} else $this->error404();
	}
/**
 * admin_add method
 *
 * @access public
 * @return void
 */
	public function admin_add() {
		$this->setAction('admin_edit');
		$this->view = 'admin_edit';
	}
/**
 * admin_edit method
 *
 * @param int $id
 * @access public
 * @return void
 */
	public function admin_edit($id = null) {
		if (empty($this->request->query['filter']['counter'])) return $this->error404();
		if (!$counter = $this->TravelOrdersCounter->find('first', array(
			'fields'     => array('id', 'counter', 'kind', 'mask', 'template_descript', 'expense'),
			'conditions' => array('id' => $this->request->query['filter']['counter'], 'active' => true),
			'recursive'  => -1
		))) return $this->error404();
		
		if (!empty($this->request->data)) {
			$items_to_delete = array();
			$data = $this->_prepareData($this->request->data, $items_to_delete);
			
			App::uses('LilPluginRegistry', 'Lil.Lil'); $registry = LilPluginRegistry::getInstance();
			$d = $registry->callPluginHandlers($this, 'travel_order_before_save', array(
				'data'            => $data, 
				'items_to_delete' => $items_to_delete
			));
			
			// validate data
			if ($d['data'] && $this->TravelOrder->saveAll($d['data'], array('validate' => 'only'))) {
				// update counter for new invoices
				if (empty($d['data']['TravelOrder']['id'])) {
					$no = $this->TravelOrderCounter->generateNo($counter, true);
					if (empty($d['data']['TravelOrder']['no'])) $d['data']['TravelOrder']['no'] = $no;
				}
				
				// do a real save
				if ($this->TravelOrder->saveAll($d['data'])) {
					$d['data']['TravelOrder']['id'] = $this->TravelOrder->id;
					$d = $registry->callPluginHandlers($this, 'travel_order_after_save', array(
						'data' => $d['data'], 
						'items_to_delete' => $d['items_to_delete']
					));
					
					foreach ($d['items_to_delete'] as $delete_model => $delete_items) {
						$this->Invoice->{$delete_model}->deleteAll(array($delete_model.'.id' => $delete_items));
					}
					
					$this->setFlash(__d('lil_travel_orders', 'TravelOrder has been successfully saved.', true));
					return $this->doRedirect(array(
						'action' => 'index',
						'?'      => array('filter' => array('counter' => $d['data']['TravelOrder']['counter_id']))
					));
				}
			}
			$this->setFlash(__d('lil_travel_orders', 'There are some errors in the form. Please correct all marked fields below.', true));
		} else {
			if (!empty($id)) {
				$this->request->data = $this->TravelOrder->find('first', array(
					'conditions' => array('TravelOrder.id' => $id),
					'contain'    => array('Payer', 'TravelOrdersItem', 'TravelOrdersExpense')
				));
				
				// set payer title field for autocomplete
				if (!empty($this->request->data['Payer']['title'])) {
					$this->request->data['TravelOrder']['payer'] = $this->request->data['Payer']['title'];
				}
			} else {
				// generate counter
				$counter['order_no'] = $this->TravelOrdersCounter->generateNo($counter);
				
				$this->request->data['TravelOrder']['kind'] = $counter['TravelOrdersCounter']['kind'];
				$this->request->data['TravelOrder']['counter_id'] = $counter['TravelOrdersCounter']['id'];
			}
			
			$this->setupRedirect();
		}
		
		$User = ClassRegistry::init('Lil.User');
		$users = $User->find('list');
		$this->set(compact('clients', 'users', 'counter', 'counters'));
	}
/**
 * admin_delete method
 *
 * @param int $id
 * @access public
 * @return void
 */
	public function admin_delete($id = null) {
		if (!empty($id) && $this->TravelOrder->delete($id)) {
			$this->setFlash(__d('lil_travel_orders', 'Travel Order has been successfully deleted.', true));
			$this->redirect(array('action'=>'index'));
		} else $this->error404();
	}
/**
 * _prepareData method
 *
 * @param mixed $data
 * @param array $items_to_delete
 * @access private
 * @return mixed
 */
	private function _prepareData($data, &$items_to_delete) {
		// remove empty task
		if (isset($data['Task'])) {
			if (empty($data['Task']['descript'])) {
				unset($data['Task']);
			} else {
				$data['Task']['project_id'] = $data['TravelOrder']['project_id'];
			}
		}
		
		// find items to delete
		if (!empty($data['TravelOrder']['items_to_delete'])) {
			$items_to_delete['TravelOrdersItem'] = (array)$data['TravelOrder']['items_to_delete'];
		} else $items_to_delete['TravelOrdersItem'] = array();
		if (!empty($data['TravelOrder']['expenses_to_delete'])) {
			$items_to_delete['TravelOrdersExpense'] = (array)$data['TravelOrder']['expenses_to_delete'];
		} else $items_to_delete['TravelOrdersExpense'] = array();
		
		// calculate total
		$data['TravelOrder']['total'] = 0;
		foreach ($data['TravelOrdersItem'] as $k => $item) {
			if (empty($item['dat_travel'])) {
				if (!empty($item['id'])) $items_to_delete['TravelOrdersItem'][] = $item['id'];
				unset($data['TravelOrdersItem'][$k]);
			} else {
				$data['TravelOrder']['total'] += 
					(round(
						$this->TravelOrder->delocalize($item['workdays']) * 
						$this->TravelOrder->delocalize($item['workday_price']), 2
					) + 
					round(
						$this->TravelOrder->delocalize($item['km']) * 
						$this->TravelOrder->delocalize($item['km_price']), 2
					));
	
				// fix date
				if (!empty($item['dat_travel'])) {
					$elements = explode('.', $item['dat_travel']);
					if (empty($elements[2])) {
						$elements[2] = strftime('%Y');
					}
					if (empty($elements[1])) {
						$elements[1] = strftime('%M');
					}
					$data['TravelOrdersItem'][$k]['dat_travel'] = implode('-', array(
						$elements[2], $elements[1], $elements[0]
					));
				}
			}
		}
		if (empty($data['TravelOrdersItem'])) unset($data['TravelOrdersItem']);
		
		foreach ($data['TravelOrdersExpense'] as $k => $expense) {
			if (empty($expense['dat_expense'])) {
				if (!empty($expense['id'])) $items_to_delete['TravelOrdersExpense'][] = $expense['id'];
				unset($data['TravelOrdersExpense'][$k]);
			} else {
				$data['TravelOrder']['total'] += round($this->TravelOrder->delocalize($expense['price']), 2);

				// fix date
				if (!empty($expense['dat_expense'])) {
					$elements = explode('.', $expense['dat_expense']);
					if (empty($elements[2])) {
						$elements[2] = strftime('%Y');
					}
					if (empty($elements[1])) {
						$elements[1] = strftime('%M');
					}
					$data['TravelOrdersExpense'][$k]['dat_expense'] = implode('-', array(
						$elements[2], $elements[1], $elements[0]
					));
				}
			}
		}
		if (empty($data['TravelOrdersExpense'])) unset($data['TravelOrdersExpense']);
		
		if (!empty($data['TravelOrdersExpense']['dat_expense'])) {
			$elements = explode('.', $data['TravelOrdersExpense']['dat_expense']);
			if (empty($elements[2])) {
				$elements[2] = strftime('%Y', strtotime($data['TravelOrder']['dat_order']));
			}
			$data['TravelOrdersExpense']['dat_expense'] = implode('-', array(
				$elements[2], $elements[1], $elements[0]
			));
		}
		
		if (empty($data['TravelOrder']['id'])) {
			// update counters (hard update which should increase counter)
			$counter = $this->TravelOrdersCounter->find('first', array(
				'fields'     => array('id', 'counter', 'mask', 'template_descript'),
				'conditions' => array('TravelOrdersCounter.id' => $data['TravelOrder']['counter_id']),
				'recursive'  => -1
			));
			$data['TravelOrder']['counter'] = $counter['TravelOrdersCounter']['counter'] + 1;
			$no = $this->TravelOrdersCounter->generateNo($counter, true);
			if (empty($data['TravelOrder']['no'])) {
				$data['TravelOrder']['no'] = $no;
			}
		}
		
		//remove empty items to delete
		if (empty($items_to_delete['TravelOrdersExpense'])) unset($items_to_delete['TravelOrdersExpense']);
		if (empty($items_to_delete['TravelOrdersItem'])) unset($items_to_delete['TravelOrdersItem']);
		
		// expense
		if (!empty($data['TravelOrder']['has_expense']) && empty($data['TravelOrder']['expense_id'])) {
			App::import('Core', 'String');
			$data['TravelOrder']['expense_id'] = String::uuid();
		}
		if (empty($data['TravelOrder']['has_expense']) && !empty($data['TravelOrder']['expense_id'])) {
			$items_to_delete['TravelOrder']['Expense'] = array($data['TravelOrder']['expense_id']);
			$data['TravelOrder']['expense_id'] = null;
		}
		
		return $data;
	}
}