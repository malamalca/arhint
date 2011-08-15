<?php
/* InvoicesItem Fixture generated on: 2011-06-12 08:08:10 : 1307858890 */

/**
 * InvoicesItemFixture
 *
 */
class InvoicesItemFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'invoice_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'item_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'descript' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'qty' => array('type' => 'float', 'null' => false, 'default' => '0.00', 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
		'unit' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 10, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'price' => array('type' => 'float', 'null' => false, 'default' => '0.00', 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
		'tax' => array('type' => 'float', 'null' => false, 'default' => '0.0', 'length' => '4,1', 'collate' => NULL, 'comment' => ''),
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
			'id' => 1,
			'invoice_id' => '4cea1fe8-4b20-48ba-95cd-4cfc025da8b9',
			'item_id' => NULL,
			'descript' => 'Spletna stran mti-bled.com',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 500.00,
			'tax' => '0.0',
			'created' => '2010-10-17 18:17:15',
			'modified' => '2010-10-26 19:41:18'
		),
		array(
			'id' => 2,
			'invoice_id' => '4cea1fe8-75d0-4a82-8ca6-4bde025da8b9',
			'item_id' => NULL,
			'descript' => 'Projektna dokumentacija za enodružinsko hišo \"Puhek\"',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 5700.00,
			'tax' => '0.0',
			'created' => '2010-10-25 18:16:33',
			'modified' => '2010-11-05 11:07:38'
		),
		array(
			'id' => 3,
			'invoice_id' => '4cea1fe8-327c-4ff4-9cc8-48fe025da8b9',
			'item_id' => NULL,
			'descript' => 'Izdelava IDZ dokumentacije',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 1200.00,
			'tax' => '0.0',
			'created' => '2010-10-25 18:22:54',
			'modified' => '2010-11-13 08:48:30'
		),
		array(
			'id' => 4,
			'invoice_id' => '4cea1fe8-5c24-4a01-abd4-4c7f025da8b9',
			'item_id' => NULL,
			'descript' => 'Izdelava PGD dokumentacije za rekonstrukcijo počitniškega objekta',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 2500.00,
			'tax' => '0.0',
			'created' => '2010-10-26 19:43:46',
			'modified' => '2010-11-06 11:18:40'
		),
		array(
			'id' => 5,
			'invoice_id' => '4cea1fe8-5f98-44d5-8d5b-4a55025da8b9',
			'item_id' => NULL,
			'descript' => 'Vodilna mapa za PGD \"Enodružinska hiša Zupan\"',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 1000.00,
			'tax' => '0.0',
			'created' => '2010-11-11 12:08:39',
			'modified' => '2010-11-11 12:08:39'
		),
		array(
			'id' => 6,
			'invoice_id' => '4cea1fe8-0d6c-4d80-867f-4e14025da8b9',
			'item_id' => NULL,
			'descript' => 'Projektna dokumentacija za enodružinsko hišo \"Puhek\"',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 1000.00,
			'tax' => '0.0',
			'created' => '2010-11-11 13:02:35',
			'modified' => '2011-01-07 16:15:09'
		),
		array(
			'id' => 7,
			'invoice_id' => '4cf39076-5ad8-4a98-9715-6ed754ffceb9',
			'item_id' => NULL,
			'descript' => 'Idejna zasnova za koledar',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 1200.00,
			'tax' => '0.0',
			'created' => '2010-11-29 12:37:26',
			'modified' => '2010-11-29 12:37:26'
		),
		array(
			'id' => 8,
			'invoice_id' => '4d18581f-b86c-4139-bbcc-738454ffceb9',
			'item_id' => NULL,
			'descript' => 'PZI projektna dokumentacija',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 3200.00,
			'tax' => '0.0',
			'created' => '2010-12-27 10:10:55',
			'modified' => '2010-12-27 10:12:06'
		),
		array(
			'id' => 9,
			'invoice_id' => '4d1a2212-8a24-4c6a-b608-8b3454ffceb9',
			'item_id' => NULL,
			'descript' => 'Projekt za pridobitev gradbenega dovoljenja za enodružinsko hišo Arzenšek',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 2720.00,
			'tax' => '0.0',
			'created' => '2010-12-28 18:44:50',
			'modified' => '2010-12-29 14:25:09'
		),
		array(
			'id' => 10,
			'invoice_id' => '4d1a22bd-6504-427b-b6e5-8a3654ffceb9',
			'item_id' => NULL,
			'descript' => 'Spletna aplikacija Spamoz',
			'qty' => 1.00,
			'unit' => 'kpl',
			'price' => 2350.00,
			'tax' => '0.0',
			'created' => '2010-12-28 18:47:41',
			'modified' => '2010-12-28 18:47:41'
		),
	);
}
