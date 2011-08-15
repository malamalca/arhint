<?php
/* LilInvoices.Invoice Test cases generated on: 2011-06-12 08:12:44 : 1307859164*/
App::uses('Invoice', 'LilInvoices.Model');

/**
 * Invoice Test Case
 *
 */
class InvoiceTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.expense', 'app.payments_expense', 'plugin.lil.payment', 'app.attachment',
		'plugin.lil_invoices.invoice', 'plugin.lil_invoices.invoices_item', 'plugin.lil_invoices.counter', 
		'plugin.lil.area', 'plugin.lil.user', 'app.projects_user',
		'plugin.lil_crm.contact', 'plugin.lil_crm.contacts_address', 'plugin.lil_crm.contacts_email', 'plugin.lil_crm.contacts_phone',
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Invoice = ClassRegistry::init('LilInvoices.Invoice');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Invoice);
		ClassRegistry::flush();

		parent::tearDown();
	}

/**
 * testAreaInstance method
 *
 * @return void
 */
	function testInvoiceInstance() {
		$this->assertTrue(is_a($this->Invoice, 'Invoice'));
	}
}