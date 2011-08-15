<?php
/**
 * Arhim: The Architectural practice
 *
 * This model if for storing TmtrWorkday
 *
 * @copyright     Copyright 2011, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
App::uses('LilAppModel', 'Lil.Model');
/**
 * TmtrWorkday model
 *
 */
class TmtrWorkday extends LilAppModel {
/**
 * name
 *
 * @access public
 */
	public $name = 'TmtrWorkday';
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
		'TmtrEvent' => array(
			'className' => 'LilTimeTrack.TmtrEvent',
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
		
		if (!empty($filter['start']) && !empty($filter['end']) &&
			$this->LilDate->isSql($filter['start']) && $this->LilDate->isSql($filter['end']))
		{
			unset($filter['month']);
			
			if ($filter['start'] > $filter['end']) $filter['end'] = $filter['start'];
			
			// try to determine if given dates are exactly specific months
			list($year1, $month1, $day1) = explode('-', $filter['start']);
			list($year2, $month2, $day2) = explode('-', $filter['end']);
			if (($year1 == $year2) && ($month1 == $month2) && ($day1 == '01') && 
				($day2 == cal_days_in_month(CAL_GREGORIAN, (int)$month2, $year2)))
			{
				$filter['month'] = $year2 . '-' . $month2;
			}
			$ret['conditions']['TmtrWorkday.started BETWEEN ? AND ?'] = array(
				$filter['start'], $this->LilDate->toSql(strtotime($filter['end']) + 24*60*60, false)
			);
		} else {
			if (empty($filter['month']) || !preg_match('/([1-9][0-9]{0,3})-((0[1-9])|(1[0-2]))/', $filter['month'])) {
				$filter['month'] = strftime('%Y-%m');
			}
			
			list($year, $month) = explode('-', $filter['month']);
			$filter['start'] = $filter['month'] . '-01';
			$filter['end'] = $filter['month'] . '-' . cal_days_in_month(CAL_GREGORIAN, (int)$month, $year);
			
			$ret['conditions']['TmtrWorkday.started BETWEEN ? AND ?'] = array(
				$filter['start'], $this->LilDate->toSql(strtotime($filter['end']) + 24*60*60, false)
			);
		}
		
		
		return $ret;
	}
/**
 * started
 *
 * Returns workday id or false if workday for specified user has already been started
 *
 * @access public
 */
	public function started($user_id, $dateString = null) {
		if (empty($dateString)) $dateString = strftime('%Y-%m-%d');
		$from = strftime('%Y-%m-%d', strtotime($dateString));
		$to = strftime('%Y-%m-%d', strtotime($dateString) + 24*60*60);
		return $this->field('id', array(
			'user_id'                 => $user_id,
			'started BETWEEN ? AND ?' => array($from, $to),
			'duration'                => null
		));
	}
/**
 * start
 *
 * Starts workday for user
 *
 * @access public
 */
	public function start($user_id = null) {
		$data = array(
			'TmtrWorkday' => array(
				'user_id' => $user_id ? $user_id : $this->currentUser->get('id'),
				'started' => $this->LilDate->toSql(floor(time()/60)*60)
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
	public function stop($id) {
		$this->id = $id;
		$started = $this->field('started', array('TmtrWorkday.id' => $id));
		
		if (!empty($this->id) && !empty($started)) {
			return $this->saveField('duration', floor(time()/60)*60 - strtotime($started));
		}
		return false;
	}
/**
 * deleteLastRegistration
 *
 * @access public
 */
	public function deleteLastRegistration($id) {
		$params = array('conditions' => array('TmtrWorkday.id' => $id), 'recursive' => -1);
		
		if (!empty($id) && ($wd = $this->find('first', $params))) {
			if (!empty($wd['TmtrWorkday']['duration'])) {
				// finished workday
				$this->id = $id;
				if ($this->saveField('duration', null)) return 'workday_end';
			} else {
				$ev_params = array(
					'conditions' => array('TmtrEvent.workday_id' => $id),
					'recursive' => -1,
					'order' => 'TmtrEvent.started DESC'
				);
				if ($ev = $this->TmtrEvent->find('first', $ev_params)) {
					if (!empty($ev['TmtrEvent']['duration'])) {
						$this->TmtrEvent->id = $ev['TmtrEvent']['id'];
						if ($this->TmtrEvent->saveField('duration', null)) return 'event_end';
					} else {
						if ($this->TmtrEvent->delete($ev['TmtrEvent']['id'])) return 'event_start';
					}
				} else {
					// delete workday
					if ($this->delete($id)) return 'workday_start';
				}
			}
		}
		return false;
	}
}