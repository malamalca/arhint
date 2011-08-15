<?php
/* TravelOrdersCounter Fixture generated on: 2011-07-05 08:51:07 : 1309848667 */

/**
 * TravelOrdersCounterFixture
 *
 */
class TravelOrdersCounterFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'kind' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'counter' => array('type' => 'integer', 'null' => false, 'default' => '0', 'collate' => NULL, 'comment' => ''),
		'expense' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'collate' => NULL, 'comment' => ''),
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
			'counter' => 7,
			'expense' => '1',
			'title' => 'Izdani računi 2010',
			'mask' => '[[year]]/[[no.2]]',
			'layout' => 'issued',
			'layout_title' => 'Račun št. [[no]]',
			'template_descript' => 'DDV ni obračunan v skladu s 1. odstavkom 94. člena ZDDV-1.\r\n\r\nRačun je plačljiv v zgoraj navedenem roku. Po preteku roka si pridržujemo pravico zaračunati zakonite zamudne obresti.\r\n\r\nMiha Nahtigal',
			'header' => '4d0527842049f.png',
			'footer' => NULL,
			'active' => '1',
			'modified' => '2010-12-12 20:50:28',
			'created' => '2010-10-22 10:56:11'
		),
		array(
			'id' => '4cf29d0e-158c-4d74-8eb6-60bb025da8b9',
			'kind' => 'issued',
			'counter' => 4,
			'expense' => '0',
			'title' => 'Predračuni 2010',
			'mask' => '[[year]]/[[no.2]]',
			'layout' => 'estimate',
			'layout_title' => 'Predračun št. [[no]]',
			'template_descript' => 'DDV ni obračunan v skladu s 1. odstavkom 94. člena ZDDV-1.\r\n\r\nVeljavnost predračuna: 14 dni\r\n\r\nZa sklic uporabite številko predračuna.\r\n\r\nMiha Nahtigal',
			'header' => '4d0527842049f.png',
			'footer' => NULL,
			'active' => '1',
			'modified' => '2010-12-27 10:06:04',
			'created' => '2010-10-25 18:15:34'
		),
		array(
			'id' => '4cf29d0e-1300-4e47-afa2-60bb025da8b9',
			'kind' => 'received',
			'counter' => 78,
			'expense' => '1',
			'title' => 'Prejeti računi',
			'mask' => '',
			'layout' => 'received',
			'layout_title' => 'Prejeti račun [[no]]',
			'template_descript' => '',
			'header' => NULL,
			'footer' => NULL,
			'active' => '1',
			'modified' => '2011-01-07 10:07:32',
			'created' => '2010-11-10 18:15:08'
		),
		array(
			'id' => '4cf29d0f-6fbc-4faf-ad22-60bb025da8b9',
			'kind' => 'issued',
			'counter' => 3,
			'expense' => '1',
			'title' => 'Izdani avansni računi 2010',
			'mask' => 'AR-[[year]]-[[no.2]]',
			'layout' => 'advance',
			'layout_title' => 'Avansni račun št. [[no]]',
			'template_descript' => 'Na podlagi predplačila po PR št. _______________ vam izstavljamo avansni račun.',
			'header' => '4d0527842049f.png',
			'footer' => NULL,
			'active' => '1',
			'modified' => '2011-01-07 16:13:51',
			'created' => '2010-11-11 12:58:55'
		),
		array(
			'id' => '4cf29d0f-53c0-48ae-8373-60bb025da8b9',
			'kind' => 'received',
			'counter' => 1,
			'expense' => '1',
			'title' => 'Prejeti avansni računi 2010',
			'mask' => '',
			'layout' => 'received',
			'layout_title' => '[[no]]',
			'template_descript' => '',
			'header' => NULL,
			'footer' => NULL,
			'active' => '1',
			'modified' => '2010-11-13 08:46:32',
			'created' => '2010-11-11 14:51:45'
		),
		array(
			'id' => '4d048d88-9070-4e13-b565-463554ffceb9',
			'kind' => 'travel',
			'counter' => 6,
			'expense' => '1',
			'title' => 'Travel order',
			'mask' => '[[year]]/[[no.2]]',
			'layout' => 'estimate',
			'layout_title' => '[[no]]',
			'template_descript' => '',
			'header' => '4d0527842049f.png',
			'footer' => '4d388e18419b9.png',
			'active' => '1',
			'modified' => '2010-12-12 09:53:28',
			'created' => '2010-12-12 09:53:28'
		),
		array(
			'id' => '4d26d76b-db60-43a1-af72-455e54ffceb9',
			'kind' => 'received',
			'counter' => 4,
			'expense' => '1',
			'title' => 'Prejeti računi 2011',
			'mask' => '',
			'layout' => 'received',
			'layout_title' => 'Prejeti račun [[no]]',
			'template_descript' => '',
			'header' => NULL,
			'footer' => NULL,
			'active' => '1',
			'modified' => '2011-01-07 10:05:47',
			'created' => '2011-01-07 10:05:47'
		),
		array(
			'id' => 'a17e8e70-6798-11e0-9ce5-b8ac6f7cbae5',
			'kind' => NULL,
			'counter' => 0,
			'expense' => '0',
			'title' => NULL,
			'mask' => NULL,
			'layout' => NULL,
			'layout_title' => NULL,
			'template_descript' => NULL,
			'header' => NULL,
			'footer' => NULL,
			'active' => '1',
			'modified' => NULL,
			'created' => NULL
		),
	);
}
