<?php
/* Expense Fixture generated on: 2011-06-12 20:30:05 : 1307903405 */

/**
 * ExpenseFixture
 *
 */
class ExpenseFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'model' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'foreign_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'project_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'user_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'dat_happened' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'title' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 250, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'total' => array('type' => 'float', 'null' => false, 'default' => '0.00', 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
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
			'id' => '4cf29707-54e4-4480-a06b-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-4b20-48ba-95cd-4cfc025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-10-10',
			'title' => 'Spletna stran mti-bled.com',
			'total' => 500.00,
			'created' => '2010-11-28 18:53:11',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cf29707-e0d4-467a-a94b-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-327c-4ff4-9cc8-48fe025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-10-25',
			'title' => 'IDZ Kralj',
			'total' => 1200.00,
			'created' => '2010-11-28 18:53:11',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cf29707-0ea0-4a95-b561-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-dbe0-4be3-a093-4bbc025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-11-05',
			'title' => 'Računovodstvo oktober',
			'total' => -100.00,
			'created' => '2010-11-28 18:53:11',
			'modified' => '2010-11-28 20:48:09'
		),
		array(
			'id' => '4cf29707-003c-4a39-b58a-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-d058-409b-8c69-437e025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-09-30',
			'title' => 'Papirni nalog (ključek)',
			'total' => -2.24,
			'created' => '2010-11-28 18:53:11',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cf29707-d708-4388-9583-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-1ac4-4772-b1f2-42e9025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-09-29',
			'title' => 'Hiša Brda statika',
			'total' => -400.00,
			'created' => '2010-11-28 18:53:11',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cf29707-05dc-4dbe-9af8-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-eb90-45be-a3ca-41c3025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-09-30',
			'title' => 'Halcom E-Key',
			'total' => -99.36,
			'created' => '2010-11-28 18:53:11',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cf29707-bf5c-4d95-9bec-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-4600-4f3f-ac17-4d7f025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-10-05',
			'title' => 'Spino kopiranje Brda',
			'total' => -57.53,
			'created' => '2010-11-28 18:53:12',
			'modified' => '2010-11-28 18:53:12'
		),
		array(
			'id' => '4cf29708-2670-4ef8-9c40-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-ee90-48ef-ae4d-4af9025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-11-05',
			'title' => 'Prvi obrok zavarovanja',
			'total' => -246.18,
			'created' => '2010-11-28 18:53:12',
			'modified' => '2010-11-28 18:53:12'
		),
		array(
			'id' => '4cf29708-e9b4-4efe-a698-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-89d8-485b-acc2-49bf025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-10-12',
			'title' => 'Kopiranje',
			'total' => -30.48,
			'created' => '2010-11-28 18:53:12',
			'modified' => '2010-11-28 18:53:12'
		),
		array(
			'id' => '4cf29708-a6b8-4568-96a4-604a025da8b9',
			'model' => 'Invoice',
			'foreign_id' => '4cea1fe8-d4a8-497e-8ca2-446e025da8b9',
			'project_id' => '',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'dat_happened' => '2010-10-19',
			'title' => 'Priporočeno',
			'total' => -1.60,
			'created' => '2010-11-28 18:53:12',
			'modified' => '2010-11-28 18:53:12'
		),
	);
}
