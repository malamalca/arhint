<?php
App::import('Controller', 'Lil.LilApp');
class ContactsPhonesController extends LilAppController {

	var $name = 'ContactsPhones';

	function admin_add($contact_id = null) {
		$this->setAction('admin_edit', null, $contact_id);
		$this->view = 'admin_edit';
	}

	function admin_edit($id = null, $contact_id = null) {
		if (!empty($this->request->data)) {
			if ($this->ContactsPhone->save($this->request->data)) {
				$this->setFlash(__d('lil_crm', 'Phone number has been successfully saved.'));
				return $this->doRedirect(array(
					'admin'      => true,
					'plugin'     => 'lil_crm',
					'controller' => 'contacts',
					'action'     => 'view',
					$this->request->data['ContactsPhone']['contact_id'],
					'highlight'=>$this->ContactsPhone->id
				));
			}
			$this->setFlash(__d('lil_crm', 'There are some errors in the form. Please correct all marked fields below.'), 'error');
		}
		if (empty($this->request->data)) {
			if (is_numeric($id) && ($this->request->data = $this->ContactsPhone->read(null, $id))) {
			} elseif (!empty($contact_id) && $this->ContactsPhone->Contact->findById($contact_id)) {
				$this->request->data['ContactsPhone']['contact_id'] = $contact_id;
			} else {
				return $this->error404();
			}
			
			$this->setupRedirect();
		}
		
		$this->set('contact_title', $this->ContactsPhone->Contact->getTitle(@$this->request->data['ContactsPhone']['contact_id']));
	}

	function admin_delete($id = null) {
		if (is_numeric($id) && $this->ContactsPhone->delete($id)) {
			$this->setFlash(__d('lil_crm', 'Phone has been successfully deleted.'));
			$this->redirect($this->referer());
		} else { 			
			$this->error404();
		}
	}
}