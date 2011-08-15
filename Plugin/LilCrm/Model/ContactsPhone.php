<?php
class ContactsPhone extends AppModel {

	var $name = 'ContactsPhone';
	
	var $validate = array(
		'kind'	=> array('alphaNumeric'),
		'no'	=> array('rule' => array('minLength', 1))
	);

	var $belongsTo = array('Contact' => array('className' => 'LilCrm.Contact'));

}