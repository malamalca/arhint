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
 * order property
 *
 * @var string
 * @access public
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
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array(
		'Expense' => array(
			'joinTable' => 'payments_expenses',
		)
	);
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'PaymentsExpense'
	);
/**
 * filter
 *
 * @access public
 */
	function filter(&$filter) {
		$ret = array();
		if (isset($filter['source']) && in_array($filter['source'], array('p', 'o', 'c'))) {
			$ret['conditions']['Payment.source'] = $filter['source'];
		}
		
		return $ret;
	}
}