<?php
	$this->set('title_for_layout', __d('lil_crm', 'Phone No. for', true) . ': ' . @$contact_title);
?>
<div class="form">
	<?php
		echo $this->LilForm->create('ContactsPhone');
		echo $this->LilForm->input('id');
		echo $this->LilForm->input('contact_id', array('type' => 'hidden'));
		echo $this->LilForm->input('referer', array('type' => 'hidden'));
		echo $this->LilForm->input('kind', array(
			'type'    => 'select', 
			'label'   => __d('lil_crm', 'Kind') . ':',
			'options' => $GLOBALS['phone_types']));
		echo $this->LilForm->input('no', array('label' => __d('lil_crm', 'Number') . ':'));
		echo $this->LilForm->submit(__d('lil_crm', 'OK'));
		echo $this->LilForm->end();
	?>
</div>