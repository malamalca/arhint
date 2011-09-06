<?php
/**
 * This is Inject Invoices with Expenses controller test file. 
 *
 */
/**
 * InjectInvoicesControllerTestCase class
 *
 * @package       lil_expensee
 * @subpackage    lil_expensee.test.case.controller
 */
class InjectInvoicesControllerTestCase extends ControllerTestCase {
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
 * testAdminAddReceivedInvoiceToExpensesCounter method
 *
 * @return void
 */
	public function testAdminAddReceivedInvoiceToExpensesCounter() {
		$Invoices = $this->generate('LilInvoices.Invoices', array(
			'components' => array(
				'Session',
				'Auth',
				'RequestHandler' => array('isPut'),
			)
		));
		
		$Invoices->Session->expects($this->once())->method('setFlash');
		
		$InvoicesCounter = ClassRegistry::init('LilInvoices.InvoicesCounter');
		$Expense = ClassRegistry::init('LilExpenses.Expense');
		$PaymentsExpense = ClassRegistry::init('LilExpenses.PaymentsExpense');
		$Payment = ClassRegistry::init('LilExpenses.Payment');
		
		$expense_count_before = $Expense->find('count');
		$payment_count_before = $Payment->find('count');
		$payments_expense_count_before = $PaymentsExpense->find('count');
		
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
				'Expense' => array(
					'id'          => '',
					'model'       => 'Invoice',
					'project_id'  => '4c39c00a-7950-48a8-a6cb-1370025da8b9',
					'user_id'     => NULL,
				)
			)
		));
		$this->assertTrue(isset($this->headers['Location']));
		$this->assertEmpty($this->controller->Invoice->validationErrors);
		
		$lastInvoice = $this->controller->Invoice->getLastInsertId();
		$this->assertFalse(empty($lastInvoice));
		
		$data = $this->controller->Invoice->find('first', array('conditions' => array('Invoice.id' => $lastInvoice)));
		$this->assertFalse(empty($data['Invoice']['id']));
		$this->assertEquals($data['Invoice']['no'], 'RI-001');
		//$this->assertFalse(empty($data['Invoice']['expense_id']));
		
		// check if expense gets added properly
		$expense_count_after = $Expense->find('count');
		$this->assertEquals($expense_count_before + 1, $expense_count_after);
		$exp = $Expense->find('first', array('conditions' => array(
			'Expense.model' => 'Invoice',
			'Expense.foreign_id' => $lastInvoice
		)));
		//$this->assertEquals($exp['Expense']['id'], $data['Invoice']['expense_id']);
		$this->assertEquals($exp['Expense']['dat_happened'], '2011-06-16');
		$this->assertEquals($exp['Expense']['title'], 'My first received test invoice');
		$this->assertEquals($exp['Expense']['total'], '-100.00');
		$this->assertEquals($exp['Expense']['project_id'], '4c39c00a-7950-48a8-a6cb-1370025da8b9');
		
		// check if payments get added properly
		$payment_count_after = $Payment->find('count');
		
		$this->assertEquals($payment_count_before + 1, $payment_count_after);
		
		$payments_expense_count_after = $PaymentsExpense->find('count');
		$this->assertEquals($payments_expense_count_before + 1, $payments_expense_count_after);
		
		$pymnt = $PaymentsExpense->find('first', array(
			'conditions' => array(
				'PaymentsExpense.expense_id' => $exp['Expense']['id']
			),
			'contain' => 'Payment'
		));
		$this->assertEquals($pymnt['Payment']['descript'], 'My first received test invoice');
		$this->assertEquals($pymnt['Payment']['dat_happened'], '2011-06-16');
		$this->assertEquals($pymnt['Payment']['amount'], '-100.00');
		$this->assertEquals($pymnt['Payment']['source'], 'c');
	}
/**
 * testAdminAddToNonExpensesCounter method
 *
 * @return void
 */
	public function testAdminAddReceivedInvoiceToNonExpensesCounter() {
		$Invoices = $this->generate('LilInvoices.Invoices', array(
			'components' => array(
				'Session',
				'Auth',
				'RequestHandler' => array('isPut'),
			)
		));
		
		$Invoices->Session->expects($this->once())->method('setFlash');
		
		$InvoicesCounter = ClassRegistry::init('LilInvoices.InvoicesCounter');
		$Expense = ClassRegistry::init('LilExpenses.Expense');
		$PaymentsExpense = ClassRegistry::init('LilExpenses.PaymentsExpense');
		$Payment = ClassRegistry::init('LilExpenses.Payment');
		
		$expense_count_before = $Expense->find('count');
		$payment_count_before = $Payment->find('count');
		$payments_expense_count_before = $PaymentsExpense->find('count');
		
		// this is received invoice that is not expense
		$counter_id = '4cf29d0e-158c-4d74-8eb6-60bb025da8b9';
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
					'no'         => 'RI-002',
					'dat_issue'  => '2011-06-19',
					'dat_service' => '2011-06-18',
					'dat_expire'  => '2011-06-20',
					'total'       => '100,00',
					'project_id'  => '4c39c00a-7950-48a8-a6cb-1370025da8b9',
					'user_id'     => NULL,
					'payment'     => 'c',
					'descript'    => 'Test invoice description'
					
				)
			)
		));
		$this->assertTrue(isset($this->headers['Location']));
		$this->assertEmpty($this->controller->Invoice->validationErrors);
		
		$lastInvoice = $this->controller->Invoice->getLastInsertId();
		$this->assertFalse(empty($lastInvoice));
		
		$data = $this->controller->Invoice->find('first', array('conditions' => array('Invoice.id' => $lastInvoice)));
		$this->assertFalse(empty($data['Invoice']['id']));
		$this->assertEquals($data['Invoice']['no'], 'RI-002');
		$this->assertTrue(empty($data['Invoice']['expense_id']));
		
		// check if expense gets added properly
		$expense_count_after = $Expense->find('count');
		$this->assertEquals($expense_count_before, $expense_count_after);
		
		
		// check if payments get added properly
		$payment_count_after = $Payment->find('count');
		$this->assertEquals($payment_count_before, $payment_count_after);
		
		$payments_expense_count_after = $PaymentsExpense->find('count');
		$this->assertEquals($payments_expense_count_before, $payments_expense_count_after);

	}
/**
 * testAdminAddIssuedInvoiceToExpensesCounter method
 *
 * @return void
 */
	public function testAdminAddIssuedInvoiceToExpensesCounter() {
		$Invoices = $this->generate('LilInvoices.Invoices', array(
			'components' => array(
				'Session',
				'Auth',
				'RequestHandler' => array('isPut'),
			)
		));
		
		$Invoices->Session->expects($this->once())->method('setFlash');
		
		$InvoicesCounter = ClassRegistry::init('LilInvoices.InvoicesCounter');
		$Expense = ClassRegistry::init('LilExpenses.Expense');
		$PaymentsExpense = ClassRegistry::init('LilExpenses.PaymentsExpense');
		$Payment = ClassRegistry::init('LilExpenses.Payment');
		$InvoicesItem = ClassRegistry::init('LilInvoices.InvoicesItem');
		
		$expense_count_before = $Expense->find('count');
		$payment_count_before = $Payment->find('count');
		$payments_expense_count_before = $PaymentsExpense->find('count');
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
					'id'         => '',
					'kind'       => 'issued',
					'counter_id' => $counter_id,
					'counter'    => $Counter->field('counter', array('InvoicesCounter.id' => $counter_id)) + 1,
					'title'      => 'My first issued test invoice',
					'client'     => '4cd5966d-b7e0-4255-a830-17b0025da8b9',
					'contact_id' => '4c36f77a-53a2-4623-b2e0-1370b6e71bbb',
					'no'         => $Counter->generateNo($counter_id),
					'dat_issue'  => '2011-05-16',
					'dat_service' => '2011-05-15',
					'dat_expire'  => '2011-05-17',
					'project_id'  => '4c39c00a-7950-48a8-a6cb-1370025da8b9',
					'user_id'     => NULL,
					'payment'     => 'c',
					'descript'    => 'Test invoice description'
				),
				'Expense' => array(
					'id'          => '',
					'model'       => 'Invoice',
					'project_id'  => '4c39c00a-7950-48a8-a6cb-1370025da8b9',
					'user_id'     => NULL,
				),
				'InvoicesItem' => array(
					array(
						'item_id' => NULL,
						'descript' => 'First item',
						'qty' => '1,0',
						'unit' => 'em',
						'price' => '120,00',
						'tax' => '20,0'
					),
					array(
						'item_id' => NULL,
						'descript' => 'Second item',
						'qty' => '2,0',
						'unit' => 'em',
						'price' => '100,00',
						'tax' => '0,5'
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
		
		// check if expense gets added properly
		$expense_count_after = $Expense->find('count');
		$this->assertEquals($expense_count_before + 1, $expense_count_after);
		$exp = $Expense->find('first', array('conditions' => array(
			'Expense.model' => 'Invoice',
			'Expense.foreign_id' => $lastInvoice
		)));
		
		//$this->assertEquals($exp['Expense']['id'], $data['Invoice']['expense_id']);
		$this->assertEquals($exp['Expense']['dat_happened'], '2011-05-16');
		$this->assertEquals($exp['Expense']['title'], 'My first issued test invoice');
		$this->assertEquals($exp['Expense']['total'], '345.00');
		$this->assertEquals($exp['Expense']['project_id'], '4c39c00a-7950-48a8-a6cb-1370025da8b9');
		
		// check if payments get added properly
		$payment_count_after = $Payment->find('count');
		$this->assertEquals($payment_count_before + 1, $payment_count_after);
		
		$payments_expense_count_after = $PaymentsExpense->find('count');
		$this->assertEquals($payments_expense_count_before + 1, $payments_expense_count_after);
		
		$pymnt = $PaymentsExpense->find('first', array(
			'conditions' => array(
				'PaymentsExpense.expense_id' => $exp['Expense']['id']
			),
			'contain' => 'Payment'
		));
		$this->assertEquals($pymnt['Payment']['descript'], 'My first issued test invoice');
		$this->assertEquals($pymnt['Payment']['dat_happened'], '2011-05-16');
		$this->assertEquals($pymnt['Payment']['amount'], '345.00');
		$this->assertEquals($pymnt['Payment']['source'], 'c');
		
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