<?php
/* PaymentsExpense Fixture generated on: 2011-06-12 20:30:14 : 1307903414 */

/**
 * PaymentsExpenseFixture
 *
 */
class PaymentsExpenseFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'payment_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'expense_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
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
			'id' => 33,
			'payment_id' => '4d2977b4-7f3c-4061-a883-48c454ffceb9',
			'expense_id' => '4cf29707-05dc-4dbe-9af8-604a025da8b9'
		),
		array(
			'id' => 34,
			'payment_id' => '4d29782f-f2dc-441f-8d93-494c54ffceb9',
			'expense_id' => '4cf29707-003c-4a39-b58a-604a025da8b9'
		),
		array(
			'id' => 35,
			'payment_id' => '4d297a99-feec-488b-a8b3-466d54ffceb9',
			'expense_id' => '4cf29707-d708-4388-9583-604a025da8b9'
		),
		array(
			'id' => 36,
			'payment_id' => '4d297b37-145c-42a7-834d-43a754ffceb9',
			'expense_id' => '4cf29708-2670-4ef8-9c40-604a025da8b9'
		),
		array(
			'id' => 37,
			'payment_id' => '4d297b74-3c00-4a5d-8f54-495554ffceb9',
			'expense_id' => '4cf29708-65f0-4b36-aaed-604a025da8b9'
		),
		array(
			'id' => 38,
			'payment_id' => '4d297ba6-945c-47a2-a590-466554ffceb9',
			'expense_id' => '4cf29707-bf5c-4d95-9bec-604a025da8b9'
		),
		array(
			'id' => 39,
			'payment_id' => '4d297cbe-5f7c-4def-b6e3-4eba54ffceb9',
			'expense_id' => '4d297c2a-711c-443e-afd1-45b754ffceb9'
		),
		array(
			'id' => 40,
			'payment_id' => '4d297d2e-58ec-4049-858e-459854ffceb9',
			'expense_id' => '4cf2b455-cdf4-46b6-bb61-621854ffceb9'
		),
		array(
			'id' => 41,
			'payment_id' => '4d297da0-0648-4954-b5fd-41b954ffceb9',
			'expense_id' => '4d297d67-ba98-4020-9a82-483e54ffceb9'
		),
		array(
			'id' => 42,
			'payment_id' => '4d29830a-8fb4-421e-8336-431a54ffceb9',
			'expense_id' => '4cf29707-e0d4-467a-a94b-604a025da8b9'
		),
	);
}
