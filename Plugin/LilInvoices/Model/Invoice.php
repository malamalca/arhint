<?php
App::uses('LilAppModel', 'Lil.Model');
class Invoice extends LilAppModel {
	var $name = 'Invoice';
	
	var $recursive = -1;
	
	var $actsAs = array('Lil.LilFloat', 'Lil.LilDate', 'Containable');
	
	var $belongsTo = array(
		'Client' => array(
			'className'  => 'LilCrm.Contact',
			'foreignKey' => 'contact_id',
			'type'       => 'INNER'
		),
		'InvoicesCounter' => array(
			'className'  => 'LilInvoices.InvoicesCounter',
			'foreignKey' => 'counter_id'
		),
		'User' => array(
			'className'  => 'Lil.User',
			'foreignKey' => 'user_id'
		),
		'Project' => array(
			'className' => 'Lil.Area',
			'foreignKey' => 'project_id'
		),
	);
	
	var $hasMany = array(
		'InvoicesItem' => array(
			'className'  => 'LilInvoices.InvoicesItem',
			'dependent' => true
		),
		'Attachment' => array(
			'conditions' => array('Attachment.model' => 'Invoice'),
			'foreignKey' => 'foreign_id',
			'order'      => 'Attachment.created',
			'dependent'  => true
		),
	);
	
	var $validate = array(
		'contact_id' => array(
			'format' => array(
				'rule' => 'checkClient',
				'allowEmpty' => false,
				'required' => true
			),
		),
		'total' => array(
			'format' => array('rule' => 'isValidFloat', 'allowEmpty' => false),
		),
	);
/**
 * filter
 *
 * @access public
 */
	public function checkClient($data) {
		$client_id = reset($data);
		return $this->Client->hasAny(array('Client.id' => $client_id));
	}
/**
 * filter
 *
 * @access public
 */
	public function filter(&$filter) {
		$ret = array();
		
		if (isset($filter['kind'])) {
			$ret['conditions']['Invoice.kind'] = $filter['kind'];
		}

		if (isset($filter['expense'])) {
			$ret['conditions']['Invoice.expense_id'] = $filter['expense'];
		}
		
		if (isset($filter['counter'])) {
			$ret['conditions']['Invoice.counter_id'] = (array)$filter['counter'];
		} 
		
		if (!empty($filter['search'])) {
			if (substr($filter['search'], 0, 1) == '#') { 
				$ret['conditions'][] = array('Invoice.counter' => substr($filter['search'], 1));
			} else {
				$ret['conditions'][] = array('OR' => array(
					'Invoice.no LIKE' => '%' . $filter['search'] . '%',
					'Invoice.counter_id' => $filter['search'],
					'Invoice.era' => $filter['search'],
					'Invoice.title LIKE' => '%' . $filter['search'] . '%',
					'Client.title LIKE' => '%' . $filter['search'] . '%',
				));
			}
		}
		
		$ret['contain'] = array('Client', 'InvoicesCounter');
		
		return $ret;
	}
/**
 * beforeSave model callback
 *
 * @access public
 */
/*i	function afterSave($created = null) {
		f ((
				!empty($this->data['Invoice']['expense_id']) && 
				($expense_id = $this->data['Invoice']['expense_id'])
			) || ($expense_id = $this->field('expense_id')))
		{
			if ($data = $this->find('first', array(
				'conditions' => array('Invoice.id' => $this->id),
				'fields'     => array(
					'Invoice.total', 'Invoice.dat_issue', 'Invoice.title', 
					'Invoice.project_id', 'Invoice.expense_id', 'Invoice.user_id',
					'InvoicesCounter.kind'
				),
				'contain'    => array('InvoicesCounter')
			))) {
				
				$data['Expense'] = $data['Invoice']; unset($data['Invoice']);
				
				$data['Expense']['id'] = $expense_id;
				$data['Expense']['dat_happened'] = $data['Expense']['dat_issue'];
				unset($data['Expense']['dat_issue']);
				$data['Expense']['model'] = 'Invoice';
				$data['Expense']['foreign_id'] = $this->id;
				
				// multiply by 1 so we get float format
				if ($data['InvoicesCounter']['kind'] == 'received') {
					$data['Expense']['total'] = $this->delocalize($data['Expense']['total'] * -1);
				} else {
					$data['Expense']['total'] = $this->delocalize($data['Expense']['total'] * 1);
				}
				
				$ret = $this->Expense->save($data);
				
				if ($ret && (!empty($this->data['Invoice']['payment']))) {
					$payment = array('Payment' => array(
						'amount'       => $data['Expense']['total'] * 1,
						'dat_happened' => $data['Expense']['dat_happened'],
						'source'       => $this->data['Invoice']['payment'],
						'descript'     => $this->data['Invoice']['title']
					));
					if ($this->Expense->Payment->save($payment)) {
						$payment_expense = array('PaymentsExpense' => array(
							'payment_id' => $this->Expense->Payment->id,
							'expense_id' => $data['Expense']['id'],
						));
						$this->Expense->Payment->PaymentsExpense->save($payment_expense);
					}
				}
			}
		}
		parent::afterSave($created);
	}*/
/**
 * afterDelete model callback
 *
 * @access public
 */
	function afterDelete() {
		//$this->Expense->deleteAll(array('Expense.model' => 'Invoice', 'Expense.foreign_id' => $this->id));
		return true;
	}
}