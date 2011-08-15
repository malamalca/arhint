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
 * TmtrUid model
 *
 */
class TmtrUid extends LilAppModel {
/**
 * name
 *
 * @access public
 */
	public $name = 'TmtrUid';
/**
 * toUser
 *
 * @access public
 */
	public function toUser($uid) {
		return $this->field('user_id', array('uid' => $uid));
	}
}