<?php
/**
 * Arhim: The Architectural practice
 *
 * This model if for storing Project Expenses
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppModel', 'Lil.Model');
/**
 * Expenses model
 *
 */
class Expense extends LilAppModel {
/**
 * name property
 *
 * @var string
 * @access public
 */
	public $name = 'Expense';
/**
 * order property
 *
 * @var string
 * @access public
 */
	public $order = 'Expense.dat_happened DESC';
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Containable', 'Lil.LilFloat', 'Lil.LilDate');
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'Project' => array(
			'className' => 'Lil.Area',
			'foreignKey' => 'project_id'
		),
		'User' => array(
			'className' => 'Lil.User',
			'foreignKey' => 'user_id'
		)
	);
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'PaymentsExpense' => array(
			'className' => 'LilExpenses.PaymentsExpense'
		)
	);
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array(
		'Payment' => array(
			'className' => 'LilExpenses.Payment',
			'joinTable' => 'payments_expenses',
		)
	);
/**
 * filter
 *
 * @access public
 */
	function filter(&$filter) {
		$ret = array();
		
		// always filter for user
		if (!$this->currentUser->role('admin')) {
			$filter['conditions']['AND']['Expense.project_id'] = array_keys(
				$this->Project->findForUser(null, 'list')
			);
		}
		
		if ($area_id = $this->currentArea->get('id')) {
			$filter['Project'] = array($area_id);
		}
		
		if (isset($filter['Project'])) {
			$ret['conditions']['Expense.project_id'] = (array)$filter['Project'];
		}
		
		if (isset($filter['kind'])) {
			if ($filter['kind'] == 'other') {
				$ret['conditions']['Expense.model'] = null;
			} else {
				$ret['conditions']['Expense.model'] = (array)$filter['kind'];
			}
		}
		
		// filter by user
		if ($this->currentUser->role('admin')) {
			if (isset($filter['User'])) {
				$ret['conditions']['Expense.user_id'] = (array)$filter['User'];
			}
		} else {
			$filter['User']  = $ret['conditions']['Expense.user_id'] = $this->currentUser->get('id');
		}
		return $ret;
	}
/**
 * __construct method
 *
 * @param mixed $id
 * @param mixed $table
 * @param mixed $ds	
 * @access private
 * @return void
 */
	function __construct($id = false, $table = null, $ds = null) {
		if (CakePlugin::loaded('LilInvoices')) {
			$this->belongsTo['Invoice'] = array(
				'className' => 'LilInvoices.Invoice',
				'foreignKey' => 'foreign_id',
				'conditions'   => array('Expense.model' => 'Invoice'),
			);
		}
		if (CakePlugin::loaded('LilTravelOrders')) {
			$this->belongsTo['TravelOrder'] = array(
				'className' => 'LilTravelOrders.TravelOrder',
				'foreignKey' => 'foreign_id',
				'conditions'   => array('Expense.model' => 'Invoice'),
			);
		}
		parent::__construct($id, $table, $ds);
	}
}