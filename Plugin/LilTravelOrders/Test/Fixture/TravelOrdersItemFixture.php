<?php
/* TravelOrdersItem Fixture generated on: 2011-07-05 08:51:24 : 1309848684 */

/**
 * TravelOrdersItemFixture
 *
 */
class TravelOrdersItemFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'order_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'dat_travel' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'origin' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'destination' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'departure' => array('type' => 'time', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'arrival' => array('type' => 'time', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'workdays' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '4,1', 'collate' => NULL, 'comment' => ''),
		'workday_price' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
		'km' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '4,1', 'collate' => NULL, 'comment' => ''),
		'km_price' => array('type' => 'float', 'null' => false, 'default' => NULL, 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
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
			'id' => 3,
			'order_id' => '4d4cf860-2a90-4cb0-8e3c-1044025da8b9',
			'dat_travel' => '2011-02-05',
			'origin' => 'Trebnje',
			'destination' => 'Ljubljana',
			'departure' => '09:20:00',
			'arrival' => '10:20:00',
			'workdays' => '0.0',
			'workday_price' => NULL,
			'km' => 50.0,
			'km_price' => '0.37'
		),
		array(
			'id' => 4,
			'order_id' => '4d53bcde-0d90-49a1-b0b2-0c6c025da8b9',
			'dat_travel' => '2011-02-10',
			'origin' => 'Ljubljana',
			'destination' => 'Trebnje',
			'departure' => '14:00:00',
			'arrival' => '15:00:00',
			'workdays' => NULL,
			'workday_price' => NULL,
			'km' => 5.0,
			'km_price' => '0.37'
		),
		array(
			'id' => 5,
			'order_id' => '4d53bd76-f160-414f-82ac-0c6c025da8b9',
			'dat_travel' => '2011-02-10',
			'origin' => 'Ljubljana',
			'destination' => 'Trebnje',
			'departure' => '16:00:00',
			'arrival' => '17:00:00',
			'workdays' => NULL,
			'workday_price' => NULL,
			'km' => 5.0,
			'km_price' => '0.37'
		),
		array(
			'id' => 6,
			'order_id' => '4d53bd76-f160-414f-82ac-0c6c025da8b9',
			'dat_travel' => '2011-02-10',
			'origin' => 'Trebnje',
			'destination' => 'Ljubljana',
			'departure' => NULL,
			'arrival' => NULL,
			'workdays' => NULL,
			'workday_price' => NULL,
			'km' => 8.0,
			'km_price' => '0.37'
		),
		array(
			'id' => 7,
			'order_id' => '4d53c271-e950-49ea-9bc4-0c6c025da8b9',
			'dat_travel' => '2011-02-10',
			'origin' => 'Trebnje',
			'destination' => 'Ljubljana',
			'departure' => '16:00:00',
			'arrival' => '17:00:00',
			'workdays' => NULL,
			'workday_price' => NULL,
			'km' => 50.0,
			'km_price' => '0.37'
		),
		array(
			'id' => 8,
			'order_id' => '4d53c271-e950-49ea-9bc4-0c6c025da8b9',
			'dat_travel' => '2011-02-10',
			'origin' => 'Ljubljana',
			'destination' => 'Trebnje',
			'departure' => NULL,
			'arrival' => NULL,
			'workdays' => NULL,
			'workday_price' => NULL,
			'km' => 50.0,
			'km_price' => '0.37'
		),
		array(
			'id' => 9,
			'order_id' => '4d53c54d-cc84-4bdb-8143-0c6c025da8b9',
			'dat_travel' => '2011-02-10',
			'origin' => 'Ljubljana',
			'destination' => 'Trebnje',
			'departure' => '12:59:00',
			'arrival' => '13:59:00',
			'workdays' => NULL,
			'workday_price' => NULL,
			'km' => 50.0,
			'km_price' => '0.37'
		),
		array(
			'id' => 10,
			'order_id' => '4d53c54d-cc84-4bdb-8143-0c6c025da8b9',
			'dat_travel' => '2011-02-10',
			'origin' => 'Trebnje',
			'destination' => 'Ljubljana',
			'departure' => '14:00:00',
			'arrival' => '15:00:00',
			'workdays' => NULL,
			'workday_price' => NULL,
			'km' => 60.0,
			'km_price' => '0.37'
		),
	);
}
