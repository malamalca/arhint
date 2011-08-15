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
 * TmtrEvents controller
 *
 */
class TmtrEventsController extends LilAppController {
/**
 * Controller name
 *
 * @var string
 */
	public $name = 'TmtrEvents';
	
	public $uses = array('LilTimeTrack.TmtrWorkday', 'LilTimeTrack.TmtrEvent', 'LilTimeTrack.TmtrUid');
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_register() {
		$this->layout = 'registration';
		if (empty($this->request->data['uid']) || !($user_id = $this->TmtrUid->toUser($this->request->data['uid']))) {
			if  (!empty($this->request->data['uid'])) {
				$this->setFlash(__d('lil_time_track', 'User does not exist!'), 'error');
				$this->redirect(array('action' => 'register'));
			}
			$this->view = 'admin_register_login';
		} else {
			if ($workday_id = $this->TmtrWorkday->started($user_id)) {
				// check if event in progress
				if ($event = $this->TmtrEvent->inProgress($workday_id)) {
					// if event in progress just stop it
					$this->TmtrEvent->stop($event);
					$this->flash(
						__d('lil_time_track', 'Event successfully stopped.'),
						array('controller' => 'tmtr_events', 'action' => 'register'),
						2,
						 'registration_message'
					);
				} else {
					// else offer events to start
					$this->view = 'admin_register_event';
					$uid = $this->request->data['uid'];
					$this->set(compact('uid', 'workday_id'));
				}
			} else {
				// start workday without any prompts
				if ($this->TmtrWorkday->start($user_id)) {
					$msg = __d('lil_time_track', 'Workday successfully started.');
				} else {
					$msg = __d('lil_time_track', 'Error starting workday');
				}
				$this->flash(
					$msg,
					array('controller' => 'tmtr_events', 'action' => 'register'),
					2,
					 'registration_message'
				);
			}
		}
	}
/**
 * admin_start method
 *
 * @return void
 */
	function admin_start($workday_id) {
		if (!$wd = $this->TmtrWorkday->find('first', array(
			'conditions' => array('TmtrWorkday.id' => $workday_id),
			'contain' => array('TmtrEvent')
		))) return $this->error404();
		
		if (!empty($this->request->data)) {
			// calculate started time
			$started = $wd['TmtrWorkday']['started'];
			if (!empty($wd['TmtrEvent'])) {
				$started = $this->LilDate->toSql(
					strtotime($wd['TmtrEvent'][sizeof($wd['TmtrEvent'])-1]['started']) + 
					$wd['TmtrEvent'][sizeof($wd['TmtrEvent'])-1]['duration']
				);
			}
			
			if ($this->request->data['TmtrEvent']['kind'] == 'end') {
				// SAVE WORKDAY END
				$this->TmtrWorkday->id = $workday_id;
				if ($this->TmtrWorkday->saveField('duration', strtotime($started) - strtotime($wd['TmtrWorkday']['started']))) {
					$this->setFlash(__d('lil_time_track', 'Workday has ended.'));
					$this->redirect(array(
						'controller' => 'tmtr_workdays',
						'admin' => true,
						'action' => 'view',
						$wd['TmtrWorkday']['id']
					));
				}
				
			} else {
				if ($this->request->data['TmtrEvent']['start_type'] == 'auto') {
					$this->request->data['TmtrEvent']['started'] = $started;
				}
				
				// save event
				if ($this->TmtrEvent->saveAll($this->request->data)) {
					$this->setFlash(__d('lil_time_track', 'Event has been started.'));
					$this->redirect(array(
						'controller' => 'tmtr_workdays',
						'admin' => true,
						'action' => 'view',
						$wd['TmtrWorkday']['id']
					));
				}
			}

			$this->setFlash(__d('lil_time_track', 'There are some errors in the form. Please correct all marked fields below.'));
		} else {
			$this->request->data['TmtrEvent']['workday_id'] = $wd['TmtrWorkday']['id'];
			$this->request->data['TmtrEvent']['started']    = strftime('%Y-%m-%d %H:%M');
		}
		
		$users = $this->TmtrWorkday->User->find('list');
		$this->set(compact('users'));
	}
/**
 * admin_end method
 *
 * @return void
 */
	function admin_end($event_id) {
		if (!empty($this->request->data)) {
			if ($this->request->data['TmtrEvent']['end_type'] == 'auto') {
				$this->request->data['TmtrEvent']['duration'] = time() - strtotime($this->request->data['TmtrEvent']['started']);
			} else if ($this->request->data['TmtrEvent']['end_type'] == 'time') {
				$ended = substr($this->request->data['TmtrEvent']['started'], 0, 11) . 
					$this->request->data['TmtrEvent']['end_time']['hour'] . ':' .
					$this->request->data['TmtrEvent']['end_time']['min'] . ':00';
				$this->request->data['TmtrEvent']['duration'] = strtotime($ended) - strtotime($this->request->data['TmtrEvent']['started']);
			}
			if ($this->TmtrEvent->saveAll($this->request->data)) {
				$this->setFlash(__d('lil_time_track', 'Event has been started.'));
				
				$this->redirect(array('controller' => 'tmtr_workdays', 'admin' => true, 'action' => 'view', $this->request->data['TmtrEvent']['workday_id']));
			}
			$this->setFlash(__d('lil_time_track', 'There are some errors in the form. Please correct all marked fields below.'));
		} else if ($this->request->data = $this->TmtrEvent->find('first', array(
			'conditions' => array('TmtrEvent.id' => $event_id),
			'contain' => array('TmtrWorkday')
		))) {
			$this->request->data['TmtrEvent']['duration'] = time() - strtotime($this->request->data['TmtrEvent']['started']);
		} else return $this->error404();
	}
/**
 * admin_register_start method
 *
 * @return void
 */
	function admin_register_start() {
		$kind = 'private';
		if (empty($this->params->named['workday'])) return $this->error404();
		if (!empty($this->params->named['kind'])) $kind = $this->params->named['kind'];
		
		if ($this->TmtrEvent->start($this->params->named['workday'], $kind)) {
			$msg = __d('lil_time_track', 'Event started.');
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
}