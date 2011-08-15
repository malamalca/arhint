<?php
/**
 * Arhim: The Architectural practice
 *
 * This controller will manage tasks.
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppController', 'Lil.Controller');
/**
 * Tasks controller
 *
 */
class TasksController extends LilAppController {
/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Tasks';
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$filter = array();
		if (!empty($this->request->query['filter'])) $filter = $this->request->query['filter'];
		
		if (!isset($filter['date']) && !$filter['date']=$this->Cookie->read('lil_tasks_index')) {
			$filter['date'] = strftime('%Y-%m-%d');
		}
		$date = $filter['date'];
		$this->request->query['filter']['date'] = $date;
		
		$date_next = strftime('%Y-%m-%d', strtotime($date) + 24*60*60);
		$date_prev = strftime('%Y-%m-%d', strtotime($date) - 24*60*60);
		
		$params = array_merge(
			array('order' => 'Task.completed, Task.deadline DESC, Task.modified DESC', 'recursive' => -1),
			$this->Task->filter($filter)
		);
		$tasks = $this->Task->find('all', $params);
		
		$this->set(compact('tasks', 'filter', 'date', 'date_next', 'date_prev'));
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
			if ($this->Task->saveAll($this->request->data)) {
				$this->setFlash(__d('lil_tasks', 'Task has been saved.'));
				
				// send email for new tasks
				/*if (empty($this->request->data['Task']['id']) && ($to = $this->currentUser->email())) {

					$cal_msg = sprintf(
						'BEGIN:VCALENDAR'.PHP_EOL.
							'PRODID:-//Microsoft Corporation//Outlook 11.0 MIMEDIR//EN'.PHP_EOL.
							'VERSION:2.0'.PHP_EOL.
							'METHOD:REQUEST'.PHP_EOL.
							'BEGIN:VEVENT'.PHP_EOL.
								'ORGANIZER:MAILTO:%1$s'.PHP_EOL.
								'DTSTART:20080714T170000Z'.PHP_EOL.
								'DTEND:20080715T035959Z'.PHP_EOL.
								'UID:%2$s' .PHP_EOL.
								//'LOCATION:my meeting location'.PHP_EOL.
								'TRANSP:OPAQUE'.PHP_EOL.
								'SEQUENCE:0'.PHP_EOL.
								'DTSTAMP:20060309T045649Z'.PHP_EOL.
								'CATEGORIES:%3$s'.PHP_EOL.
								'DESCRIPTION:%5$s'.PHP_EOL.
								'SUMMARY:%4$s'.PHP_EOL.
								'PRIORITY:5'.PHP_EOL.
								'X-MICROSOFT-CDO-IMPORTANCE:1'.PHP_EOL.
								'CLASS:PUBLIC'.PHP_EOL.
									'BEGIN:VALARM'.PHP_EOL.
										'TRIGGER:-PT3D'.PHP_EOL.
										'ACTION:DISPLAY'.PHP_EOL.
										'DESCRIPTION:Reminder'.PHP_EOL.
									'END:VALARM'.PHP_EOL.
									'BEGIN:VTIMEZONE'.PHP_EOL.
										'TZID:US/Central'.PHP_EOL.
									'END:VTIMEZONE'.PHP_EOL.
							'END:VEVENT'.PHP_EOL.
						'END:VCALENDAR',
						Configure::read('LilTasks.from.email'), //%1$s
						String::uuid(), //%2$s
						__d('lil_tasks', 'Task'), //3$s
						$this->request->data['Task']['title'], //%4$s
						strtr(
							strtr(
								strtr($this->request->data['Task']['descript'], "\r", "\n"),
								"\nn", "\n"
							),
							"\n", '\n'
						) . '\n' //%5$s
					);
					
					$tmp_file = TMP . 'test.ics';
					$file_att_type = "text/calendar; method=REQUEST";
					file_put_contents($tmp_file,$cal_msg);

					App::uses('CakeEmail', 'Network/Email');
					$email = new CakeEmail();
					$email->template('LilTasks.notification_new');
					$email->format = 'both';
					$email->from(Configure::read('LilTasks.from.email'), Configure::read('LilTasks.from.name'));
					$email->subject(__d('lil_tasks', 'New Task'));
					$email->to($to);
					$email->addAttachments($tmp_file);
					$email->viewVars(array('data' => $this->request->data));
					$email->send();
				}*/
				
				
				return $this->doRedirect();
			}
			$this->setFlash(__d('lil_tasks', 'There are some errors in the form. Please correct all marked fields below.'));
		} else if (!empty($id)) {
			if (!$this->request->data = $this->Task->find('first', 
				array('conditions' => array('Task.id' => $id), 'recursive' => -1)
			)) {
				$this->error404();
			}
		} else {
			$this->request->data['Task']['deadline'] = strftime('%Y-%m-%d');
		}
		$this->setupRedirect();
		
		$users = $this->Task->User->find('list');
		$projects = $this->Task->Project->findForUser(null, 'list');
		
		$this->set(compact('projects', 'users'));
	}
/**
 * admin_toggle method
 *
 * @param mixed $id
 * @return void
 */
	function admin_toggle() {
		if ($dateString = $this->Task->field('completed', array('Task.id' => $this->request->params['named']['id']))) {
			$dateString = null;
		} else {
			$dateString = strftime('%Y-%m-%d');
		}
		
		$this->Task->id = $this->request->params['named']['id'];
		$this->Task->saveField('completed', $dateString);
		$this->view = 'admin_index';
		$this->setAction('admin_index');
	}
/**
 * admin_redate method
 *
 * @param mixed $id
 * @return void
 */
	function admin_redate() {
		$this->Task->id = $this->request->params['named']['id'];
		$this->Task->saveField('deadline', $this->request->params['named']['date']);
		$this->Task->saveField('completed', null);
		$this->view = 'admin_index';
		$this->setAction('admin_index');
	}
/**
 * admin_delete method
 *
 * @param mixed $id
 * @return void
 */
	function admin_delete($id = null) {
		$conditions = array('Task.id' => $id);
		if (!empty($id) && $this->Task->hasAny($conditions) && $this->Task->delete($id)) {
			$this->setFlash(__d('lil_tasks', 'Task has been deleted.'));
			$this->redirect($this->referer());
		} else {
			$this->error404();
		}
	}
}