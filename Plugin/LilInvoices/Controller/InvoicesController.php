<?php
/**
 * LilIntranet
 *
 * This controller will manage invoices
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_plan.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * InvoicesController class
 *
 * @uses          LilAppController
 */
class InvoicesController extends LilAppController {
/**
 * name property
 *
 * @var string 'Invoices'
 */
	public $name = 'Invoices';
	
	public $uses = array('LilInvoices.Invoice', 'LilInvoices.InvoicesCounter');
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$counter_params = array('fields' => array('id', 'kind', 'active', 'title'));
		
		if (empty($this->request->query['filter']['counter'])) {
			// no counter specified; find first (or default) counter
			$counter_params['conditions'] = array('active' => true, 'kind' => array('received', 'issued'));
			$counter_params['order'] = array('active', 'kind DESC');
		} else {
			$counter_params['conditions'] = array('id' => $this->request->query['filter']['counter']);
		}
		
		// fetch current counter
		if (!$counter = $this->InvoicesCounter->find('first', $counter_params)) return $this->error404();
		
		$this->request->query['filter']['counter'] = $counter['InvoicesCounter']['id']; // needed for sidebar
		$this->request->query['filter']['kind'] = $counter['InvoicesCounter']['kind'];
		
		// fetch invoices
		$filter = $this->Invoice->filter($this->request->query['filter']);
		$data = $this->Invoice->find('all', $filter);
		
		// get total sum regardless of pagiantion
		$total_sum = $this->Invoice->field('SUM(Invoice.total) as total_sum', $filter['conditions']);
		
		$this->set(compact('data', 'filter', 'counter', 'total_sum'));
	}
/**
 * admin_view method
 *
 * @param int $id Invoice id.
 * @access public
 * @return void
 */
	public function admin_view($id = null) {
		if ($data = $this->Invoice->find('first', array(
			'conditions' => array('Invoice.id' => $id),
			'contain' => array(
				'InvoicesItem',
				'InvoicesCounter',
				'Client' => 'PrimaryAddress',
				'Attachment'
			)
		))) {
			$this->request->query['filter']['counter'] = $data['InvoicesCounter']['id']; // sidebar
			$this->set(compact('data'));
		} else $this->error404();
	}
/**
 * admin_view method
 *
 * @param int $id
 * @access public
 * @return void
 */
	public function admin_print($id = null) {
		if ($data = $this->Invoice->find('first', array(
			'conditions' => array('Invoice.id' => $id),
			'contain' => array(
				'InvoicesItem',
				'InvoicesCounter',
				'Client' => 'PrimaryAddress',
				'Attachment'
			)
		))) {
			$this->response->type('pdf');
			$this->set(compact('data'));
			$this->autoRender = false;
			$html = $this->render('layouts' . DS . $data['InvoicesCounter']['layout']);
		} else $this->error404();
	}
/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		$this->setAction('admin_edit');
		$this->view = 'admin_edit';
	}
/**
 * admin_edit method
 *
 * @param int $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (empty($this->request->query['filter']['counter'])) return $this->error404();
		if (!$counter = $this->Invoice->InvoicesCounter->find('first', array(
			'fields'     => array('id', 'counter', 'kind', 'mask', 'template_descript', 'expense'),
			'conditions' => array('id' => $this->request->query['filter']['counter'], 'active' => true),
			'recursive'  => -1
		))) return $this->error404();
		
		if (!empty($this->request->data)) {
			$items_to_delete = array();
			$data = $this->_prepareData($this->request->data, $items_to_delete);
			App::uses('LilPluginRegistry', 'Lil.Lil'); $registry = LilPluginRegistry::getInstance();
			$d = $registry->callPluginHandlers($this, 'invoice_before_save', array(
				'data'            => $data, 
				'items_to_delete' => $items_to_delete
			));
			
			// validate data
			if ($d['data'] && $this->Invoice->saveAll($d['data'], array('validate' => 'only'))) {
				// update counter for new invoices
				if (empty($d['data']['Invoice']['id'])) {
					$no = $this->Invoice->InvoicesCounter->generateNo($counter, true);
					if (empty($d['data']['Invoice']['no'])) $d['data']['Invoice']['no'] = $no;
				}
				
				// do a real save
				if ($this->Invoice->saveAll($d['data'])) {
					$d['data']['Invoice']['id'] = $this->Invoice->id;
					$d = $registry->callPluginHandlers($this, 'invoice_after_save', array(
						'data' => $d['data'], 
						'items_to_delete' => $d['items_to_delete']
					));
					
					foreach ($d['items_to_delete'] as $delete_model => $delete_items) {
						$this->Invoice->{$delete_model}->deleteAll(array($delete_model.'.id' => $delete_items));
					}
					
					$this->setFlash(__d('lil_invoices', 'Invoice has been successfully saved.'));
					return $this->doRedirect(array(
						'action' => 'index',
						'?'      => array('filter' => array('kind' => $d['data']['Invoice']['kind']))
					));
				}
			}
			$this->setFlash(__d('lil_invoices', 'There are some errors in the form. Please correct all marked fields below.'), 'error');

		} else {
			if (!empty($id)) {
				$this->request->data = $this->Invoice->find('first', array(
					'conditions' => array('Invoice.id' => $id),
					'recursive' => 1
				));
				// set client title field for autocomplete
				if (!empty($this->request->data['Client']['title'])) {
					$this->request->data['Invoice']['client'] = $this->request->data['Client']['title'];
				}
			} else {
				// generate counter
				$counter['invoice_no'] = $this->Invoice->InvoicesCounter->generateNo($counter);
				
				$this->request->data['Invoice']['kind'] = $counter['InvoicesCounter']['kind'];
				$this->request->data['Invoice']['counter_id'] = $counter['InvoicesCounter']['id'];
			}
		}
		
		$this->setupRedirect();
		$this->set(compact('counter'));
	}
/**
 * admin_delete method
 *
 * @param int $id
 * @return void
 */
	public function admin_delete($id = null) {
		if (!empty($id) && $this->Invoice->delete($id)) {
			$this->setFlash(__d('lil_invoices', 'Invoice has been successfully deleted.'));
			$this->redirect($this->referer());
		} else $this->error404();
	}
/**
 * admin_attachment method
 *
 * @param mixed $id
 * @return void
 */
	public function admin_attachment($id = null, $name = null) {
		if (!empty($id) && ($atch = $this->Invoice->Attachment->find('first', array(
			'conditions' => array('Attachment.id' => $id)
		)))) {
			$this->viewClass = 'Media';
			
			$data = array(
				'id'   => $atch['Attachment']['filename'],
				'path' => APP . 'uploads' . DS,
				'name' => substr(
					$atch['Attachment']['original'],
					0,
					strlen($atch['Attachment']['original']) - strlen($atch['Attachment']['ext']) - 1
				),
				'extension' => $atch['Attachment']['ext']
			);
			
			$this->set($data);
		} else $this->error404();
	}
/**
 * admin_attachment_delete method
 *
 * @param mixed $id
 * @access public
 * @return void
 */
	public function admin_attachment_delete($id = null) {
		if (!empty($id) && $this->Invoice->Attachment->delete($id)) {
			$this->setFlash(__d('lil_invoices', 'Attachment has been successfully deleted.'));
			$this->redirect($this->referer());
		} else $this->error404();
	}
/**
 * _prepareData method
 *
 * @param mixed $data
 * @param array $items_to_delete
 * @access private
 * @return mixed
 */
	private function _prepareData($data, &$items_to_delete) {
		// remove empty attachments
		if (!empty($data['Attachment']) && is_array($data['Attachment'])) {
			foreach ($data['Attachment'] as $k => $atch) {
				if (empty($atch['filename']['name'])) unset($data['Attachment'][$k]);
			}
		}
		if (empty($data['Attachment'])) unset($data['Attachment']);
		
		// calculate total price
		if (!empty($data['InvoicesItem']) && ($data['Invoice']['kind'] == 'issued')) {
			$data['Invoice']['total'] = 0;
			foreach ($data['InvoicesItem'] as $inv_item) {
				$data['Invoice']['total'] += 
					(round(
						$this->Invoice->delocalize($inv_item['qty']) * 
						$this->Invoice->delocalize($inv_item['price']), 2
					) + 
					round(
						$this->Invoice->delocalize($inv_item['qty']) * 
						$this->Invoice->delocalize($inv_item['price']) *
						$this->Invoice->delocalize($inv_item['tax']) / 100, 2));
			}
		}
		
		// find items to delete
		if (!empty($data['Invoice']['items_to_delete'])) {
			$items_to_delete['InvoicesItem'] = (array)$data['Invoice']['items_to_delete'];
		}
		
		App::uses('LilPluginRegistry', 'Lil.Lil'); $registry = LilPluginRegistry::getInstance();
		$ret = $registry->callPluginHandlers($this, 'invoice_prepare_data', array(
			'data' => $data, 'items_to_delete' => $items_to_delete
		));
		$data = $ret['data']; $items_to_delete = $ret['items_to_delete'];
		
		return $data;
	}
}