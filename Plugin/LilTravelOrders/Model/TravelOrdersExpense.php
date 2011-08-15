<?php
class TravelOrdersExpense extends AppModel {

	var $name = 'TravelOrdersExpense';

	var $belongsTo = array(
		'TravelOrder' => array(
			'foreignKey' => 'order_id',
		)
	);
	var $actsAs = array('Lil.LilFloat');
}
?>