<?php
/* ProjectsUser Fixture generated on: 2011-06-14 10:42:55 : 1308040975 */

/**
 * ProjectsUserFixture
 *
 */
class ProjectsUserFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'project_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'user_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
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
			'id' => 1,
			'project_id' => '4c39c00a-7950-48a8-a6cb-1370025da8b9',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb'
		),
		array(
			'id' => 16,
			'project_id' => '4c468be2-28b4-4674-9eda-883f54ffceb9',
			'user_id' => '4c77ad22-e688-48a3-8346-dfc054ffceb9'
		),
		array(
			'id' => 15,
			'project_id' => '4c3bfd82-857c-4ac9-97a2-4b1854ffceb9',
			'user_id' => '4c77ad22-e688-48a3-8346-dfc054ffceb9'
		),
		array(
			'id' => 14,
			'project_id' => '4c3bfd70-8b54-459c-88c6-47ed54ffceb9',
			'user_id' => '4c77ad22-e688-48a3-8346-dfc054ffceb9'
		),
		array(
			'id' => 13,
			'project_id' => '4c3a005e-0e24-4e61-87fb-1370025da8b9',
			'user_id' => '4c77ad22-e688-48a3-8346-dfc054ffceb9'
		),
		array(
			'id' => 17,
			'project_id' => '4c6a7992-832c-4a8a-8429-074d54ffceb9',
			'user_id' => '4c77ad22-e688-48a3-8346-dfc054ffceb9'
		),
	);
}
