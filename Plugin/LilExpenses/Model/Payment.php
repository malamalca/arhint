<?php
/**
 * Arhim: The Architectural practice
 *
 * This model if for storing Payments
 *
 * @copyright     Copyright 2010, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/arhim.php
 */
App::uses('LilAppModel', 'Lil.Model');
/**
 * Payment model
 *
 */
class Payment extends LilAppModel {
/**
 * name property
 *
 * @var string
 */
	public $name = 'Payment';
/**
 * order property
 *
 * @var string
 */
	public $order = 'Payment.dat_happened DESC';
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array(
		'Lil.LilFloat', 'Lil.LilDate'
	);
/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'PaymentsAccount' => array(
			'className' => 'LilExpenses.PaymentsAccount',
			'foreignKey' => 'account_id'
		)
	);
/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array(
		'Expense' => array(
			'className' => 'LilExpenses.Expense',
			'joinTable' => 'payments_expenses',
		)
	);
/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array(
		'PaymentsExpense' => array(
			'className' => 'LilExpenses.PaymentsExpense'
		)
	);
/**
 * filter
 *
 */
	public function filter(&$filter) {
		$ret = array();
		if (!isset($filter['start']) || !$this->LilDate->isSql($filter['start'])) {
			$filter['start'] = $this->field('MIN(dat_happened) AS start', array('1'=>'1'));
		}
		$ret['conditions']['Payment.dat_happened >'] = $filter['start'];
		
		if (!isset($filter['end']) || !$this->LilDate->isSql($filter['end'])) {
			$filter['end'] = $this->LilDate->toSql(time(), false);
		}
		$ret['conditions']['Payment.dat_happened <='] = $filter['end'];
		
		if (empty($filter['account']) || !in_array($filter['account'], array_keys($this->PaymentsAccount->find('list')))) {
			$filter['account'] = null;
		} else {
			$ret['conditions']['Payment.account_id'] = $filter['account'];
		}
		
		if (empty($filter['type']) || !in_array($filter['type'], array('from', 'to'))) {
			$filter['type'] = null;
		} else {
			if ($filter['type'] == 'from') {
				$ret['conditions']['Payment.amount <'] = 0;
			} else {
				$ret['conditions']['Payment.amount >'] = 0;
			}
		}
		
		return $ret;
	}
}