<?php
/**
 * SyncrotonDatumFixture
 *
 */
class SyncrotonDatumFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'class' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'folder_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'creation_time' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'last_modified_time' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'data' => array('type' => 'binary', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
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
			'class' => 'Lorem ipsum dolor sit amet',
			'folder_id' => 'Lorem ipsum dolor sit amet',
			'creation_time' => '2013-10-24 15:51:15',
			'last_modified_time' => '2013-10-24 15:51:15',
			'data' => 'Lorem ipsum dolor sit amet'
		),
	);

}
