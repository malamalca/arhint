<?php
/* Counter Fixture generated on: 2011-06-12 08:08:36 : 1307858916 */

/**
 * CounterFixture
 *
 */
class CounterFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'kind' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'expense' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'collate' => NULL, 'comment' => ''),
		'counter' => array('type' => 'integer', 'null' => false, 'default' => '0', 'collate' => NULL, 'comment' => ''),
		'title' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'mask' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'layout' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'layout_title' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'template_descript' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'header' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'footer' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'collate' => NULL, 'comment' => ''),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
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
			'id' => '4cf29d0e-a834-4bdd-9ab1-60bb025da8b9',
			'kind' => 'issued',
			'expense' => '1',
			'counter' => 52,
			'title' => 'Issued invoices of 2010',
			'mask' => '[[year]]/[[no.2]]',
			'layout' => 'issued',
			'layout_title' => 'Invoice no. [[no]]',
			'template_descript' => 'Additional description template for an issued invoice.',
			'header' => '4d0527842049f.png',
			'footer' => NULL,
			'active' => '1',
			'modified' => '2010-12-12 20:50:28',
			'created' => '2010-10-22 10:56:11'
		),
		array(
			'id' => '4cf29d0e-158c-4d74-8eb6-60bb025da8b9',
			'kind' => 'received',
			'expense' => '0',
			'counter' => 4,
			'title' => 'Received estimates of 2010',
			'mask' => '[[year]]/[[no.2]]',
			'layout' => 'received',
			'layout_title' => 'Estimate no. [[no]]',
			'template_descript' => 'Additional description template for an received estimate.',
			'header' => NULL,
			'footer' => NULL,
			'active' => '1',
			'modified' => '2010-12-27 10:06:04',
			'created' => '2010-10-25 18:15:34'
		),
		array(
			'id' => '4cf29d0e-1300-4e47-afa2-60bb025da8b9',
			'kind' => 'received',
			'expense' => '1',
			'counter' => 39,
			'title' => 'Received invoices od 2010',
			'mask' => '',
			'layout' => 'received',
			'layout_title' => 'Received invoice [[no]]',
			'template_descript' => 'Additional description template for a received invoice.',
			'header' => NULL,
			'footer' => NULL,
			'active' => '1',
			'modified' => '2011-01-07 10:07:32',
			'created' => '2010-11-10 18:15:08'
		),
		// example of an archived counter
		array(
			'id' => '4cd596f1-3854-4bf4-ac59-18f0025da8b9',
			'kind' => 'received',
			'expense' => '1',
			'counter' => 39,
			'title' => 'Received invoices of 2009',
			'mask' => '',
			'layout' => 'received',
			'layout_title' => 'Received invoice [[no]]',
			'template_descript' => 'Additional description template for a received invoice.',
			'header' => NULL,
			'footer' => NULL,
			'active' => '0',
			'modified' => '2011-01-07 10:07:32',
			'created' => '2010-11-10 18:15:08'
		),
	);
}