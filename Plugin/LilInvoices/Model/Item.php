<?php
App::import('Model', 'Lil.LilAppModel');
class Item extends LilAppModel {

	var $name = 'Item';
	
	var $displayField = 'descript';
	
	var $validate = array(
	);

	var $actsAs = array(
		'Lil.LilFloat', 'Containable',
	);
}