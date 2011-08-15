<?php
	$title = $this->Html->clean($contact['Contact']['title']);
	
	$ret = '';
	if ( !empty($contact['Contact']['job'])) {
		$ret .= ', '.$this->Html->clean($contact['Contact']['job']);
	} else {
		if (!empty($contact['Company']['title'])) {
			$ret .= ', ' . __d('lil_crm', 'employed', true);
		}
	}
	if (!empty($contact['Company']['title'])) {
		$ret .= 
			' ' . __d('lil_crm', 'at', true) . ' ' .
			$this->Html->link($contact['Company']['title'], array(
				'controller'=>'contacts', 
				'action'=>'view', 
				$contact['Company']['id']
			));
	}
	if (!empty($ret)) $title .= sprintf('<span class="light">%s</span>', $ret);
	$this->set('title_for_layout', $title);