<?php
App::uses('LilAppModel', 'Lil.Model');
class Invoice extends LilAppModel {
	var $name = 'Invoice';
	
	var $recursive = -1;
	
	var $actsAs = array(
		'Lil.LilFloat', 'Lil.LilDate', 'Containable',
		'Lil.LilAttachment' => array(
			'counterCache' => true
		)
	);
	
	var $belongsTo = array(
		'Client' => array(
			'className'  => 'LilCrm.Contact',
			'foreignKey' => 'contact_id',
			'type'       => 'INNER'
		),
		'InvoicesCounter' => array(
			'className'  => 'LilInvoices.InvoicesCounter',
			'foreignKey' => 'counter_id',
			'type'       => 'INNER'
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
		
		$ret['contain'] = array('Client');
		
		return $ret;
	}
}