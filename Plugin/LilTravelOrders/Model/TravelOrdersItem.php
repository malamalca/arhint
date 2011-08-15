<?php
class TravelOrdersItem extends AppModel {

	var $name = 'TravelOrdersItem';

	var $belongsTo = array(
		'TravelOrder' => array(
			'foreignKey' => 'order_id',
		)
	);
	var $actsAs = array('Lil.LilFloat');
}
?>