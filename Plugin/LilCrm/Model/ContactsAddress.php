<?php
class ContactsAddress extends AppModel {

	var $name = 'ContactsAddress';
	
	var $validate = array(
		'kind'       => array('alphaNumeric')
	);

	var $belongsTo = array('Contact' => array('className' => 'LilCrm.Contact'));
	
	function beforeSave() {
		if (!empty($this->data['ContactsAddress']['primary'])) {
			$this->updateAll(array('primary' => 0), array(
				'ContactsAddress.contact_id' => $this->data['ContactsAddress']['contact_id']
			));
		}
		return true;
	}
}