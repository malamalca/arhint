<?php
/**
 * SyncrotonContentFixture
 *
 */
class SyncrotonContentFixture extends CakeTestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $table = 'syncroton_content';

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'device_id' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'folder_id' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'contentid' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'creation_time' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'creation_synckey' => array('type' => 'integer', 'null' => false, 'default' => null),
		'is_deleted' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'device_id--folder_id--contentid' => array('column' => array('device_id', 'folder_id', 'contentid'), 'unique' => 1, 'length' => array('contentid' => '40')),
			'Syncroton_contents::device_id' => array('column' => 'device_id', 'unique' => 0)
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
			'folder_id' => 'Lorem ipsum dolor sit amet',
			'contentid' => 'Lorem ipsum dolor sit amet',
			'creation_time' => '2013-10-24 15:51:10',
			'creation_synckey' => 1,
			'is_deleted' => 1
		),
	);

}
