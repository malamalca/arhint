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
class DatotekeController extends LilAppController {
/**
 * name property
 *
 * @var string 'Invoices'
 */
	public $name = 'Datoteke';
/**
 * uses property
 *
 * @var array
 */
	public $uses = array();
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
	public function index($project_id = null) {
		$filter = array();
		
		$Area = ClassRegistry::init('Lil.Area');
		$projects = $Area->find('list', array('conditions' => array('Area.active' => true)));
		
		$this->set(compact('projects'));
	}
/**
 * admin_attachment_add method
 *
 * @param mixed $id Invoice id
 * @access public
 * @return void
 */
	public function admin_attachment_add($id = null) {
		$Area = ClassRegistry::init('Lil.Area');
		
		if (!empty($id) && $Area->hasAny(array('Area.id' => $id))) {
			if (!empty($this->request->data)) {
				if (empty($this->request->data['InvoicesAttachment']['filename']['name'])) {
					$this->Invoice->InvoicesAttachment->invalidate('filename', 'empty');
				} else {
					$this->request->data['InvoicesAttachment']['foreign_id'] = $id;
					$this->request->data['InvoicesAttachment']['model'] = 'Invoice';
					if ($this->Invoice->InvoicesAttachment->save($this->request->data)) {
						$this->setFlash(__d('lil_invoices', 'Attachment has been successfully added.'));
						return $this->doRedirect(array('action' => 'view', $id), array('modelClass' => 'LilAttachment'));
					}
				}
				$this->setFlash(__d('lil_invoices', 'There are some errors in the form. Please correct all marked fields below.'), 'error');
			}
			
			$this->setupRedirect('LilAttachment');
			$this->set('invoice_id', $id);
		} else $this->error404();
	}
}