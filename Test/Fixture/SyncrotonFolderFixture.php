<?php
/**
 * SyncrotonFolderFixture
 *
 */
class SyncrotonFolderFixture extends CakeTestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $table = 'syncroton_folder';

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'device_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'class' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'folderid' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 254, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'parentid' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 254, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'displayname' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 254, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'type' => array('type' => 'integer', 'null' => false, 'default' => null),
		'creation_time' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'lastfiltertype' => array('type' => 'integer', 'null' => true, 'default' => null),
		'supportedfields' => array('type' => 'binary', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'device_id--class--folderid' => array('column' => array('device_id', 'class', 'folderid'), 'unique' => 1, 'length' => array('class' => '40', 'folderid' => '40')),
			'folderstates::device_id--devices::id' => array('column' => 'device_id', 'unique' => 0)
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
			'class' => 'Lorem ipsum dolor sit amet',
			'folderid' => 'Lorem ipsum dolor sit amet',
			'parentid' => 'Lorem ipsum dolor sit amet',
			'displayname' => 'Lorem ipsum dolor sit amet',
			'type' => 1,
			'creation_time' => '2013-10-24 15:51:22',
			'lastfiltertype' => 1,
			'supportedfields' => 'Lorem ipsum dolor sit amet'
		),
	);

}
