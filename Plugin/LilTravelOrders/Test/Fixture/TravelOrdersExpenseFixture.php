<?php
/* TravelOrdersExpense Fixture generated on: 2011-07-05 08:51:18 : 1309848678 */

/**
 * TravelOrdersExpenseFixture
 *
 */
class TravelOrdersExpenseFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'order_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'dat_expense' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'descript' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'price' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 5,
			'order_id' => '4d4cf860-2a90-4cb0-8e3c-1044025da8b9',
			'dat_expense' => '2011-02-05',
			'descript' => 'Parkirnina',
			'price' => 20.00
		),
	);
}
