<?php
/* Attachment Fixture generated on: 2011-06-12 08:17:46 : 1307859466 */

/**
 * AttachmentFixture
 *
 */
class AttachmentFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'model' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'foreign_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'filename' => array('type' => 'string', 'null' => true, 'default' => NULL, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'original' => array('type' => 'string', 'null' => true, 'default' => NULL, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'ext' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'mimetype' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 30, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'filesize' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'height' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 4, 'collate' => NULL, 'comment' => ''),
		'width' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 4, 'collate' => NULL, 'comment' => ''),
		'title' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'description' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
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
			'id' => '4d334669-3e10-4e51-8889-108c025da8b9',
			'model' => 'Post',
			'foreign_id' => '19',
			'filename' => '4d3346698a4fa',
			'original' => 'arhim.gif',
			'ext' => 'gif',
			'mimetype' => 'image/gif',
			'filesize' => 7029,
			'height' => 176,
			'width' => 514,
			'title' => NULL,
			'description' => NULL,
			'created' => '2011-01-16 20:26:33',
			'modified' => '2011-01-16 20:26:33'
		),
		array(
			'id' => '4d39d403-6dd4-403b-8b45-0484025da8b9',
			'model' => 'Post',
			'foreign_id' => '19',
			'filename' => '4d39d4035eb19',
			'original' => 'noga.png',
			'ext' => 'png',
			'mimetype' => 'image/png',
			'filesize' => 18609,
			'height' => 150,
			'width' => 1600,
			'title' => NULL,
			'description' => NULL,
			'created' => '2011-01-21 19:44:19',
			'modified' => '2011-01-21 19:44:19'
		),
		array(
			'id' => '4d39d4f5-5d38-4654-a7b9-0484025da8b9',
			'model' => 'Post',
			'foreign_id' => '20',
			'filename' => '4d39d4f51bde1',
			'original' => 'noga.png',
			'ext' => 'png',
			'mimetype' => 'image/png',
			'filesize' => 18609,
			'height' => 150,
			'width' => 1600,
			'title' => NULL,
			'description' => NULL,
			'created' => '2011-01-21 19:48:21',
			'modified' => '2011-01-21 19:48:21'
		),
		array(
			'id' => '4d39db50-eda0-40e4-bf2b-0484025da8b9',
			'model' => NULL,
			'foreign_id' => NULL,
			'filename' => NULL,
			'original' => NULL,
			'ext' => NULL,
			'mimetype' => NULL,
			'filesize' => NULL,
			'height' => NULL,
			'width' => NULL,
			'title' => NULL,
			'description' => NULL,
			'created' => '2011-01-21 20:15:28',
			'modified' => '2011-01-21 20:15:28'
		),
		array(
			'id' => '4d39db4a-29a4-4d81-9413-0484025da8b9',
			'model' => NULL,
			'foreign_id' => NULL,
			'filename' => NULL,
			'original' => NULL,
			'ext' => NULL,
			'mimetype' => NULL,
			'filesize' => NULL,
			'height' => NULL,
			'width' => NULL,
			'title' => NULL,
			'description' => NULL,
			'created' => '2011-01-21 20:15:22',
			'modified' => '2011-01-21 20:15:22'
		),
		array(
			'id' => '4d41b3db-4ab0-42c7-88d5-1d7c025da8b9',
			'model' => NULL,
			'foreign_id' => NULL,
			'filename' => NULL,
			'original' => NULL,
			'ext' => NULL,
			'mimetype' => NULL,
			'filesize' => NULL,
			'height' => NULL,
			'width' => NULL,
			'title' => NULL,
			'description' => NULL,
			'created' => '2011-01-27 19:05:15',
			'modified' => '2011-01-27 19:05:15'
		),
		array(
			'id' => '4d41b7e4-f0ec-458e-a8f5-1d7c025da8b9',
			'model' => NULL,
			'foreign_id' => NULL,
			'filename' => NULL,
			'original' => NULL,
			'ext' => NULL,
			'mimetype' => NULL,
			'filesize' => NULL,
			'height' => NULL,
			'width' => NULL,
			'title' => NULL,
			'description' => NULL,
			'created' => '2011-01-27 19:22:28',
			'modified' => '2011-01-27 19:22:28'
		),
		array(
			'id' => '4d4268e9-8b00-48a6-9ca0-1d7c025da8b9',
			'model' => 'Post',
			'foreign_id' => '22',
			'filename' => '4d4268e904a51',
			'original' => 'noga.png',
			'ext' => 'png',
			'mimetype' => 'image/png',
			'filesize' => 18609,
			'height' => 150,
			'width' => 1600,
			'title' => '',
			'description' => NULL,
			'created' => '2011-01-28 07:57:45',
			'modified' => '2011-01-28 07:57:45'
		),
		array(
			'id' => '4dcecb56-7530-463e-9dd9-1354025da8b9',
			'model' => NULL,
			'foreign_id' => NULL,
			'filename' => NULL,
			'original' => NULL,
			'ext' => NULL,
			'mimetype' => NULL,
			'filesize' => NULL,
			'height' => NULL,
			'width' => NULL,
			'title' => NULL,
			'description' => NULL,
			'created' => '2011-05-14 20:35:02',
			'modified' => '2011-05-14 20:35:02'
		),
	);
}
