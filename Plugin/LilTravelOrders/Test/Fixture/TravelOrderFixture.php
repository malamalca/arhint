<?php
/* TravelOrder Fixture generated on: 2011-07-05 08:49:37 : 1309848577 */

/**
 * TravelOrderFixture
 *
 */
class TravelOrderFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'payer_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'employee_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'vehicle_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'counter_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'counter' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'no' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'dat_order' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'location' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'descript' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'task' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'taskee' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'dat_task' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'departure' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'arrival' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'vehicle_title' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'vehicle_registration' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'vehicle_owner' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'advance' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
		'dat_advance' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'total' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
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
			'id' => '4d4cf860-2a90-4cb0-8e3c-1044025da8b9',
			'payer_id' => '4cd59658-12f4-42f2-8ab4-1bbc025da8b9',
			'employee_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'vehicle_id' => NULL,
			'counter_id' => '4d048d88-9070-4e13-b565-463554ffceb9',
			'counter' => 1,
			'no' => '2011/01',
			'dat_order' => '2011-02-05',
			'location' => 'Ljubljana',
			'descript' => 'Trebnje-Ljubljana-Trebnje',
			'task' => 'sestanek s stranko',
			'taskee' => 'direktor',
			'dat_task' => '2011-02-05',
			'departure' => '2011-02-05 08:10:00',
			'arrival' => '2011-02-05 13:10:00',
			'vehicle_title' => 'Megane',
			'vehicle_registration' => 'D2-690',
			'vehicle_owner' => 'privatno',
			'advance' => '0.00',
			'dat_advance' => NULL,
			'total' => 38.50,
			'created' => '2011-02-05 08:12:32',
			'modified' => '2011-02-05 09:21:09'
		),
		array(
			'id' => '4d53bcde-0d90-49a1-b0b2-0c6c025da8b9',
			'payer_id' => '',
			'employee_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'vehicle_id' => NULL,
			'counter_id' => '4d048d88-9070-4e13-b565-463554ffceb9',
			'counter' => 2,
			'no' => '2011/02',
			'dat_order' => '2011-02-10',
			'location' => '',
			'descript' => '',
			'task' => '',
			'taskee' => '',
			'dat_task' => '2011-02-10',
			'departure' => '2011-02-10 11:25:00',
			'arrival' => '2011-02-10 11:25:00',
			'vehicle_title' => '',
			'vehicle_registration' => '',
			'vehicle_owner' => '',
			'advance' => '0.00',
			'dat_advance' => NULL,
			'total' => 1.85,
			'created' => '2011-02-10 11:24:30',
			'modified' => '2011-02-10 11:25:37'
		),
		array(
			'id' => '4d53bd0e-f880-482b-8d6d-0c6c025da8b9',
			'payer_id' => '',
			'employee_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'vehicle_id' => NULL,
			'counter_id' => '4d048d88-9070-4e13-b565-463554ffceb9',
			'counter' => 3,
			'no' => '2011/03',
			'dat_order' => '2011-02-10',
			'location' => '',
			'descript' => '',
			'task' => '',
			'taskee' => '',
			'dat_task' => '2011-02-10',
			'departure' => '2011-02-10 12:25:00',
			'arrival' => '2011-02-10 13:25:00',
			'vehicle_title' => '',
			'vehicle_registration' => '',
			'vehicle_owner' => '',
			'advance' => '0.00',
			'dat_advance' => NULL,
			'total' => '0.00',
			'created' => '2011-02-10 11:25:18',
			'modified' => '2011-02-10 11:25:18'
		),
		array(
			'id' => '4d53bd76-f160-414f-82ac-0c6c025da8b9',
			'payer_id' => '',
			'employee_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'vehicle_id' => NULL,
			'counter_id' => '4d048d88-9070-4e13-b565-463554ffceb9',
			'counter' => 4,
			'no' => '2011/04',
			'dat_order' => '2011-02-10',
			'location' => '',
			'descript' => '',
			'task' => '',
			'taskee' => '',
			'dat_task' => '2011-02-10',
			'departure' => '2011-02-10 12:25:00',
			'arrival' => '2011-02-10 13:25:00',
			'vehicle_title' => '',
			'vehicle_registration' => '',
			'vehicle_owner' => '',
			'advance' => '0.00',
			'dat_advance' => NULL,
			'total' => 4.81,
			'created' => '2011-02-10 11:27:02',
			'modified' => '2011-02-10 11:27:02'
		),
		array(
			'id' => '4d53c271-e950-49ea-9bc4-0c6c025da8b9',
			'payer_id' => '',
			'employee_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'vehicle_id' => NULL,
			'counter_id' => '4d048d88-9070-4e13-b565-463554ffceb9',
			'counter' => 5,
			'no' => '2011/05',
			'dat_order' => '2011-02-10',
			'location' => '',
			'descript' => '',
			'task' => '',
			'taskee' => '',
			'dat_task' => '2011-02-10',
			'departure' => '2011-02-10 12:45:00',
			'arrival' => '2011-02-10 13:45:00',
			'vehicle_title' => '',
			'vehicle_registration' => '',
			'vehicle_owner' => '',
			'advance' => '0.00',
			'dat_advance' => NULL,
			'total' => 37.00,
			'created' => '2011-02-10 11:48:17',
			'modified' => '2011-02-10 11:48:17'
		),
		array(
			'id' => '4d53c54d-cc84-4bdb-8143-0c6c025da8b9',
			'payer_id' => '',
			'employee_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'vehicle_id' => NULL,
			'counter_id' => '4d048d88-9070-4e13-b565-463554ffceb9',
			'counter' => 6,
			'no' => '2011/06',
			'dat_order' => '2011-02-10',
			'location' => '',
			'descript' => '',
			'task' => '',
			'taskee' => '',
			'dat_task' => '2011-02-10',
			'departure' => '2011-02-10 11:00:00',
			'arrival' => '2011-02-10 11:00:00',
			'vehicle_title' => '',
			'vehicle_registration' => '',
			'vehicle_owner' => '',
			'advance' => '0.00',
			'dat_advance' => NULL,
			'total' => 40.70,
			'created' => '2011-02-10 12:00:29',
			'modified' => '2011-02-10 12:00:29'
		),
	);
}