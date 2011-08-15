<?php
/* Contact Fixture generated on: 2011-06-12 08:16:18 : 1307859378 */

/**
 * ContactFixture
 *
 */
class ContactFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'kind' => array('type' => 'string', 'null' => false, 'default' => 'T', 'length' => 1, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'surname' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'title' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'tax_no' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'tax_status' => array('type' => 'boolean', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'company_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'job' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'username' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'passwd' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'admin' => array('type' => 'boolean', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'tmtr_price' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
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
			'id' => '4cd5966d-b7e0-4255-a830-17b0025da8b9',
			'kind' => 'C',
			'name' => NULL,
			'surname' => NULL,
			'title' => 'MTI d.o.o.',
			'tax_no' => 'SI53560922 ',
			'tax_status' => '1',
			'company_id' => NULL,
			'job' => NULL,
			'username' => '',
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-10-22 11:07:00',
			'modified' => '2011-03-26 10:50:45'
		),
		array(
			'id' => '4cd596f1-3854-4bf4-ac59-18f0025da8b9',
			'kind' => 'T',
			'name' => 'Jure',
			'surname' => 'Puhek',
			'title' => 'Jure Puhek',
			'tax_no' => NULL,
			'tax_status' => NULL,
			'company_id' => NULL,
			'job' => '',
			'username' => NULL,
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-11-05 11:05:29',
			'modified' => '2010-11-05 11:05:29'
		),
		array(
			'id' => '4cd59714-b968-40dd-a81b-1bd8025da8b9',
			'kind' => 'T',
			'name' => 'Janez',
			'surname' => 'Kralj',
			'title' => 'Janez Kralj',
			'tax_no' => NULL,
			'tax_status' => NULL,
			'company_id' => NULL,
			'job' => '',
			'username' => NULL,
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-11-05 11:08:41',
			'modified' => '2010-11-05 11:08:41'
		),
		array(
			'id' => '4cd5973b-c534-487c-a561-194c025da8b9',
			'kind' => 'T',
			'name' => 'Tina',
			'surname' => 'Arzenšek',
			'title' => 'Tina Arzenšek',
			'tax_no' => NULL,
			'tax_status' => NULL,
			'company_id' => NULL,
			'job' => '',
			'username' => NULL,
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-11-06 11:17:40',
			'modified' => '2010-11-06 11:17:40'
		),
		array(
			'id' => '4cd59658-12f4-42f2-8ab4-1bbc025da8b9',
			'kind' => 'C',
			'name' => NULL,
			'surname' => NULL,
			'title' => 'ARHIM d.o.o.',
			'tax_no' => NULL,
			'tax_status' => NULL,
			'company_id' => NULL,
			'job' => NULL,
			'username' => NULL,
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-11-06 18:20:07',
			'modified' => '2010-11-06 18:20:07'
		),
		array(
			'id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'T',
			'name' => 'Miha',
			'surname' => 'Nahtigal',
			'title' => 'Miha Nahtigal',
			'tax_no' => NULL,
			'tax_status' => NULL,
			'company_id' => '4cd59658-12f4-42f2-8ab4-1bbc025da8b9',
			'job' => 'direktor',
			'username' => 'miha.nahtigal',
			'passwd' => '6153207a76c94e0a88e2d3516e6914d397e9e813',
			'admin' => '1',
			'tmtr_price' => 20.00,
			'created' => '2010-11-06 18:20:07',
			'modified' => '2010-11-06 18:20:07'
		),
		array(
			'id' => '4cdad357-6c7c-4033-8d3e-94a454ffceb9',
			'kind' => 'C',
			'name' => NULL,
			'surname' => NULL,
			'title' => 'LEONIS d.o.o.',
			'tax_no' => '966997745',
			'tax_status' => NULL,
			'company_id' => NULL,
			'job' => NULL,
			'username' => NULL,
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-11-10 18:16:07',
			'modified' => '2010-11-10 18:16:07'
		),
		array(
			'id' => '4cdbeff2-6848-4a9f-bec9-a3a154ffceb9',
			'kind' => 'C',
			'name' => NULL,
			'surname' => NULL,
			'title' => 'Raiffeisen Banka d.d.',
			'tax_no' => 'SI29370876',
			'tax_status' => NULL,
			'company_id' => NULL,
			'job' => NULL,
			'username' => NULL,
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-11-11 14:30:26',
			'modified' => '2010-11-11 14:30:26'
		),
		array(
			'id' => '4cdbf0a3-0760-458f-8b30-a5ad54ffceb9',
			'kind' => 'C',
			'name' => NULL,
			'surname' => NULL,
			'title' => 'Mgrad, projektiranje gradbenih konstrukcij in tehnično svetovanje, Miha Brodnjak s.p.',
			'tax_no' => '22102418',
			'tax_status' => NULL,
			'company_id' => NULL,
			'job' => NULL,
			'username' => NULL,
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-11-11 14:33:23',
			'modified' => '2010-11-11 14:33:23'
		),
		array(
			'id' => '4cdbf13c-3db8-4419-8ec2-a5a854ffceb9',
			'kind' => 'C',
			'name' => NULL,
			'surname' => NULL,
			'title' => 'HALCOM d.d.',
			'tax_no' => 'SI43353126',
			'tax_status' => NULL,
			'company_id' => NULL,
			'job' => NULL,
			'username' => NULL,
			'passwd' => NULL,
			'admin' => NULL,
			'tmtr_price' => NULL,
			'created' => '2010-11-11 14:35:56',
			'modified' => '2010-11-11 14:35:56'
		),
	);
}
