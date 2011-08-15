<?php
/**
 * LilIntranet
 *
 * This controller will manage Items and Invoices relations
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * InvoicesItems controller
 *
 * @uses          LilAppController
 *
 */
class InvoicesItemsController extends LilAppController {
/**
 * Controller name
 *
 * @var string
 */
	public $name = 'InvoicesItems';
/**
 * Admin delete
 *
 * @return void
 */
	public function admin_delete($id = null) {
		if (is_numeric($id) && $this->InvoicesItem->delete($id)) {
			$this->setFlash(__d('lil_invoices', 'InvoicesItems has been successfully deleted.'));
			$this->redirect($this->referer());
		} else { 			
			$this->error404();
		}
	}
}