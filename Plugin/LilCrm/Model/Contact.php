<?php
class Contact extends AppModel {
	var $name = 'Contact';
	
	var $displayField = 'title';
	
	var $validate = array(
		'title'       => array('rule' => array('minLength', 1))
	);
	
	var $belongsTo = array(
		'Company' => array(
			'className' => 'Contact',
			'foreignKey' => 'company_id',
		)
	);

	var $hasMany = array(
		'ContactsEmail' => array(
			'dependent'=> true,
			'order' => 'ContactsEmail.primary DESC, ContactsEmail.email'
		), 
		'ContactsPhone' => array(
			'dependent'=> true
		), 
		'ContactsAddress' => array(
			'dependent'=> true,
			'order' => 'ContactsAddress.primary DESC, ContactsAddress.street'
		)
	);
	
	var $hasOne = array(
		'PrimaryAddress' => array(
			'className' => 'ContactsAddress',
			'foreignKey' => 'contact_id',
			'conditions'   => array('PrimaryAddress.primary' => true),
		),
		'PrimaryEmail' => array(
			'className' => 'ContactsEmail',
			'foreignKey' => 'contact_id',
			'conditions'   => array('PrimaryEmail.primary' => true),
		),
	);
	
	var $actsAs = array(
		'Containable',
	);
	
	function beforeSave() {
		// create contact's "display title" from name and surname
		if (strtoupper($this->data['Contact']['kind']) == 'T' && 
			isset($this->data['Contact']['name']) && 
			isset($this->data['Contact']['surname'])) 
		{
			$this->data['Contact']['title'] = implode(' ', Set::filter(
				array($this->data['Contact']['name'], $this->data['Contact']['surname'])
			));
		}
		return parent::beforeSave();
	}
	
	function filter(&$filter) {
		$ret = array();
		if (empty($filter['kind'])) $filter['kind'] = 'C';
		
		$ret['conditions']['Contact.kind'] = $filter['kind'];
		if (!empty($filter['search'])) {
			$ret['conditions']['OR'] = array(
				'Contact.title LIKE'	=> '%'.$filter['search'].'%',
				'Contact.name LIKE'	=> '%'.$filter['search'].'%',
				"CONCAT(Contact.title, ' ', Contact.name)" => $filter['search'],
				"CONCAT(Contact.name, ' ', Contact.title)" => $filter['search'],
			);
		}
		return $ret;
	}
	
	function getTitle($id) {
		$this->recursive = -1;
		return $this->field('title', array('Contact.id'=>$id));
	}

	function getDefaultEmail($id, $nice=false) {
		if ($nice) {
			$this->Email->contain('Contact');
			$fields = array('ContactsEmail.email', 'Contact.title');
		} else {
			$this->Email->contain();
			$fields = array('ContactsEmail.email');
		}
		
		if (is_numeric($id) && $data = $this->Email->find('first', array(
			'conditions'=>array('ContactsEmail.contact_id'=>$id), 
			'fields'=>$fields, 
			'order'=>'ContactsEmail.primary DESC, ContactsEmail.created'))
		) {
			if ($nice) {
				$title = $data['Contact']['title'];
				if (!empty($title)) {
					return $title.' <'.$data['ContactsEmail']['email'].'>';
				} else return $data['ContactsEmail']['email'];
			} else return $data['ContactsEmail']['email'];
		}
		
		return false;
	}

}