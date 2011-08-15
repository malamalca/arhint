<?php
/**
 * LilIntranet: The
 *
 * This controller will manage tasks.
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * TmtrWorkdays controller
 *
 */
class TmtrWorkdaysController extends LilAppController {
/**
 * Controller name
 *
 * @var string
 */
	public $name = 'TmtrWorkdays';
	
	public $uses = array('LilTimeTrack.TmtrWorkday', 'LilTimeTrack.TmtrEvent');
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$filter = array();
		if (!empty($this->request->query['filter'])) $filter = $this->request->query['filter'];
		
		$months = array();
		for ($i = 1; $i <= 12; $i++) {
			$months[$i] = strftime('%B', mktime(0, 0, 0, $i));
		}
		
		$params = array_merge(
			array(
				'conditions' => array('TmtrWorkday.user_id' => $this->currentUser->get('id')),
				'order' => 'TmtrWorkday.started',
				'contain' => 'TmtrEvent'
			),
			$this->TmtrWorkday->filter($filter)
		);
		$data = $this->TmtrWorkday->find('all', $params);
		
		$this->set(compact('data', 'filter', 'months'));
	}
/**
 * admin_view method
 *
 * @return void
 */
	public function admin_view($id = null) {
		if (empty($id) || !$data = $this->TmtrWorkday->find('first', array(
			'conditions' => array('TmtrWorkday.id' => $id),
			'contain' => array('TmtrEvent')
		))) $this->redirect(array('action' => 'index'));
		
		$this->set(compact('data'));
	}
/**
 * admin_select method
 *
 * @return void
 */
	public function admin_select() {
		$filter = array();
		if (!empty($this->request->query['filter'])) $filter = $this->request->query['filter'];
		
		if (!isset($filter['date']) && !$filter['date'] = $this->Cookie->read('lil_tmtr_view')) {
			$filter['date'] = strftime('%Y-%m-%d');
		}
		$date = $filter['date'];
		$this->request->query['filter']['date'] = $date;
		
		$date_next = strftime('%Y-%m-%d', strtotime($date) + 24*60*60);
		$date_prev = strftime('%Y-%m-%d', strtotime($date) - 24*60*60);
		
		$data = $this->TmtrWorkday->find('all', array(
			'conditions' => array(
				'TmtrWorkday.started BETWEEN ? AND ?' => array($filter['date'], $date_next),
				'TmtrWorkday.user_id' => $this->currentUser->get('id')
			),
			'order' => 'TmtrWorkday.started',
			'contain' => array('TmtrEvent')
		));
		
		if (sizeof($data) == 1) {
			$this->redirect(array('admin' => true, 'action' => 'view', $data[0]['TmtrWorkday']['id']));
		}
		
		$this->set(compact('data', 'filter', 'date', 'date_next', 'date_prev'));
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
 * @param mixed $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (!empty($this->request->data)) {
			if ($this->TmtrWorkday->saveAll($this->request->data)) {
				$this->setFlash(__d('lil_time_track', 'Workday has been saved.'));
				return $this->doRedirect();
			}
			$this->setFlash(__d('lil_time_track', 'There are some errors in the form. Please correct all marked fields below.'));
		} else if (!empty($id)) {
			if ($this->request->data = $this->TmtrWorkday->find('first', 
				array('conditions' => array('TmtrWorkday.id' => $id), 'recursive' => -1)
			)) {
				// mingle with data
			} else $this->error404();
		} else {
			$this->request->data['TmtrWorkday']['started'] = strftime('%Y-%m-%d %H:%M');
		}
		$this->setupRedirect();
		
		$users = $this->TmtrWorkday->User->find('list');
		$this->set(compact('users'));
	}
/**
 * admin_stop method
 *
 * @param mixed $id
 * @return void
 */
	function admin_stop($id = null) {
		if ($this->TmtrWorkday->stop($id)) {
			$msg = __d('lil_time_track', 'Workday stopped.');
		} else {
			$msg = __d('lil_time_track', 'Sth bad happened');
		}
		$this->flash(
			$msg,
			array('controller' => 'tmtr_events', 'action' => 'register'),
			2,
			'registration_message'
 		);
	}
/**
 * admin_delete method
 *
 * @param mixed $id
 * @return void
 */
	function admin_delete($id = null) {
		$conditions = array('TmtrWorkday.id' => $id);
		if (!empty($id) && ($started = $this->TmtrWorkday->field('started', $conditions)) && $this->TmtrWorkday->delete($id)) {
			$this->setFlash(__d('lil_time_track', 'Workday has been deleted.'));
			$this->redirect(array(
				'action' => 'select',
				'?' => array('filter' => array('date' => $this->LilDate->toSql($started, false)))
			));
		} else {
			$this->error404();
		}
	}
/**
 * admin_delete_last method
 *
 * @param mixed $id
 * @return void
 */
	function admin_delete_last($id = null) {
		if (!empty($id) && ($kind = $this->TmtrWorkday->deleteLastRegistration($id))) {
			$this->setFlash(__d('lil_time_track', 'Last registration has been deleted.'));
			$this->redirect($this->referer());
		} else {
			$this->error404();
		}
	}
}