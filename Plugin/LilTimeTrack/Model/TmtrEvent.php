<?php
/**
 * Arhim: The Architectural practice
 *
 * This model if for storing TmtrEvent
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppModel', 'Lil.Model');
/**
 * TmtrWorkday model
 *
 */
class TmtrEvent extends LilAppModel {
/**
 * name
 *
 * @access public
 */
	public $name = 'TmtrEvent';
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
 * order property
 *
 * @var string
 * @access public
 */
	public $order = array(
		'TmtrEvent.started'
	);
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'User' => array(
			'className' => 'Lil.User',
			'foreignKey' => 'user_id'
		),
		'TmtrWorkday' => array(
			'className' => 'LilTimeTrack.TmtrWorkday',
			'foreignKey' => 'workday_id'
		)
	);
/**
 * filter
 *
 * @access public
 */
	public function filter(&$filter) {
		$ret = array();
		
		if (isset($filter['date'])) {
			$ret['conditions']['TmtrEvent.started'] = $filter['date'];
		}
		
		return $ret;
	}
/**
 * inProgress
 *
 * @access public
 */
	public function inProgress($workday_id) {
		return $this->find('first', array(
			'conditions' => array(
				'workday_id' => $workday_id,
				'duration' => null,
			),
			'recursive' => -1
		));
	}
/**
 * start
 *
 * Creates timetrack event for user
 *
 * @access public
 */
	public function start($workday_id, $kind, $project_id = null, $user_id = null) {
		if (!in_array($kind, array('lunch', 'private', 'work'))) return false;
		
		if ($event = $this->inProgress($workday_id)) $this->stop($event);
		
		$data = array(
			'TmtrEvent' => array(
				'workday_id' => $workday_id,
				'kind'       => $kind,
				'project_id' => $project_id,
				'user_id'    => $user_id ? $user_id : $this->currentUser->get('id'),
				'started'    => $this->LilDate->toSql(floor(time()/60)*60)
			)
		);
		$this->create($data);
		return $this->save();
	}
/**
 * stop
 *
 * @access public
 */
	public function stop($event) {
		$this->id = $event;
		if (isset($event['TmtrEvent']['id'])) $this->id = $event['TmtrEvent']['id'];
		
		if (isset($event['TmtrEvent']['started'])) {
			$started = $event['TmtrEvent']['started'];
		} else {
			$started = $this->field('started', array('TmtrEvent.id' => $this->id));
		}
		
		if (is_numeric($this->id) && !empty($started)) {
			return $this->saveField('duration', floor(time()/60)*60 - strtotime($started));
		}
		return false;
	}
}