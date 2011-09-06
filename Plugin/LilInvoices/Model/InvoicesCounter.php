<?php
App::uses('LilAppModel', 'Lil.Model');
class InvoicesCounter extends LilAppModel {

	var $name = 'InvoicesCounter';
	
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
				'conditions' => array('InvoicesCounter.id' => $id),
				'recursive'  => -1
			));
		} else if (is_array($id)) {
			$data = $id;
		}
		
		if (isset($data['InvoicesCounter']['mask']) && isset($data['InvoicesCounter']['counter'])) {
			$ret = strtr(
				$data['InvoicesCounter']['mask'],
				array(
					'[[year]]' => strftime('%Y'),
					'[[month]]' => strftime('%m'),
					'[[no]]'   => (int)$data['InvoicesCounter']['counter'] + 1,
					'[[no.2]]'   => str_pad((int)$data['InvoicesCounter']['counter'] + 1, 2, '0', STR_PAD_LEFT),
					'[[no.3]]'   => str_pad((int)$data['InvoicesCounter']['counter'] + 1, 3, '0', STR_PAD_LEFT)
				)
			);

			if ($updateCounter && !empty($data['InvoicesCounter']['id'])) {
				$this->updateAll(
					array('InvoicesCounter.counter' => (int)$data['InvoicesCounter']['counter'] + 1),
					array('InvoicesCounter.id'      => $data['InvoicesCounter']['id'])
				); 
			}
		}
		return $ret;
	}
	
	function getDescript($kind) {
		return $this->field('template_descript', array('InvoicesCounter.kind' => $kind));
	}
	
	function received($kind = 'list') {
		return $this->find($kind, array('conditions' => array('InvoicesCounter.kind' => 'received')));
	}
	
	function issued($kind = 'list') {
		return $this->find($kind, array('conditions' => array('InvoicesCounter.kind' => 'issued')));
	}
}