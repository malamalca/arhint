<?php
/**
 * LilIntranet
 *
 * This controller will manage conters
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * Counters class
 *
 * @uses          LilAppController
 *
 */
class TravelOrdersCountersController extends LilAppController {
/**
 * name property
 *
 * @var string 'InvociesCounters'
 * @access public
 */
	public $name = 'TravelOrdersCounters';
/**
 * admin_index method
 *
 * @access public
 * @return void
 */
	public function admin_index() {
		$data = $this->TravelOrdersCounter->find('all');
		$this->set(compact('data'));
	}
/**
 * admin_add method
 *
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
 * @return void
 */
	public function admin_edit($id = null) {
		$this->TravelOrdersCounter->recursive = -1;
		
		if (!empty($this->data)) {
			if ($this->TravelOrdersCounter->save($this->data)) {
				$this->setFlash(__d('lil_travel_orders', 'Counter has been saved.'));
				$this->doRedirect(array('action' => 'index'));
			} else {
				$this->setFlash(__d('lil_travel_orders', 'Please verify that the information is correct.'), 'error');
			}
		} else if (!empty($id)) {
			$this->data = $this->TravelOrdersCounter->read(null, $id);
		}
		
		$this->setupRedirect();
	}
/**
 * admin_delete method
 *
 * @param int $id
 * @return void
 */
	public function admin_delete($id=null) {
		$this->TravelOrdersCounter->recursive = -1;
		if (!empty($id) && $data = $this->TravelOrdersCounter->findById($id)) {
			$this->TravelOrdersCounter->delete($id);
			$this->setFlash(__d('lil_travel_orders', 'Counter has been deleted.'));
			$this->redirect(array('controller' => 'travel_orders_counters', 'action' => 'index'));
		} else {
			$this->error404();
		}
	}
}