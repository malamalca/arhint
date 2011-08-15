<?php
App::uses('LilAppModel', 'Lil.Model');
class Counter extends LilAppModel {

	var $name = 'Counter';
	
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
		
		if (isset($data['Counter']['mask']) && isset($data['Counter']['counter'])) {
			$ret = strtr(
				$data['Counter']['mask'],
				array(
					'[[year]]' => strftime('%Y'),
					'[[month]]' => strftime('%m'),
					'[[no]]'   => (int)$data['Counter']['counter'] + 1,
					'[[no.2]]'   => str_pad((int)$data['Counter']['counter'] + 1, 2, '0', STR_PAD_LEFT),
					'[[no.3]]'   => str_pad((int)$data['Counter']['counter'] + 1, 3, '0', STR_PAD_LEFT)
				)
			);

			if ($updateCounter && !empty($data['Counter']['id'])) {
				$this->updateAll(
					array('Counter.counter' => (int)$data['Counter']['counter'] + 1),
					array('Counter.id'      => $data['Counter']['id'])
				); 
			}
		}
		return $ret;
	}
	
	function getDescript($kind) {
		return $this->field('template_descript', array('Counter.kind' => $kind));
	}
	
	function received($kind = 'list') {
		return $this->find($kind, array('conditions' => array('Counter.kind' => 'received')));
	}
	
	function issued($kind = 'list') {
		return $this->find($kind, array('conditions' => array('Counter.kind' => 'issued')));
	}
}