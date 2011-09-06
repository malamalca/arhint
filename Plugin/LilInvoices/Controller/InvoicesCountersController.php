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
class InvoicesCountersController extends LilAppController {
/**
 * name property
 *
 * @var string 'InvociesCounters'
 * @access public
 */
	public $name = 'InvoicesCounters';
/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Session', 'Auth', 'RequestHandler', 'Cookie');
/**
 * admin_index method
 *
 * @access public
 * @return void
 */
	public function admin_index() {
		$this->set('data', $this->paginate('InvoicesCounter'));
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
		$this->InvoicesCounter->recursive = -1;
		
		if (!empty($this->data)) {
			if ($this->InvoicesCounter->save($this->data)) {
				$this->setFlash(__d('lil_invoices', 'Counter has been saved.'));
				$this->doRedirect(array('action' => 'index'));
			} else {
				$this->setFlash(__d('lil_invoices', 'Please verify that the information is correct.'), 'error');
			}
		} else if (!empty($id)) {
			$this->data = $this->InvoicesCounter->read(null, $id);
		}
		
		$this->setupRedirect();
		
		$layouts = array();
		
		$files = new DirectoryIterator(App::pluginPath('LilInvoices') . 'View' . DS . 'Invoices' . DS . 'layouts');
		foreach ($files as $item) if ($item->isFile() && ($basename = $item->getBasename('.ctp'))) {
			$layouts[$basename] = $basename;
		}
		
		$this->set(compact('layouts'));
	}
/**
 * admin_delete method
 *
 * @param int $id
 * @return void
 */
	public function admin_delete($id=null) {
		$this->InvoicesCounter->recursive = -1;
		if (!empty($id) && $data = $this->InvoicesCounter->findById($id)) {
			$this->InvoicesCounter->delete($id);
			$this->setFlash(__d('lil_invoices', 'Counter has been deleted.'));
			$this->redirect(array('controller' => 'counters', 'action' => 'index'));
		} else {
			$this->error404();
		}
	}
}