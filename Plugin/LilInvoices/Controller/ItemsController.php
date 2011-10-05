<?php
/**
 * LilIntranet
 *
 * This controller will manage Items
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * ItemsController class
 *
 * @uses          LilAppController
 *
 */
class ItemsController extends LilAppController {
/**
 * name property
 *
 * @var string 'Items'
 */
	public $name = 'Items';
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->set('data', $this->paginate('Item'));
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
		$this->Item->recursive = -1;
		
		if (!empty($this->request->data)) {
			if ($this->Item->save($this->request->data)) {
				$this->setFlash(__d('lil_invoices', 'Item has been saved.'));
				$this->doRedirect(array('action' => 'index'));
			} else {
				$this->setFlash(__d('lil_invoices', 'Please verify that the information is correct.'), 'error');
			}
		} else if (!empty($id)) {
			$this->request->data = $this->Item->read(null, $id);
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
		$this->Item->recursive = -1;
		if (!empty($id) && $data = $this->Item->hasAny(array('Item.id' => $id))) {
			$this->Item->delete($id);
			$this->setFlash(__d('lil_invoices', 'Item has been deleted.'));
			$this->redirect($this->referer());
		} else {
			$this->error404();
		}
	}
/**
 * admin_view method
 *
 * @param int $id
 * @access public
 * @return void
 */
	public function admin_view($id=null) {
		$this->Item->recursive = -1;
		if (!empty($id) && $data = $this->Item->findById($id)) {
			$this->set(compact('data'));
		} else {
			$this->error404();
		}
	}
/**
 * admin_autocomplete method
 *
 * @access public
 * @return void
 */
	public function admin_autocomplete() {
		if ($this->RequestHandler->isAjax()) {
			$result = $this->Item->find('all', array(
				'conditions' => array('Item.descript LIKE' => '%' . $this->params['url']['term'] . '%'),
				'recursive'  => -1
			));
			
			$data = array();
			foreach ($result as $i) {
				$data[] = array(
					'id'    => $i['Item']['id'],
					'label' => $i['Item']['descript'],
					'qty'   => $i['Item']['qty'],
					'unit'  => $i['Item']['unit'],
					'price' => $i['Item']['price'],
					'tax'   => $i['Item']['tax'],
				);
			}
			$this->set(compact('data'));
		} else $this->error404();
	}
}