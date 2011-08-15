<?php
/* Project Fixture generated on: 2011-06-14 10:42:51 : 1308040971 */

/**
 * ProjectFixture
 *
 */
class ProjectFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'slug' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'no' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'descript' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'collate' => NULL, 'comment' => ''),
		'username' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'passwd' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
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
			'id' => '4c39c00a-7950-48a8-a6cb-1370025da8b9',
			'slug' => 'kraska-vila',
			'no' => NULL,
			'name' => 'Kraška vila',
			'descript' => NULL,
			'active' => '0',
			'username' => 'test2',
			'passwd' => '161ed3792d23208adf5def6fe3fa249cb2713b31',
			'created' => '2010-07-11 14:58:50',
			'modified' => '2011-03-26 10:24:48'
		),
		array(
			'id' => '4c3a005e-0e24-4e61-87fb-1370025da8b9',
			'slug' => 'snack-bar-zvezdica',
			'no' => NULL,
			'name' => 'Snack bar Zvezdica',
			'descript' => NULL,
			'active' => '0',
			'username' => NULL,
			'passwd' => NULL,
			'created' => '2010-07-11 19:33:18',
			'modified' => '2011-01-02 09:21:20'
		),
		array(
			'id' => '4c3bfd70-8b54-459c-88c6-47ed54ffceb9',
			'slug' => 'hisa-kralj',
			'no' => NULL,
			'name' => 'Hiša Kralj',
			'descript' => NULL,
			'active' => '1',
			'username' => 'test',
			'passwd' => '086b93ffd3093b3f8e1e6c354b9493d75d2202b8',
			'created' => '2010-07-13 07:45:20',
			'modified' => '2011-01-28 20:24:22'
		),
		array(
			'id' => '4c3bfd82-857c-4ac9-97a2-4b1854ffceb9',
			'slug' => 'zidanica-arzensek',
			'no' => NULL,
			'name' => 'Zidanica Arzenšek',
			'descript' => NULL,
			'active' => '0',
			'username' => '',
			'passwd' => NULL,
			'created' => '2010-07-13 07:45:38',
			'modified' => '2011-01-28 20:10:33'
		),
		array(
			'id' => '4c468be2-28b4-4674-9eda-883f54ffceb9',
			'slug' => 'zupan-vodilna-mapa',
			'no' => NULL,
			'name' => 'Zupan - vodilna mapa',
			'descript' => NULL,
			'active' => '1',
			'username' => NULL,
			'passwd' => NULL,
			'created' => '2010-07-21 07:55:46',
			'modified' => '2010-07-21 07:55:46'
		),
		array(
			'id' => '4c6a7992-832c-4a8a-8429-074d54ffceb9',
			'slug' => 'dule-pojejsi-predsta',
			'no' => NULL,
			'name' => 'Dule - Pojejsi predstavitve',
			'descript' => NULL,
			'active' => '0',
			'username' => '',
			'passwd' => NULL,
			'created' => '2010-08-17 13:59:14',
			'modified' => '2011-01-28 20:12:59'
		),
		array(
			'id' => '4c778ddb-2ee0-4c61-811a-ddb054ffceb9',
			'slug' => 'groznik-rekonstrukci',
			'no' => NULL,
			'name' => 'Groznik - rekonstrukcija hiše',
			'descript' => NULL,
			'active' => '0',
			'username' => NULL,
			'passwd' => NULL,
			'created' => '2010-08-27 12:05:15',
			'modified' => '2011-01-02 09:21:51'
		),
		array(
			'id' => '4d2035a8-ace8-4af1-b634-e1a054ffceb9',
			'slug' => 'hisa-tonge',
			'no' => '1/1',
			'name' => 'Hiša Tonge',
			'descript' => 'test',
			'active' => '1',
			'username' => '',
			'passwd' => NULL,
			'created' => '2011-01-02 09:22:00',
			'modified' => '2011-04-15 09:56:13'
		),
		array(
			'id' => '4d2035b2-e15c-49e7-b03e-e1a054ffceb9',
			'slug' => 'hisa-puhek',
			'no' => NULL,
			'name' => 'Hiša Puhek',
			'descript' => NULL,
			'active' => '1',
			'username' => NULL,
			'passwd' => NULL,
			'created' => '2011-01-02 09:22:10',
			'modified' => '2011-01-02 09:22:31'
		),
		array(
			'id' => '4d2035c1-d4ac-4244-a720-e0b554ffceb9',
			'slug' => 'hisa-zabret',
			'no' => NULL,
			'name' => 'Hiša Zabret',
			'descript' => NULL,
			'active' => '1',
			'username' => NULL,
			'passwd' => NULL,
			'created' => '2011-01-02 09:22:25',
			'modified' => '2011-01-02 09:22:37'
		),
	);
}
