<?php
/**
 * LilExpensesPluginController
 *
 * This is Lil Plugin for Expenses and Payments
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * LilInvoicesPluginController class
 *
 * @uses          LilPluginController
 */
class LilExpensesPluginController extends LilPluginController {
/**
 * name property
 *
 * @var string
 */
	public $name = 'LilExpensesPlugin';
/**
 * name property
 *
 * @var string
 */
	public $helpers = array('Html', 'Form', 'Time', 'Text', 'Lil.Lil', 'Lil.LilFloat', 'Lil.LilDate');
/**
 * handlers property
 *
 * @var array
 */
	public $handlers = array(
		'before_construct_model' => array('function' => '_beforeConstructModel', 'params' => array()),
		
		'invoice_before_save'     => array('function' => '_beforeSaveInvoice', 'params' => array()),
		'invoice_after_save'     => array('function' => '_afterSaveInvoice', 'params' => array()),
		'form_edit_invoice'      => array('function' => '_modifyInvoiceForm', 'params' => array()),
		
		'admin_sidebar'          => array('function' => '_setAdminSidebar', 'params' => array()),
		'admin_dashboard'        => array('function' => '_modifyDashboard', 'params' => array()),
		
		'view_invoice'       	 => array('function' => '_modifyInvoiceView', 'params' => array()),
	);
/**
 * _beforeConstructModel method
 *
 * Filter users
 *
 * @param mixed $model
 * @param array $data
 * @return bool
 */
	public function _beforeConstructModel($model) {
		if ($model->name == 'Invoice') {
			$model->hasOne['Expense'] = array(
				'className' => 'LilExpenses.Expense',
				'foreignKey' => 'foreign_id',
				'conditions'   => array('Expense.model' => 'Invoice'),
			);
		}
		if ($model->name == 'TravelOrder') {
			$model->hasOne['Expense'] = array(
				'className'  => 'LilExpenses.Expense',
				'foreignKey' => 'foreign_id',
				'conditions' => array('Expense.model' => 'TravelOrder'),
			);
		}
		return true;
	}
/**
 * _afterSaveModel method
 *
 * Update expense
 *
 * @param mixed $model
 * @param array $data
 * @return bool
 */
	public function _afterSaveModel($model, $created) {
		if ($model->name == 'Invoice') {
			$Invoice = ClassRegistry::init('LilInvoices.Invoice');
			$InvoicesCounter = ClassRegistry::init('LilInvoices.InvoicesCounter');
			$Expense = ClassRegistry::init('LilExpenses.Expense');
			
			$counter_id = null;
			if (!$counter_id = $Invoice->field('counter_id', array('Invoice.id' => $model->id))) return false;
			
			$counter = $InvoicesCounter->find('first', array(
				'conditions' => array('InvoicesCounter.id' => $counter_id),
				'recursive'  => -1,
				'fields'     => array('expense', 'kind')
			));
			if (!$counter['InvoicesCounter']['expense']) return true; // if invoice is not expense, we're finished
			
			if (!$expense_data = $Expense->find('first', array(
				'conditions' => array('Expense.model' => 'Invoice', 'Expense.foreign_id' => $model->id),
				'recursive' => -1
			))) {
				$expense_data = array('Expense' => array(
					'id' => null,
					'model' => 'Invoice',
					'foreign_id' => $model->id
				));
			}
			if (isset($model->data['Invoice']['title'])) {
				$expense_data['Expense']['title'] = $model->data['Invoice']['title'];
			}
			if (isset($model->data['Invoice']['dat_issue'])) {
				$expense_data['Expense']['dat_happened'] = $model->data['Invoice']['dat_issue'];
			}
			if (isset($model->data['Invoice']['total'])) {
				// multiply by 1 so we get float format
				if ($counter['InvoicesCounter']['kind'] == 'received') {
					$expense_data['Expense']['total'] = $model->data['Invoice']['total'] * -1;
				} else {
					$expense_data['Expense']['total'] = $model->data['Invoice']['total'] * 1;
				}
			}
			$ret = $Expense->save($expense_data);
		}
		return true;
	}
/**
 * _beforeSaveInvoice method
 *
 * Update expense
 *
 * @param mixed $model
 * @param array $data
 * @return bool
 */
	public function _beforeSaveInvoice($controller, $data) {
		$InvoicesCounter = ClassRegistry::init('LilInvoices.InvoicesCounter');
		$c = $InvoicesCounter->find('first', array(
			'conditions' => array('InvoicesCounter.id' => $data['data']['Invoice']['counter_id']),
			'recursive'  => -1,
			'fields'     => array('expense', 'kind')
		));
		if ($c['InvoicesCounter']['expense']) {
			if (isset($data['data']['Invoice']['title'])) {
				$data['data']['Expense']['title'] = $data['data']['Invoice']['title'];
			}
			if (isset($data['data']['Invoice']['dat_issue'])) {
				$data['data']['Expense']['dat_happened'] = $data['data']['Invoice']['dat_issue'];
			}
			if (isset($data['data']['Invoice']['total'])) {	
				$Expense = ClassRegistry::init('LilExpenses.Expense');
				// multiply by 1 so we get float format
				if ($c['InvoicesCounter']['kind'] == 'received') {
					$data['data']['Expense']['total'] = $Expense->delocalize($data['data']['Invoice']['total']) * -1;
				} else {
					$data['data']['Expense']['total'] = $Expense->delocalize($data['data']['Invoice']['total']) * 1;
				}
			}
		}
		return $data;
	}
/**
 * _afterSaveInvoice method
 *
 * Update expense
 *
 * @param mixed $model
 * @param array $data
 * @return bool
 */
	public function _afterSaveInvoice($controller, $d) {
		if (!empty($d['data']['Invoice']['payment'])) {
			$Payment = ClassRegistry::init('LilExpenses.Payment');
			$PaymentsExpense = ClassRegistry::init('LilExpenses.PaymentsExpense');
			$Expense = ClassRegistry::init('LilExpenses.Expense');
			
			if ($expense_data = $Expense->find('first', array(
				'conditions' => array('Expense.model' => 'Invoice', 'Expense.foreign_id' => $d['data']['Invoice']['id']),
				'recursive' => -1
			))) {
			
				$p_data = array('Payment' => array(
					'amount'       => $expense_data['Expense']['total'] * 1,
					'dat_happened' => $expense_data['Expense']['dat_happened'],
					'source'       => $d['data']['Invoice']['payment'],
					'descript'     => $expense_data['Expense']['title']
				));
				if ($p = $Payment->save($p_data)) {
					$payment_expense = array('PaymentsExpense' => array(
						'payment_id' => $Payment->id,
						'expense_id' => $expense_data['Expense']['id'],
					));
					$PaymentsExpense->save($payment_expense);
				}
			}
		}
		return $d;
	}
/**
 * _modifyInvoiceForm method
 *
 * Adds payments to invoice
 *
 * @param mixed $view
 * @param mixed $panels
 * @return array
 */	
	public function _modifyInvoiceView($view, $data) {
		$Payment = ClassRegistry::init('LilExpenses.Payment');
		$PaymentsExpense = ClassRegistry::init('LilExpenses.PaymentsExpense');
		$Expense = ClassRegistry::init('LilExpenses.Expense');
		
		$expense_id = $Expense->field('id', array(
			'Expense.model' => 'Invoice',
			'Expense.foreign_id' => $data['data']['Invoice']['id']
		));
		$payments = $PaymentsExpense->find('all', array(
			'conditions' => array(
				'PaymentsExpense.expense_id' => $expense_id,
			),
			'contain' => array('Payment')
		));
		$view->set(compact('payments', 'expense_id'));
		
		$data['contents']['panels'][] = $view->element('expenses_view_invoice', array(), array('plugin' => 'LilExpenses'));
		$data['contents']['panels'][] = $view->element('js' . DS . 'popup_payment');
		

	
		return $data;
	}
/**
 * _modifyInvoiceForm method
 *
 * Adds fields to invoice form
 *
 * @param mixed $view
 * @param mixed $form
 * @return array
 */
	public function _modifyInvoiceForm($view, $form) {
		if (!empty($view->viewVars['counter']['InvoicesCounter']['expense'])) {
			$User = ClassRegistry::init('Lil.User');
			$Area = ClassRegistry::init('Lil.Area');
			$users = $User->find('list');
			$projects = $Area->findForUser(null, 'list');
		
			$e = array(
				'fs_expense_start' => '<fieldset>',
				'fs_expense_legend' => sprintf('<legend>%s</legend>', __d('lil_expenses', 'Income and Expenses')),
				'expense_id' => array(
					'class'      => $view->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'Expense.id',
						'options' => array(
							'type' => 'hidden'
						)
					)
				),
				'expense_foreign_id' => array(
					'class'      => $view->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'Expense.foreign_id',
						'options' => array(
							'type' => 'hidden'
						)
					)
				),
				'expense_model' => array(
					'class'      => $view->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'Expense.model',
						'options' => array(
							'type' => 'hidden',
							'default' => 'Invoice'
						)
					)
				),
				'expense_project_id' => array(
					'class'      => $view->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'Expense.project_id',
						'options' => array(
							'label' => __d('lil_expenses', 'Project') . ':',
							'options' => $projects,
							'empty'   => __d('lil_expenses', '-- select project --'),
							'default' => $this->currentArea->get('id')
						)
					)
				),
				// show payment dropdown only when adding an invoice
				'payment' => $view->Form->value('Invoice.id') ? null : array(
					'class'      => $view->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'Invoice.payment',
						'options' => array(
							'label' => __d('lil_expenses', 'Payment') . ':',
							'type' => 'select',
							'empty' => '-- ' . __d('lil_expenses', 'do not create payment') . ' --',
							'options' => array(
								'c' => __d('lil_expenses', 'paid from company account'),
								'p' => __d('lil_expenses', 'paid from private account'),
								'o' => __d('lil_expenses', 'paid from other source')
							)
						)
					)
				),
				'user_id' => 
					($this->currentUser->role('admin') && (sizeof($users) > 1)) ?
						array(
							'class'      => $view->LilForm,
							'method'     => 'input',
							'parameters' => array(
								'field' => 'Expense.user_id',
								'options' => array(
									'type'    => 'select',
									'options' => $users,
									'label'   => __d('lil_expenses', 'User') . ':',
									'default' => $this->currentUser->get('id')
								)
							)
						)
					:
						array(
							'class'      => $view->LilForm,
							'method'     => 'input',
							'parameters' => array(
								'field' => 'Expense.user_id',
								'options' => array(
									'type'    => 'hidden',
									'default' => $this->currentUser->get('id')
								)
							)
						)
					,
				'fs_expense_end' => '</fieldset>'
			);
		
			$this->insertIntoArray(
				$form['form']['lines'],
				$e,
				array('after' => 'fs_analytics_end')
			);
		}
		
		return $form;
	}
/**
 * _setAdminSidebar method
 *
 * Add admin sidebar elements.
 *
 * @param mixed $controller
 * @param mixed $sidebar
 * @return array
 */
	public function _setAdminSidebar($controller, $sidebar) {
		$app['title'] = __d('lil_expenses', 'Income and Expenses');
		$app['visible'] = true;
		$app['active'] = in_array($this->request->params['controller'], array('expenses', 'payments'));
		$app['url'] = array(
			'admin'      => true,
			'plugin'     => 'lil_expenses',
			'controller' => 'expenses',
			'action'     => 'index',
		);
		
		$app['items'] = array(
			'app_expenses' => array(
				'visible' => true,
				'title' => __d('lil_expenses', 'Income and Expenses'),
				'url'   => array(
					'plugin'     => 'lil_expenses',
					'controller' => 'expenses',
					'action'     => 'index',
					'admin'      => true,
				),
				'params' => array(),
				'active' => in_array($this->request->params['controller'], array('expenses')),
				'expand' => false,
				
			),
			'app_payments' => array(
				'visible' => true,
				'title' => __d('lil_expenses', 'Payments'),
				'url'   => array(
					'plugin'     => 'lil_expenses',
					'controller' => 'payments',
					'action'     => 'index',
					'admin'      => true,
				),
				'params' => array(),
				'active' => in_array($this->request->params['controller'], array('payments')) && empty($this->request->query['filter']['source']),
				'expand' => in_array($this->request->params['controller'], array('payments')),
				'submenu' => array(
					'c' => array(
						'visible' => true,
						'title' => __d('lil_expenses', 'Company account'),
						'url' => array('action' => 'index', '?' => array('filter' => array('source' => 'c'))),
						'active' => (!empty($this->request->query['filter']['source']) && $this->request->query['filter']['source']=='c')
					),
					'p' => array(
						'visible' => true,
						'title' => __d('lil_expenses', 'Personal account'),
						'url' => array('action' => 'index', '?' => array('filter' => array('source' => 'p'))),
						'active' => (!empty($this->request->query['filter']['source']) && $this->request->query['filter']['source']=='p')
					),
					'o' => array(
						'visible' => true,
						'title' => __d('lil_expenses', 'Other account'),
						'url' => array('action' => 'index', '?' => array('filter' => array('source' => 'o'))),
						'active' => (!empty($this->request->query['filter']['source']) && $this->request->query['filter']['source']=='o')
					)
					
				)
			),
		);
		
		// insert into sidebar right after welcome panel
		$this->sidebarInsertPanel($sidebar, array('after' => 'welcome'), array('app' => $app));
		
		return $sidebar;
	}
/**
 * _modifyDashboard method
 *
 * Add dashboard panel with latest expenses
 *
 * @param mixed $view
 * @param mixed $dashboard
 * @return array
 */
	public function _modifyDashboard($view, $dashboard) {
		$this->autoRender = false;
		$this->autoLayout = false;
		
		$Expense = ClassRegistry::init('LilExpenses.Expense');
		
		$expense_filter = array();
		if ($area_id = $this->currentArea->get('id')) $expense_filter['Project'] = $area_id;
		$expense_defaults = array('order' => 'dat_happened DESC', 'limit' => 5);
		$expenses = $Expense->find('all', array_merge($expense_defaults, $Expense->filter($expense_filter)));
		
		// payments on first page only
		if (!($area_id = $this->currentArea->get('id'))) {
			$Payment = ClassRegistry::init('LilInvoices.Payment');
			$payments = $Payment->find('all', array(
				'conditions' => array(
					'Payment.dat_happened >=' => strftime('%Y-%m-01', time())
				),
				'order' => 'Payment.dat_happened DESC, Payment.created DESC',
				'recursive' => -1
			));
			$payments = array_reverse($payments);
			// get total sum regardless of pagiantion
			$first = reset($payments);
			$saldo = $Payment->find('first', array(
				'conditions' => array(
					'OR' => array(
						0 => array('Payment.dat_happened <' => $first['Payment']['dat_happened']),
						1 => array(
							'Payment.dat_happened' => $first['Payment']['dat_happened'],
							'Payment.created <' => $first['Payment']['created']
						)
					)
				),
				'fields' => array('SUM(amount) AS saldo')
			));
			$saldo = $saldo[0]['saldo'];
		}
		
		
		$view->set(compact('expenses', 'payments', 'saldo'));
		
		$exp = array(
			'pre'  => '',
			'post' => '',
			'html' => $view->element('expenses_modify_dashboard', array(), array('plugin' => 'LilExpenses'))
		);
		$dashboard['panels']['expenses'] = $exp;
		
		return $dashboard;
	}
}