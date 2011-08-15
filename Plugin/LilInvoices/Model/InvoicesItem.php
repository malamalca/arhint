<?php
class InvoicesItem extends AppModel {

	var $name = 'InvoicesItem';

	var $belongsTo = array('Invoice');
	var $actsAs = array('Lil.LilFloat');
	
	var $validate = array(
		'descript' => array('rule' => array('minLength', 1), 'allowEmpty' => false),
		'unit'     => array('rule' => array('minLength', 1), 'allowEmpty' => false),
		'amount' => array(
			'format' => array('rule' => 'isValidFloat', 'allowEmpty' => false),
		),
		'price' => array(
			'format' => array('rule' => 'isValidFloat', 'allowEmpty' => false),
		),
	);
}