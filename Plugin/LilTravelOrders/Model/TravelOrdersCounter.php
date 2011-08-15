<?php
App::uses('LilAppModel', 'Lil.Model');
class TravelOrdersCounter extends LilAppModel {

	var $name = 'TravelOrdersCounter';
	var $displayField = 'title';
	
	var $validate = array(
	);
	
	var $actsAs = array(
		'Containable',
		'Lil.LilUpload' => array(
			'fields' => array(
				'header' => array(
					'allowedMime'       => '*',
					'allowedExt'        => array('jpg','jpeg','gif','png'),
					'dirFormat'         => '',
					'fileFormat'        => '{HASH}.{$extension}',
					'fileField'         => 'header',
					
					'overwriteExisting' => true,
					'mustUploadFile'    => false,
				),
				'footer' => array(
					'allowedMime'       => '*',
					'allowedExt'        => array('jpg','jpeg','gif','png'),
					'dirFormat'         => '',
					'fileFormat'        => '{HASH}.{$extension}',
					'fileField'         => 'footer',
					
					'overwriteExisting' => true,
					'mustUploadFile'    => false,
				),
			)
		)
	);
	
	function generateNo($id, $updateCounter = false) {
		$ret = false;
		
		if (is_string($id)) {
			$data = $this->find('first', array(
				'fields'     => array('id', 'counter', 'mask'),
				'conditions' => array('Counter.id' => $id),
				'recursive'  => -1
			));
		} else if (is_array($id)) {
			$data = $id;
		}
		
		if (isset($data['TravelOrdersCounter']['mask']) && isset($data['TravelOrdersCounter']['counter'])) {
			$ret = strtr(
				$data['TravelOrdersCounter']['mask'],
				array(
					'[[year]]' => strftime('%Y'),
					'[[month]]' => strftime('%m'),
					'[[no]]'   => (int)$data['TravelOrdersCounter']['counter'] + 1,
					'[[no.2]]'   => str_pad((int)$data['TravelOrdersCounter']['counter'] + 1, 2, '0', STR_PAD_LEFT),
					'[[no.3]]'   => str_pad((int)$data['TravelOrdersCounter']['counter'] + 1, 3, '0', STR_PAD_LEFT)
				)
			);

			if ($updateCounter && !empty($data['TravelOrdersCounter']['id'])) {
				$this->updateAll(
					array('TravelOrdersCounter.counter' => (int)$data['TravelOrdersCounter']['counter'] + 1),
					array('TravelOrdersCounter.id'      => $data['TravelOrdersCounter']['id'])
				); 
			}
		}
		return $ret;
	}
	
	function getDescript($kind) {
		return $this->field('template_descript', array('TravelOrdersCounter.kind' => $kind));
	}
	
	function received($kind = 'list') {
		return $this->find($kind, array('conditions' => array('TravelOrdersCounter.kind' => 'received')));
	}
	
	function issued($kind = 'list') {
		return $this->find($kind, array('conditions' => array('TravelOrdersCounter.kind' => 'issued')));
	}
}