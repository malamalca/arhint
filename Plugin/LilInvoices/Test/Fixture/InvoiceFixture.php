<?php
/* Invoice Fixture generated on: 2011-06-12 08:07:35 : 1307858855 */

/**
 * InvoiceFixture
 *
 */
class InvoiceFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'contact_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'counter_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'expense_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'project_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'user_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'kind' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'counter' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'no' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'title' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'descript' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'dat_issue' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'dat_service' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'dat_expire' => array('type' => 'date', 'null' => true, 'default' => NULL, 'collate' => NULL, 'comment' => ''),
		'total' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '15,2', 'collate' => NULL, 'comment' => ''),
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
			'id' => '4cea1fe8-4b20-48ba-95cd-4cfc025da8b9',
			'contact_id' => '4cd5966d-b7e0-4255-a830-17b0025da8b9',
			'counter_id' => '4cf29d0e-a834-4bdd-9ab1-60bb025da8b9',
			'expense_id' => '4cf29707-54e4-4480-a06b-604a025da8b9',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'issued',
			'counter' => 1,
			'no' => '2010-01',
			'title' => 'Spletna stran mti-bled.com',
			'descript' => 'DDV ni obračunan v skladu s 1. odstavkom 94. člena ZDDV-1.\r\n\r\nRačun je plačljiv v zgoraj navedenem roku. Po preteku roka si pridržujemo pravico zaračunati zakonite zamudne obresti.\r\n\r\nMiha Nahtigal',
			'dat_issue' => '2010-10-10',
			'dat_service' => '2010-10-10',
			'dat_expire' => '2010-10-20',
			'total' => 500.00,
			'created' => '2010-10-17 18:17:15',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cea1fe8-75d0-4a82-8ca6-4bde025da8b9',
			'contact_id' => '4cd596f1-3854-4bf4-ac59-18f0025da8b9',
			'counter_id' => '4cf29d0e-158c-4d74-8eb6-60bb025da8b9',
			'expense_id' => NULL,
			'user_id' => '',
			'kind' => 'issued',
			'counter' => 1,
			'no' => '2010-01',
			'title' => 'Predračun Puhek',
			'descript' => 'DDV ni obračunan v skladu s 1. odstavkom 94. člena ZDDV-1.\r\n\r\nPlačilni pogoji:\r\n1. Avans v višini 1.000 € do datuma zapadlosti predračuna.\r\n2. V roku 14 dni po vlogi za gradbeno dovoljenje avans v višini 2.300 €.\r\n3. Po zaključku pogodbe plačilo preostalega zneska v višini 2.400 €.\r\n\r\nVeljavnost predračuna: 14 dni\r\n\r\nZa sklic uporabite številko predračuna.\r\n\r\nMiha Nahtigal',
			'dat_issue' => '2010-10-22',
			'dat_service' => '2010-10-22',
			'dat_expire' => '2010-11-06',
			'total' => 5700.00,
			'created' => '2010-10-25 18:16:33',
			'modified' => '2010-11-05 11:07:38'
		),
		array(
			'id' => '4cea1fe8-327c-4ff4-9cc8-48fe025da8b9',
			'contact_id' => '4cd59714-b968-40dd-a81b-1bd8025da8b9',
			'counter_id' => '4cf29d0e-a834-4bdd-9ab1-60bb025da8b9',
			'expense_id' => '4cf29707-e0d4-467a-a94b-604a025da8b9',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'issued',
			'counter' => 2,
			'no' => '2010-02',
			'title' => 'IDZ Kralj',
			'descript' => 'DDV ni obračunan v skladu s 1. odstavkom 94. člena ZDDV-1.\r\n\r\nRačun je plačljiv v zgoraj navedenem roku. Po preteku roka si pridržujemo pravico zaračunati zakonite zamudne obresti.\r\n\r\nMiha Nahtigal',
			'dat_issue' => '2010-10-25',
			'dat_service' => '2010-10-25',
			'dat_expire' => '2010-11-02',
			'total' => 1200.00,
			'created' => '2010-10-25 18:22:54',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cea1fe8-5c24-4a01-abd4-4c7f025da8b9',
			'contact_id' => '4cd5973b-c534-487c-a561-194c025da8b9',
			'counter_id' => '4cf29d0e-158c-4d74-8eb6-60bb025da8b9',
			'expense_id' => NULL,
			'user_id' => '',
			'kind' => 'issued',
			'counter' => 2,
			'no' => '2010-02',
			'title' => 'Predračun Arzenšek',
			'descript' => 'DDV ni obračunan v skladu s 1. odstavkom 94. člena ZDDV-1.\r\n\r\nPlačilni pogoji:\r\n1. 1000 € pred vlogo za gradbeno dovoljenje (takoj po prejetju predračuna).\r\n2. 1500 € po pridobljenem gradbenem dovoljenju.\r\n\r\nVeljavnost predračuna: 2.11.2010\r\n\r\nZa sklic uporabite številko predračuna.\r\n\r\nMiha Nahtigal',
			'dat_issue' => '2010-10-29',
			'dat_service' => '2010-12-01',
			'dat_expire' => '2010-11-02',
			'total' => 2500.00,
			'created' => '2010-10-26 19:43:46',
			'modified' => '2010-11-06 11:18:40'
		),
		array(
			'id' => '4cea1fe8-dbe0-4be3-a093-4bbc025da8b9',
			'contact_id' => '4cdad357-6c7c-4033-8d3e-94a454ffceb9',
			'counter_id' => '4cf29d0e-1300-4e47-afa2-60bb025da8b9',
			'expense_id' => '4cf29707-0ea0-4a95-b561-604a025da8b9',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'received',
			'counter' => 12,
			'no' => '2010019',
			'title' => 'Računovodstvo oktober',
			'descript' => '',
			'dat_issue' => '2010-11-05',
			'dat_service' => '2010-11-04',
			'dat_expire' => '2010-11-13',
			'total' => 100.00,
			'created' => '2010-11-10 18:18:18',
			'modified' => '2010-11-28 20:48:09'
		),
		array(
			'id' => '4cea1fe8-5f98-44d5-8d5b-4a55025da8b9',
			'contact_id' => '4cdbcdf8-4c74-4aa2-a757-a1e954ffceb9',
			'counter_id' => '4cf29d0e-158c-4d74-8eb6-60bb025da8b9',
			'expense_id' => NULL,
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'issued',
			'counter' => 3,
			'no' => '2010-03',
			'title' => 'Dokumentacija - vodilna mapa',
			'descript' => 'DDV ni obračunan v skladu s 1. odstavkom 94. člena ZDDV-1.\r\n\r\nPlačilni pogoji:\r\n1. Avans v višini 800 € do oddaji dokumentacije\r\n2. Po pridobitvi gradbenega dovoljenja plačilo preostalega zneska v višini 200 €.\r\n\r\nVeljavnost predračuna: 14 dni\r\n\r\nZa sklic uporabite številko predračuna.\r\n\r\nMiha Nahtigal',
			'dat_issue' => '2010-11-11',
			'dat_service' => '2010-11-11',
			'dat_expire' => '2010-11-21',
			'total' => 1000.00,
			'created' => '2010-11-11 12:08:39',
			'modified' => '2010-11-11 12:08:39'
		),
		array(
			'id' => '4cea1fe8-0d6c-4d80-867f-4e14025da8b9',
			'contact_id' => '4cd596f1-3854-4bf4-ac59-18f0025da8b9',
			'counter_id' => '4cf29d0f-6fbc-4faf-ad22-60bb025da8b9',
			'expense_id' => '4d011089-5558-4fb1-882e-4f9f54ffceb9',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'issued',
			'counter' => 1,
			'no' => 'AR-2010-01',
			'title' => 'Avansni račun za prvi del',
			'descript' => 'Znesek predračuna: 5.700 €\r\nOstane za plačilo: 4.700 €\r\n\r\nDDV ni obračunan v skladu s 1. odstavkom 94. člena ZDDV-1.\r\n\r\n\r\nNa podlagi predplačila po PR št. 2010-01 vam izstavljamo avansni račun.\r\n\r\nMiha Nahtigal',
			'dat_issue' => '2010-11-02',
			'dat_service' => '2010-12-30',
			'dat_expire' => '2010-11-02',
			'total' => 1000.00,
			'created' => '2010-11-11 13:02:35',
			'modified' => '2011-01-07 16:15:09'
		),
		array(
			'id' => '4cea1fe8-d058-409b-8c69-437e025da8b9',
			'contact_id' => '4cdbeff2-6848-4a9f-bec9-a3a154ffceb9',
			'counter_id' => '4cf29d0e-1300-4e47-afa2-60bb025da8b9',
			'expense_id' => '4cf29707-003c-4a39-b58a-604a025da8b9',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'received',
			'counter' => 2,
			'no' => '601782',
			'title' => 'Papirni nalog (ključek)',
			'descript' => '',
			'dat_issue' => '2010-09-30',
			'dat_service' => '2010-11-11',
			'dat_expire' => '2010-11-11',
			'total' => 2.24,
			'created' => '2010-11-11 14:32:32',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cea1fe8-1ac4-4772-b1f2-42e9025da8b9',
			'contact_id' => '4cdbf0a3-0760-458f-8b30-a5ad54ffceb9',
			'counter_id' => '4cf29d0e-1300-4e47-afa2-60bb025da8b9',
			'expense_id' => '4cf29707-d708-4388-9583-604a025da8b9',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'received',
			'counter' => 1,
			'no' => '04/2010',
			'title' => 'Hiša Brda statika',
			'descript' => '',
			'dat_issue' => '2010-09-29',
			'dat_service' => '2010-09-29',
			'dat_expire' => '2010-10-07',
			'total' => 400.00,
			'created' => '2010-11-11 14:34:57',
			'modified' => '2010-11-28 18:53:11'
		),
		array(
			'id' => '4cea1fe8-eb90-45be-a3ca-41c3025da8b9',
			'contact_id' => '4cdbf13c-3db8-4419-8ec2-a5a854ffceb9',
			'counter_id' => '4cf29d0e-1300-4e47-afa2-60bb025da8b9',
			'expense_id' => '4cf29707-05dc-4dbe-9af8-604a025da8b9',
			'user_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
			'kind' => 'received',
			'counter' => 3,
			'no' => '63-2010-13471',
			'title' => 'Halcom E-Key',
			'descript' => '',
			'dat_issue' => '2010-09-30',
			'dat_service' => '2010-09-30',
			'dat_expire' => '2010-09-30',
			'total' => 99.36,
			'created' => '2010-11-11 14:37:23',
			'modified' => '2010-11-28 18:53:11'
		),
	);
}
