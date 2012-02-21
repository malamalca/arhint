<?php
/**
 * Arhim :: Racuni
 *
 * This controller will manage invoices
 *
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * RacuniController class
 *
 * @uses          LilAppController
 */
class RacuniController extends LilAppController {
/**
 * name property
 *
 * @var string 'Invoices'
 */
	public $name = 'Racuni';
/**
 * uses property
 *
 * @var array
 */
	public $uses = array('LilInvoices.Invoice', 'LilInvoices.InvoicesCounter');
/**
 * beforeFilter method
 *
 * @return void
 */
	public function beforeFilter() {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="ARHIM Racuni"');
			header('HTTP/1.0 401 Unauthorized');
			echo __('Napacno uporabnisko ime ali geslo');
			exit;
		} else {
			if (($_SERVER['PHP_AUTH_USER'] != 'leonis') || ($_SERVER['PHP_AUTH_PW'] != 'slava') ) {
				unset($_SERVER['PHP_AUTH_USER']);
				header('HTTP/1.0 401 Unauthorized');
				echo __('Napačno uporabnisko ime ali geslo');
				exit;
			}
		}
			
		$this->Auth->allow('*');
		parent::beforeFilter();
	}
/**
 * index method
 *
 * @return void
 */
	public function index() {
		$filter = array();
		if (!empty($this->params['url']['filter'])) $filter = $this->params['url']['filter'];
		
		// fetch current counter
		if (!$counter = $this->InvoicesCounter->findDefaultCounter($filter)) {
			return $this->error404(__d('lil_invoices', 'Counter does not exist'));
		}
		
		$counters = $this->InvoicesCounter->find('all', array('conditions' => array('InvoicesCounter.active' => 1)));
		
		// fetch invoices
		$filter['counter'] = $counter['InvoicesCounter']['id'];
		$params = array_merge(array('order' => 'Invoice.counter DESC'), $this->Invoice->filter($filter));
		$data = $this->Invoice->find('all', $params);
		
		$this->set(compact('data', 'filter', 'counter', 'counters'));
	}
/**
 * admin_view method
 *
 * @param int $id Invoice id.
 * @return void
 */
	public function view($id = null) {
		if ($data = $this->Invoice->find('first', array(
			'conditions' => array('Invoice.id' => $id),
			'contain' => array(
				'InvoicesItem', 'InvoicesTax' => 'Vat',
				'InvoicesCounter',
				'Client' => 'PrimaryAddress',
				'InvoicesAttachment'
			)
		))) {
			$Vat = ClassRegistry::init('LilInvoices.Vat');
			$vats = $Vat->findList();
		
			$this->set(compact('data', 'vats'));
		} else $this->error404();
	}
/**
 * admin_export method
 *
 * @param string $type Type of export data
 * @param int $id Single Invoice's id to export instead of invoices from $request->query['filter'].
 * @param bool $inline Display export data inline or download as attachment. Default false.
 * @return void
 */
	public function export($type, $id = null, $inline = false) {
		if (!in_array($type, array('pdf'))) {
			return $this->error404(__d('lil_invoices', 'Invalid export type'));
		}
		$filter = array();
		if (!empty($this->request->query['filter'])) $filter = $this->request->query['filter'];
		if (!empty($id)) $filter['invoice'] = $id;
		
		$this->autoRender = false;
		App::uses('LilInvoicesExport', 'LilInvoices.Lib');
		$result = LilInvoicesExport::execute($type, $filter, $inline);
		
		if ($result !== true) {
			$this->setFlash($result, 'error');
			$this->redirect($this->referer());
		}
	}
/**
 * admin_attachment method
 *
 * @param mixed $id
 * @return void
 */
	public function attachment($id = null, $name = null) {
		if (!empty($id) && ($atch = $this->Invoice->InvoicesAttachment->find('first', array(
			'conditions' => array('InvoicesAttachment.id' => $id)
		)))) {
			$this->viewClass = 'Media';
			
			$data = array(
				'id'   => $atch['InvoicesAttachment']['filename'],
				'path' => APP . 'uploads' . DS . 'Invoice' . DS,
				'name' => substr(
					$atch['InvoicesAttachment']['original'],
					0,
					strlen($atch['InvoicesAttachment']['original']) - strlen($atch['InvoicesAttachment']['ext']) - 1
				),
				'extension' => $atch['InvoicesAttachment']['ext']
			);
			
			$this->set($data);
		} else $this->error404();
	}
}