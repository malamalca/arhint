<?php
/**
 * Arhim: The Architectural practice
 *
 * This model if for storing TravelOrders
 *
 * PHP versions 4 and 5
 *
 * @copyright     Copyright 2010, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/arhim.php
 */
App::import('Model', 'Lil.LilAppModel');
/**
 * TravelOrder model
 *
 */
class TravelOrder extends LilAppModel {
/**
 * order property
 *
 * @var string
 * @access public
 */
	public $order = 'TravelOrder.dat_order DESC';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'Employee' => array(
			'className'  => 'LilCrm.Contact',
			'conditions' => array('Employee.kind' => 'T')
		),
		'Payer' => array(
			'className'  => 'LilCrm.Contact',
			'foreignKey' => 'payer_id',
			'conditions' => array('Payer.kind' => 'C'),
		),
		'TravelOrdersCounter' => array(
			'className'  => 'LilTravelOrders.TravelOrdersCounter',
			'foreignKey' => 'counter_id'
		),
	);
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'TravelOrdersItem' => array(
			'className'  => 'LilTravelOrders.TravelOrdersItem',
			'foreignKey' => 'order_id',
			'dependent' => true
		),
		'TravelOrdersExpense' => array(
			'className'  => 'LilTravelOrders.TravelOrdersExpense',
			'foreignKey' => 'order_id',
			'dependent' => true
		),
	);
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array(
		'Containable', 'Lil.LilFloat'
	);
/**
 * filter
 *
 * @access public
 */
	function filter(&$filter) {
		$ret = array('conditions' => array());
		
		return $ret;
	}
}