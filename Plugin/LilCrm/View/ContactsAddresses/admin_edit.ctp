<?php
	$this->set('title_for_layout', __d('lil_crm', 'Address', true) . ': ' . @$contact_title);
?>
<div class="form">
	<?php
		echo $this->LilForm->create('ContactsAddress');
		echo $this->LilForm->input('id');
		echo $this->LilForm->input('contact_id', array('type' => 'hidden'));
		echo $this->LilForm->input('referer', array('type' => 'hidden'));
		
		echo $this->LilForm->input('kind', array('
			type'     => 'select',
			'label'   => __d('lil_crm', 'Kind', true) . ':',
			'options' => $GLOBALS['address_types']));
		echo $this->LilForm->input('street', array(
			'label'=>__d('lil_crm', 'Street', true) . ':'
		));
		
		echo $this->LilForm->input('zip', array('label' => __d('lil_crm', 'ZIP', true) . ':'));
		echo $this->LilForm->input('city', array('label' => __d('lil_crm', 'City', true) . ':'));
		
		echo $this->LilForm->input('country', array('label' => __d('lil_crm', 'State', true) . ':'));
		
		echo $this->LilForm->input('primary', array('label'=>__d('lil_crm', 'This is a primary address', true), 'type' => 'checkbox'));
		
		echo $this->LilForm->submit(__d('lil_crm', 'OK', true));
		echo $this->LilForm->end();
	?>
</div>