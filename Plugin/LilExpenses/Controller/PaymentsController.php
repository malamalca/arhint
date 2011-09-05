<?php
/**
 * Arhim: The Architectural practice
 *
 * This controller will manage payments.
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * Payments controller
 *
 */
class PaymentsController extends LilAppController {
/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Payments';
/**
 * This controller doesnt use any model.
 *
 * @var array
 */
	public $uses = array('LilExpenses.Payment');
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$filter = array();
		if (!empty($this->request->query['filter'])) $filter = $this->request->query['filter'];
		
		$params = array_merge(
			array('order' => 'Payment.dat_happened, Payment.created'),
			$this->Payment->filter($filter)
		);
		$payments = $this->Payment->find('all', $params);
		
		// get total sum regardless of pagiantion
		$first = reset($payments);
		$saldo = $this->Payment->find('first', array(
			'conditions' => array(
				'OR' => array(
					0 => array('Payment.dat_happened <' => $first['Payment']['dat_happened']),
					1 => array(
						'Payment.dat_happened' => $first['Payment']['dat_happened'],
						'Payment.created <' => $first['Payment']['created']
					)
				)
			),
			'fields' => array('SUM(amount) AS saldo')
		));
		$saldo = $saldo[0]['saldo'];
		
		$this->set(compact('payments', 'filter', 'total_sum', 'saldo'));
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
 * @param mixed $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (!empty($this->request->data)) {
			if ($this->Payment->saveAll($this->request->data)) {
				$this->setFlash(__d('lil_expenses', 'Payment has been saved.'));
				return $this->doRedirect();
			}
			$this->setFlash(__d('lil_expenses', 'There are some errors in the form. Please correct all marked fields below.'));
		} else if (!empty($id)) {
			if (!$this->request->data = $this->Payment->find('first', 
				array('conditions' => array('Payment.id' => $id), 'recursive' => -1)
			)) {
				// must redirect to index because of redirects from delete actions
				$this->redirect(array(
					'plugin' => 'lil_expenses',
					'controller' => 'payments',
					'admin' => true,
					'action' => 'index'
				));
			}
		} else if (!empty($this->request->params['named']['expense'])) {
			if ($exp = $this->Payment->Expense->find('first', array(
				'conditions' => array('Expense.id' => $this->request->params['named']['expense']),
				'recursive' => -1
			))) {
				$this->request->data['Payment']['dat_happened'] = strftime('%Y-%m-%d');
				$this->request->data['Payment']['descript']     = $exp['Expense']['title'];
				$this->request->data['Payment']['amount']       = $exp['Expense']['total'];
			}
			$this->request->data['PaymentsExpense'][0]['expense_id'] = $this->request->params['named']['expense'];
		}
		
		$this->setupRedirect();
	}
	
/**
 * admin_delete method
 *
 * @param mixed $id
 * @return void
 */
	function admin_delete($id = null) {
		$conditions = array('Payment.id' => $id);
		if (!empty($id) && $this->Payment->hasAny($conditions) && $this->Payment->delete($id)) {
			$this->setFlash(__d('lil_expenses', 'Payment has been deleted.'));
			$this->redirect($this->referer());
		} else {
			$this->error404();
		}
	}
}