<?php
/* ContactsAddress Fixture generated on: 2011-06-12 08:16:26 : 1307859386 */

/**
 * ContactsAddressFixture
 *
 */
class ContactsAddressFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'contact_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'primary' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'collate' => NULL, 'comment' => ''),
		'kind' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'street' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'town' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'zip' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'city' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'country' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
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
			'contact_id' => '4cd5966d-b7e0-4255-a830-17b0025da8b9',
			'primary' => '1',
			'kind' => 'W',
			'street' => 'Devova ulica 5',
			'town' => NULL,
			'zip' => '1117',
			'city' => 'Ljubljana',
			'country' => '',
			'created' => '2010-11-03 13:36:08',
			'modified' => '2010-11-03 13:39:39'
		),
		array(
			'id' => 2,
			'contact_id' => '4cd596f1-3854-4bf4-ac59-18f0025da8b9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Ulica 28. maja 67',
			'town' => NULL,
			'zip' => '1117',
			'city' => 'Ljubljana',
			'country' => '',
			'created' => '2010-11-05 11:05:52',
			'modified' => '2010-11-05 11:06:28'
		),
		array(
			'id' => 3,
			'contact_id' => '4cd59714-b968-40dd-a81b-1bd8025da8b9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Babškova pot 22',
			'town' => NULL,
			'zip' => '1291',
			'city' => 'Škofljica',
			'country' => '',
			'created' => '2010-11-05 11:09:24',
			'modified' => '2010-11-05 11:09:24'
		),
		array(
			'id' => 4,
			'contact_id' => '4cd5973b-c534-487c-a561-194c025da8b9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Videm 97',
			'town' => NULL,
			'zip' => '1312',
			'city' => 'Videm-Dobrepolje',
			'country' => '',
			'created' => '2010-11-06 11:18:08',
			'modified' => '2010-11-06 11:18:08'
		),
		array(
			'id' => 5,
			'contact_id' => '4cdbcdf8-4c74-4aa2-a757-a1e954ffceb9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Cesta na Svetje 7',
			'town' => NULL,
			'zip' => '1215',
			'city' => 'Medvode',
			'country' => '',
			'created' => '2010-11-11 12:23:12',
			'modified' => '2010-11-11 12:23:12'
		),
		array(
			'id' => 6,
			'contact_id' => '4cdbeff2-6848-4a9f-bec9-a3a154ffceb9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Zagrebška cesta 76',
			'town' => NULL,
			'zip' => '2000',
			'city' => 'Maribor',
			'country' => '',
			'created' => '2010-11-11 14:30:51',
			'modified' => '2010-11-11 14:30:51'
		),
		array(
			'id' => 7,
			'contact_id' => '4cdbf0a3-0760-458f-8b30-a5ad54ffceb9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Štihova ulica 8',
			'town' => NULL,
			'zip' => '1000',
			'city' => 'Ljubljana',
			'country' => '',
			'created' => '2010-11-11 14:33:47',
			'modified' => '2010-11-11 14:33:47'
		),
		array(
			'id' => 8,
			'contact_id' => '4cdbf13c-3db8-4419-8ec2-a5a854ffceb9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Tržaška cesta 118',
			'town' => NULL,
			'zip' => '1000',
			'city' => 'Ljubljana',
			'country' => '',
			'created' => '2010-11-11 14:36:14',
			'modified' => '2010-11-11 14:36:14'
		),
		array(
			'id' => 9,
			'contact_id' => '4cdbf1bf-236c-4831-b56a-a49b54ffceb9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Jurčkova cesta 233',
			'town' => NULL,
			'zip' => '1000',
			'city' => 'Ljubljana',
			'country' => '',
			'created' => '2010-11-11 14:38:23',
			'modified' => '2010-11-11 14:38:23'
		),
		array(
			'id' => 10,
			'contact_id' => '4cdbf28b-3664-44f7-80c8-a0ce54ffceb9',
			'primary' => '1',
			'kind' => 'H',
			'street' => 'Miklošičeva cesta 19',
			'town' => NULL,
			'zip' => '1000',
			'city' => 'Ljubljana',
			'country' => '',
			'created' => '2010-11-11 14:41:45',
			'modified' => '2010-11-11 14:41:45'
		),
	);
}
