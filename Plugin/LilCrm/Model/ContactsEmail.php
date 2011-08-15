<?php
class ContactsEmail extends AppModel {

	var $name = 'ContactsEmail';
	
	var $validate = array(
		'email'      => array('email'),
		'kind'       => array('alphaNumeric')
	);

	var $belongsTo = array('Contact' => array('className' => 'LilCrm.Contact'));

	function beforeSave() {
		if (!empty($this->data['ContactsEmail']['primary'])) {
			$this->updateAll(array('primary' => 0), array(
				'ContactsEmail.contact_id' => $this->data['ContactsEmail']['contact_id']
			));
		}
		return true;
	}
}