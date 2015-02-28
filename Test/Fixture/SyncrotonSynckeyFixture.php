<?php
/**
 * SyncrotonSynckeyFixture
 *
 */
class SyncrotonSynckeyFixture extends CakeTestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $table = 'syncroton_synckey';

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'device_id' => array('type' => 'string', 'null' => false, 'length' => 40, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'type' => array('type' => 'string', 'null' => false, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'counter' => array('type' => 'integer', 'null' => false, 'default' => '0'),
		'lastsync' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'pendingdata' => array('type' => 'binary', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'device_id--type--counter' => array('column' => array('device_id', 'type', 'counter'), 'unique' => 1)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 'Lorem ipsum dolor sit amet',
			'device_id' => 'Lorem ipsum dolor sit amet',
			'type' => 'Lorem ipsum dolor sit amet',
			'counter' => 1,
			'lastsync' => '2013-10-24 15:51:29',
			'pendingdata' => 'Lorem ipsum dolor sit amet'
		),
	);

}
