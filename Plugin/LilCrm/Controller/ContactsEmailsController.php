<?php
App::import('Controller', 'Lil.LilApp');

class ContactsEmailsController extends LilAppController {

	var $name = 'ContactsEmails';
	
	function admin_add($contact_id = null) {
		$this->setAction('admin_edit', null, $contact_id);
		$this->view = 'admin_edit';
	}

	function admin_edit($id = null, $contact_id = null) {
		if (!empty($this->request->data)) {
			if ($this->ContactsEmail->save($this->request->data)) {
				$this->setFlash(__d('lil_crm', 'Email has been successfully saved.'));
				return $this->doRedirect(array(
					'admin'      => true,
					'plugin'     => 'lil_crm',
					'controller' => 'contacts',
					'action'     => 'view',
					$this->data['ContactsEmail']['contact_id'],
					'highlight'=>$this->ContactsEmail->id
				));
			}
			$this->setFlash(__d('lil_crm', 'There are some errors in the form. Please correct all marked fields below.'), 'error');
		}
		if (empty($this->request->data)) {
			if (is_numeric($id) && ($this->request->data = $this->ContactsEmail->find('first', array(
				'conditions' => array(
					'ContactsEmail.id' => $id
				),
				'contain' => 'Contact'
			)))) {

			} elseif (!empty($contact_id) && $this->ContactsEmail->Contact->findById($contact_id)) {
				$this->request->data['ContactsEmail']['contact_id'] = $contact_id;
			} else {
				return $this->error404();
			}
			
			$this->setupRedirect();
		}
		
		$this->set('contact_title', $this->ContactsEmail->Contact->getTitle(@$this->request->data['ContactsEmail']['contact_id']));
	}

	function admin_delete($id = null) {
		if (is_numeric($id) && $this->ContactsEmail->delete($id)) {
			$this->setFlash(__d('lil_crm', 'Email has been successfully deleted.'));
			$this->redirect($this->referer());
		} else { 			
			$this->error404();
		}
	}
}