<?php
/**
 * This is Invoices controller test file. 
 *
 */
/**
 * InvoicesControllerTestCase class
 *
 * @package       lil_invoices
 * @subpackage    lil_invoices.test.case.controller
 */
class InvoicesControllerTestCase extends ControllerTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.project', 'app.projects_user', 'app.attachment',
		'plugin.lil_expenses.expense', 'plugin.lil_expenses.payments_expense', 'plugin.lil_expenses.payment',
		'plugin.lil_invoices.invoice', 'plugin.lil_invoices.invoices_item', 'plugin.lil_invoices.counter', 
		'plugin.lil.user',
		'plugin.lil_crm.contact', 'plugin.lil_crm.contacts_address', 'plugin.lil_crm.contacts_email', 'plugin.lil_crm.contacts_phone',
		'plugin.lil_travel_orders.travel_order', 'plugin.lil_travel_orders.travel_orders_counter',
		'plugin.lil_travel_orders.travel_orders_expense', 'plugin.lil_travel_orders.travel_orders_item'
	);
/**
 * startTest method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Lil.plugins', array('Crm', 'Invoices'));
	}
/**
 * endTest method
 *
 * @return void
 */
	public function tearDown() {
		ClassRegistry::flush();
		parent::tearDown();
	}
/**
 * testAdminIndex method
 *
 * @return void
 */
	public function testAdminIndex() {
		$Invoices = $this->generate('LilInvoices.Invoices', array(
			'methods' => array(
			),
			'models' => array(
			//	'LilInvoices.Invoice'
			),
			'components' => array(
				'Auth'
			)
		));
		//$Invoices->expects($this->any())->method('isAuthorized')->will($this->returnValue(true));
		//$Invoices->expects($this->exactly(2))->method('filter')->will($this->returnValue(array()));
		$this->testAction('/admin/invoices/index');
		
		$this->assertFalse(isset($this->headers['Location']));
	}
/**
 * testAdminView method
 *
 * @return void
 */
	public function testAdminView() {
		$Invoices = $this->generate('LilInvoices.Invoices', array(
			'components' => array('Auth')
		));
		
		// this is regular invoice
		$this->testAction('/admin/invoices/view/4cea1fe8-4b20-48ba-95cd-4cfc025da8b9');
		$this->assertFalse(isset($this->headers['Location']));
		if ($this->assertTrue(isset($this->controller->viewVars['data']['Invoice']['id']))) {
			$expected = '4cea1fe8-4b20-48ba-95cd-4cfc025da8b9';
			$this->assertEquals($this->controller->viewVars['data']['Invoice']['id'], $expected);
		}
	}
/**
 * testAdminAddReceivedInvoice method
 *
 * @return void
 */
	public function testAdminAddReceivedInvoice() {
		$Invoices = $this->generate('LilInvoices.Invoices', array(
			'components' => array('Session', 'Auth', 'RequestHandler' => array('isPut'))
		));
		
		$Invoices->Session->expects($this->once())->method('setFlash');
		
		$InvoicesCounter = ClassRegistry::init('LilInvoices.InvoicesCounter');
		
		// this is regular received invoice
		$counter_id = '4cf29d0e-1300-4e47-afa2-60bb025da8b9';
		$this->testAction('/admin/invoices/add?filter%5Bcounter%5D=' . $counter_id, array(
			'data' => array(
				'Invoice' => array(
					'id'         => '',
					'kind'       => 'received',
					'counter_id' => $counter_id,
					'counter'    => $Counter->generateNo($counter_id),
					'title'      => 'My first received test invoice',
					'client'     => '4cd5966d-b7e0-4255-a830-17b0025da8b9',
					'contact_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
					'no'         => 'RI-001',
					'dat_issue'  => '2011-06-16',
					'dat_service' => '2011-06-15',
					'dat_expire'  => '2011-06-17',
					'total'       => '100,00',
					'payment'     => 'c',
					'descript'    => 'Test invoice description'
				),
			)
		));
		$this->assertTrue(isset($this->headers['Location']));
		$this->assertEmpty($this->controller->Invoice->validationErrors);
		
		$lastInvoice = $this->controller->Invoice->getLastInsertId();
		$this->assertFalse(empty($lastInvoice));
		
		$data = $this->controller->Invoice->find('first', array('conditions' => array('Invoice.id' => $lastInvoice)));
		$this->assertFalse(empty($data['Invoice']['id']));
		$this->assertEquals($data['Invoice']['no'], 'RI-001');
	}

/**
 * testAdminAddIssuedInvoice method
 *
 * @return void
 */
	public function testAdminAddIssuedInvoice() {
		$Invoices = $this->generate('LilInvoices.Invoices', array(
			'components' => array('Session', 'Auth', 'RequestHandler' => array('isPut'))
		));
		
		$Invoices->Session->expects($this->once())->method('setFlash');
		
		$InvoicesCounter = ClassRegistry::init('LilInvoices.InvoicesCounter');
		$InvoicesItem = ClassRegistry::init('LilInvoices.InvoicesItem');
		
		$invoices_item_count_before = $InvoicesItem->find('count');
		
		// this is regular issued invoice which is treated like expense
		$counter_id = '4cf29d0e-a834-4bdd-9ab1-60bb025da8b9';
		$counter_data = $Counter->find('first', array(
			'conditions' => array('InvoicesCounter.id' => $counter_id),
			'recursive'  => -1
		));
		
		$this->testAction('/admin/invoices/add?filter%5Bcounter%5D=' . $counter_id, array(
			'data' => array(
				'Invoice' => array(
					'id'           => '',
					'kind'         => 'issued',
					'counter_id'   => $counter_id,
					'counter'      => $Counter->field('counter', array('InvoicesCounter.id' => $counter_id)) + 1,
					'title'        => 'My first issued test invoice',
					'client'       => '4cd5966d-b7e0-4255-a830-17b0025da8b9',
					'contact_id'   => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
					'no'           => $Counter->generateNo($counter_id),
					'dat_issue'    => '2011-05-16',
					'dat_service'  => '2011-05-15',
					'dat_expire'   => '2011-05-17',
					'project_id'   => '4c39c00a-7950-48a8-a6cb-1370025da8b9',
					'user_id'      => NULL,
					'payment'      => 'c',
					'descript'     => 'Test invoice description'
				),
				'InvoicesItem' => array(
					array(
						'item_id'  => NULL,
						'descript' => 'First item',
						'qty'      => '1,0',
						'unit'     => 'em',
						'price'    => '120,00',
						'tax'      => '20,0'
					),
					array(
						'item_id'  => NULL,
						'descript' => 'Second item',
						'qty'      => '2,0',
						'unit'     => 'em',
						'price'    => '100,00',
						'tax'      => '0,5'
					)
				)
			)
		));
		$this->assertTrue(isset($this->headers['Location']));
		$this->assertEmpty($this->controller->Invoice->validationErrors);
		
		$lastInvoice = $this->controller->Invoice->getLastInsertId();
		$this->assertFalse(empty($lastInvoice));
		
		
		$data = $this->controller->Invoice->find('first', array('conditions' => array('Invoice.id' => $lastInvoice)));
		$this->assertFalse(empty($data['Invoice']['id']));
		$this->assertEquals($data['Invoice']['counter'], $counter_data['InvoicesCounter']['counter']+1);
		$this->assertEquals($data['Invoice']['no'], $Counter->generateNo($counter_data));
		//$this->assertFalse(empty($data['Invoice']['expense_id']));
		
		// check if items get added properly
		$invoices_item_count_after = $InvoicesItem->find('count');
		$this->assertEquals($invoices_item_count_before + 2, $invoices_item_count_after);
		$pymnt = $InvoicesItem->find('all', array(
			'conditions' => array(
				'InvoicesItem.invoice_id' => $lastInvoice
			),
			'contain' => 'Item'
		));
	}
} 