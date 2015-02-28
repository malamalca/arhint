<?php
/**
 * SyncrotonDataFolderFixture
 *
 */
class SyncrotonDataFolderFixture extends CakeTestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $table = 'syncroton_data_folder';

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'owner_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'type' => array('type' => 'integer', 'null' => false, 'default' => null),
		'name' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'parent_id' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'creation_time' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'last_modified_time' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => array('id', 'owner_id'), 'unique' => 1)
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
			'owner_id' => 'Lorem ipsum dolor sit amet',
			'type' => 1,
			'name' => 'Lorem ipsum dolor sit amet',
			'parent_id' => 'Lorem ipsum dolor sit amet',
			'creation_time' => '2013-10-24 15:50:36',
			'last_modified_time' => '2013-10-24 15:50:36'
		),
	);

}
