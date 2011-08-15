<?php
/* Payment Fixture generated on: 2011-06-12 20:29:56 : 1307903396 */

/**
 * PaymentFixture
 *
 */
class PaymentFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'dat_happened' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'descript' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'amount' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
		'source' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
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
			'id' => 'dc6d211a-1bcd-11e0-877d-00508bec500c',
			'dat_happened' => '2010-09-27',
			'descript' => 'obresti',
			'amount' => '0.27',
			'source' => 'c',
			'created' => '2010-09-27 12:00:00',
			'modified' => '2011-01-09 10:02:51'
		),
		array(
			'id' => 'dc6d2973-1bcd-11e0-877d-00508bec500c',
			'dat_happened' => '2010-09-27',
			'descript' => 'nakazilo depozita',
			'amount' => 7500.00,
			'source' => 'c',
			'created' => '2010-09-27 12:10:00',
			'modified' => '2011-01-09 10:03:04'
		),
		array(
			'id' => '4d2977b4-7f3c-4061-a883-48c454ffceb9',
			'dat_happened' => '2010-09-29',
			'descript' => 'Halcom E-Key',
			'amount' => -99.36,
			'source' => 'c',
			'created' => '2011-01-09 09:54:12',
			'modified' => '2011-01-09 10:03:55'
		),
		array(
			'id' => '4d29782f-f2dc-441f-8d93-494c54ffceb9',
			'dat_happened' => '2010-09-30',
			'descript' => 'Obračun provizije za Halcom E-Key',
			'amount' => -2.24,
			'source' => 'c',
			'created' => '2011-01-09 09:56:15',
			'modified' => '2011-01-09 10:04:51'
		),
		array(
			'id' => '4d297a4c-5eac-4d63-92e6-435754ffceb9',
			'dat_happened' => '2010-09-30',
			'descript' => 'obresti',
			'amount' => '0.06',
			'source' => 'c',
			'created' => '2011-01-09 10:05:16',
			'modified' => '2011-01-09 10:05:16'
		),
		array(
			'id' => '4d297a99-feec-488b-a8b3-466d54ffceb9',
			'dat_happened' => '2010-10-07',
			'descript' => 'MGrad, Hiša Brda statika',
			'amount' => -400.00,
			'source' => 'c',
			'created' => '2011-01-09 10:06:33',
			'modified' => '2011-01-09 10:06:33'
		),
		array(
			'id' => '4d297b37-145c-42a7-834d-43a754ffceb9',
			'dat_happened' => '2010-10-20',
			'descript' => 'Zavarovanje - prvi obrok',
			'amount' => -246.18,
			'source' => 'c',
			'created' => '2011-01-09 10:09:11',
			'modified' => '2011-01-09 10:11:19'
		),
		array(
			'id' => '4d297b74-3c00-4a5d-8f54-495554ffceb9',
			'dat_happened' => '2010-10-20',
			'descript' => 'AGT ram',
			'amount' => -145.30,
			'source' => 'c',
			'created' => '2011-01-09 10:10:12',
			'modified' => '2011-01-09 10:11:33'
		),
		array(
			'id' => '4d297ba6-945c-47a2-a590-466554ffceb9',
			'dat_happened' => '2010-10-24',
			'descript' => 'Spino kopiranje',
			'amount' => -57.53,
			'source' => 'c',
			'created' => '2011-01-09 10:11:02',
			'modified' => '2011-01-09 10:11:44'
		),
		array(
			'id' => '4d297cbe-5f7c-4def-b6e3-4eba54ffceb9',
			'dat_happened' => '2010-10-21',
			'descript' => 'Osebni dohodek - september',
			'amount' => -221.88,
			'source' => 'c',
			'created' => '2011-01-09 10:15:42',
			'modified' => '2011-01-09 10:15:42'
		),
	);
}
