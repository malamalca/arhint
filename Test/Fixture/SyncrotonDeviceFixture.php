<?php
/**
 * SyncrotonDeviceFixture
 *
 */
class SyncrotonDeviceFixture extends CakeTestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $table = 'syncroton_device';

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'deviceid' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'devicetype' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'owner_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'acsversion' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'policykey' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'policy_id' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'useragent' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'imei' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'model' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'friendlyname' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'os' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'oslanguage' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'phonenumber' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'pinglifetime' => array('type' => 'integer', 'null' => true, 'default' => null),
		'remotewipe' => array('type' => 'integer', 'null' => true, 'default' => '0'),
		'pingfolder' => array('type' => 'binary', 'null' => true, 'default' => null),
		'lastsynccollection' => array('type' => 'binary', 'null' => true, 'default' => null),
		'lastping' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'contactsfilter_id' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'calendarfilter_id' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'tasksfilter_id' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'emailfilter_id' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'owner_id--deviceid' => array('column' => array('owner_id', 'deviceid'), 'unique' => 1)
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
			'deviceid' => 'Lorem ipsum dolor sit amet',
			'devicetype' => 'Lorem ipsum dolor sit amet',
			'owner_id' => 'Lorem ipsum dolor sit amet',
			'acsversion' => 'Lorem ipsum dolor sit amet',
			'policykey' => 'Lorem ipsum dolor sit amet',
			'policy_id' => 'Lorem ipsum dolor sit amet',
			'useragent' => 'Lorem ipsum dolor sit amet',
			'imei' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'friendlyname' => 'Lorem ipsum dolor sit amet',
			'os' => 'Lorem ipsum dolor sit amet',
			'oslanguage' => 'Lorem ipsum dolor sit amet',
			'phonenumber' => 'Lorem ipsum dolor sit amet',
			'pinglifetime' => 1,
			'remotewipe' => 1,
			'pingfolder' => 'Lorem ipsum dolor sit amet',
			'lastsynccollection' => 'Lorem ipsum dolor sit amet',
			'lastping' => '2013-10-24 15:51:19',
			'contactsfilter_id' => 'Lorem ipsum dolor sit amet',
			'calendarfilter_id' => 'Lorem ipsum dolor sit amet',
			'tasksfilter_id' => 'Lorem ipsum dolor sit amet',
			'emailfilter_id' => 'Lorem ipsum dolor sit amet'
		),
	);

}
