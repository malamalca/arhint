<?php
/**
 * This model if for storing Payments
 *
 */
App::uses('LilAppModel', 'Lil.Model');
/**
 * PaymentsAccount model
 *
 */
class PaymentsAccount extends LilAppModel {
/**
 * name property
 *
 * @var string
 */
	public $name = 'PaymentsAccount';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasMany = array(
		'Payment' => array(
			'className' => 'LilExpenses.Payment',
			'foreignKey' => 'account_id'
		)
	);
/**
 * filter
 *
 */
	public function filter(&$filter) {
		
		
		return $ret;
	}
}