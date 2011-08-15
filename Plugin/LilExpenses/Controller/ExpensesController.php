<?php
/**
 * Arhim: The Architectural practice
 *
 * This controller will manage expenses.
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * Expenses controller
 *
 */
class ExpensesController extends LilAppController {
/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Expenses';
/**
 * This controller doesnt use any model.
 *
 * @var array
 */
	public $uses = array('LilExpenses.Expense', 'LilExpenses.Payment');
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$filter = array();
		if (!empty($this->params['url']['filter'])) $filter = $this->params['url']['filter'];
		
		$this->paginate = array_merge(
			array('order' => 'Expense.dat_happened DESC, Expense.created DESC',),
			$this->Expense->filter($filter)
		);
		$expenses = $this->paginate('Expense');
		
		// get total sum regardless of pagiantion
		$total_sum = $this->Expense->find('first', array_merge($this->paginate, array(
			'fields' => array('SUM(Expense.total) as total_sum'),
		)));
		$total_sum = $total_sum[0]['total_sum'];
		
		$users = $this->Expense->User->find('list');
		$projects = $this->Expense->Project->findForUser(null, 'list');
		
		$this->set(compact('expenses', 'filter', 'users', 'projects', 'total_sum'));
		
	}

/**
 * admin_view method
 *
 * @param mixed $id
 * @return void
 */
	public function admin_view($id = null) {
		if ($data = $this->Expense->find('first', array(
			'conditions' => array(
				'Expense.id' => $id
			),
			'contain' => array(
				'Payment', 'User', 'Project'
			)
		))) {
			$this->set(compact('data'));
		} else $this->error404();
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
 * admin_add method
 *
 * @param mixed $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (!empty($this->request->data)) {
			if ($this->Expense->saveAll($this->request->data)) {
				// create payment if it is selected
				if (empty($this->request->data['Expense']['id']) && !empty($this->request->data['Expense']['payment'])) {
					$p = array(
						'Payment' => array(
							'dat_happened' => $this->request->data['Expense']['dat_happened'],
							'descript'     => $this->request->data['Expense']['title'],
							'amount'       => $this->request->data['Expense']['total'],
							'source'       => $this->request->data['Expense']['payment']
						),
						'PaymentsExpense' => array(0 => array(
							'expense_id' => $this->Expense->getLastInsertId()
						))
					);
					$this->Payment->saveAll($p);
				}
				
				$this->setFlash(__d('lil_expenses', 'Expense has been saved.'));
				$this->doRedirect();
			} else {
				$this->setFlash(__d('lil_expenses', 'There are some errors in the form. Please correct all marked fields below.'));
			}
		} else if (!empty($id)) {
			$conditions = array('Expense.id' => $id);
			if (!$this->currentUser->role('admin')) $conditions['Expense.user_id'] = $this->user('id');
			
			if (!$this->request->data = $this->Expense->find('first', array(
				'conditions' => $conditions, 'recursive' => -1))
			) {
				$this->error404();
			}
		} else if (empty($id)) {
			$this->request->data['Expense']['dat_happened'] = strftime('%Y-%m-%d');
		}
		
		$this->setupRedirect();
		
		$projects = $this->Expense->Project->findForUser(null, 'list');
		$users = $this->Expense->User->find('list');
		
		$this->set(compact('projects', 'users'));
	}
	
/**
 * admin_delete method
 *
 * @access public
 */
	public function admin_delete($id = null) {
		$conditions = array('Expense.id' => $id);
		
		// user can only delete own expenses, unless admin
		if (!$this->currentUser->role('admin')) $conditions['Expense.user_id'] = $this->user('id');
			
		if (!empty($id) && $this->Expense->hasAny($conditions) && $this->Expense->delete($id)) {
			$this->setFlash(__d('lil_expenses', 'Expense has been deleted.'));
			$this->doRedirect();
		} else {
			$this->error404();
		}
	}
}