<?php
App::import('Controller', 'Lil.LilApp');
/**
 * ContactsController class
 *
 */
class ContactsController extends LilAppController {
/**
 * name property
 *
 * @var string 'Contacts'
 * @access public
 */
	public $name = 'Contacts';

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$params = array();
		$params['kind'] = strtoupper(@$this->request->params['named']['kind']) == 'C' ? 'C' : 'T';
		
		if (!empty($this->data['search'])) $params['search'] = $this->data['search'];
		$params = array_merge($this->params['named'], $params);
		
		$this->paginate = array(
			'contain'    => array('ContactsEmail', 'ContactsPhone', 'PrimaryAddress', 'Company'),
			'conditions' => $this->Contact->filter($params),
			'order'      => 'Contact.title',
		);
		$contacts = $this->paginate('Contact');
		
		// redirect when only single contact found
		if (sizeof($contacts) == 1 && !empty($params['search'])) {
			$this->redirect(array('action'=>'view', $contacts[0]['Contact']['id']));
		}
		
		$this->set(compact('contacts', 'params'));
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
		if (!empty($this->request->data)) {
			$this->Contact->create();
			
			// find company if it already exists
			if (empty($this->request->data['Contact']['id'])) {
				if (empty($this->request->data['Company']['title'])) {
					unset($this->request->data['Company']);
				}
				$address = @$this->request->data['ContactsAddress'][0];
				if (empty($address['street']) && empty($address['zip']) && empty($address['city']) &&
					empty($address['country'])) unset($this->request->data['ContactsAddress']);
			}
						
			if ($this->Contact->saveAll($this->request->data)) {
				// add id to array for popups
				$this->request->data['Contact']['id'] = $this->Contact->id;
				$this->setFlash(__d('lil_crm', 'Contact has been successfully saved.'));
				return $this->doRedirect(array('action' => 'view', $this->Contact->id));
			} else {
				$this->setFlash(__d('lil_crm', 'There are some errors in the form. Please correct all marked fields below.'), 'error');
			}
		} else {
			if (!empty($id)) {
				if (!$this->request->data = $this->Contact->find('first', array(
					'conditions' => array('Contact.id' => $id),
					'contain' => array(
						'ContactsEmail',
						'ContactsPhone',
						'ContactsAddress', 
						'Company'
					)
				))) {
					return $this->error404();
				}
			} else if (!is_null($id)) {
				return $this->error404();
			} else {
				if (
					isset($this->request->params['named']['kind']) && 
					in_array(strtoupper($this->request->params['named']['kind']), array('C', 'T'))
				) {
					$this->request->data['Contact']['kind'] = strtoupper($this->request->params['named']['kind']);
				}
			}
		}
		
		$this->setupRedirect();
		
		$this->set('contact', $this->request->data);
	}

/**
 * admin_view method
 *
 * @param int $id
 * @return void
 */
	public function admin_view($id = null) {
		if (!empty($id) && $data = $this->Contact->find('first', array(
			'conditions' => array('Contact.id' => $id),
			'contain'    => array('ContactsEmail', 'ContactsPhone', 'ContactsAddress', 'Company')
		))) {
			$this->set('data', $data);
		} else {
			// because of deletion from view action
			$this->redirect(array(
				'action' => 'index',
				'kind'   => @$this->request->params['named']['kind']
			));
		}
	}
	
/**
 * admin_delete method
 *
 * @param int $id
 * @return void
 */
	public function admin_delete($id = null) {
		if (!empty($id) && $this->Contact->delete($id)) {
			$this->setFlash(__d('lil_crm', 'Contact has been successfully deleted.'));
			$this->redirect($this->referer());
		} else {
			$this->error404();
		}
	}
	
/**
 * admin_autocomplete method
 *
 * @return void
 */
	public function admin_autocomplete() {
		if ($this->request->is('ajax')) {
			$params =  array(
				'conditions' => array(
					'Contact.title LIKE' => '%' . $this->request->query['term'] . '%'
				),
			);
			if (!empty($this->request->params['named']['kind'])) {
				$params['conditions']['Contact.kind'] = $this->request->params['named']['kind'];
			}
			$result = $this->Contact->find('list', $params);
			
			$data = array();
			foreach ($result as $k => $c) {
				$data[] = array('id' => $k, 'label' => $c, 'value' => $c);
			}
			$this->set(compact('data'));
		} else $this->error404();
	}
}