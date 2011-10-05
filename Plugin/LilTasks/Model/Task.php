<?php
/**
 * Arhim: The Architectural practice
 *
 * This model if for storing Tasks
 *
 * @copyright     Copyright 2010, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/arhim.php
 */
App::uses('LilAppModel', 'Lil.Model');
/**
 * Tasks model
 *
 */
class Task extends LilAppModel {
/**
 * name
 *
 * @access public
 */
	public $name = 'Task';
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array(
		'Lil.LilDate'
	);
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'Project' => array(
			'className' => 'Lil.Area',
			'foreignKey' => 'project_id'
		),
		'User' => array(
			'className' => 'Lil.User',
			'foreignKey' => 'user_id'
		)
	);
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'Attachment' => array(
			'className'  => 'Attachment',
			'conditions' => array('Attachment.model' => 'Task'),
			'foreignKey' => 'foreign_id',
			'order'      => 'Attachment.created',
			'dependent'  => true
		),
	);
/**
 * filter
 *
 * @access public
 */
	public function filter(&$filter) {
		$ret = array();
		
		if (isset($filter['date'])) {
			if ($filter['date'] == '7d') {
				$ret['conditions']['OR'] = array(
					array(
						'Task.completed >=' => strftime('%Y-%m-%d', time()),
						'Task.completed <=' => strftime('%Y-%m-%d', time()+7*24*60*60),
					),
					array(
						'Task.completed' => null,
						'Task.deadline <=' => strftime('%Y-%m-%d', time()+7*24*60*60)
					),
					array(
						'Task.deadline' => null
					)
				);
			} else if ($filter['date'] == '30d') {
				$ret['conditions']['OR'] = array(
					array(
						'Task.completed >=' => strftime('%Y-%m-%d', time()),
						'Task.completed <=' => strftime('%Y-%m-%d', time()+30*24*60*60),
					),
					array(
						'Task.completed' => null,
						'Task.deadline <=' => strftime('%Y-%m-%d', time()+30*24*60*60)
					),
					array(
						'Task.deadline' => null
					)
				);
			} else {
				$ret['conditions']['OR'] = array(
					array(
						'Task.completed' => $filter['date'],
					),
					array(
						'Task.completed' => null,
						'Task.deadline <=' => $filter['date']
					),
					array(
						'Task.deadline' => null
					)
				);
			}
		}
		
		if (isset($filter['completed'])) {
			if ($filter['completed'] == 1) {
				$ret['conditions']['NOT']['Task.completed'] = null;
			} else {
				$filter['completed'] = 0;
				$ret['conditions']['Task.completed'] = null;
			}
		}
		
		if (isset($filter['Project'])) {
			$ret['conditions']['Task.project_id'] = (array)$filter['Project'];
		}
		
		// filter by user
		if ($this->currentUser->role('admin')) {
			if (isset($filter['User'])) {
				$ret['conditions']['Task.user_id'] = (array)$filter['User'];
			}
		} else {
			$filter['User']  = $ret['conditions']['Task.user_id'] = $this->currentUser->get('id');
		}
		
		return $ret;
	}
}