<?php
	$this->set('title_for_layout', __d('lil_crm', 'Email for', true) . ': ' . @$contact_title);
?>
<div class="form">
	<?php
		echo $this->LilForm->create('ContactsEmail');
		echo $this->LilForm->input('id', array('type' => 'hidden'));
		echo $this->LilForm->input('contact_id', array('type' => 'hidden'));
		echo $this->LilForm->input('referer', array('type' => 'hidden'));
		echo $this->LilForm->input('kind', array('type' => 'select', 'label' => __d('lil_crm', 'Kind') . ':', 'options' => $GLOBALS['email_types']));
		echo $this->LilForm->input('email', array('label' => __d('lil_crm', 'Email') . ':', 'class' => 'big'));
		echo $this->LilForm->input('primary', array('label'=>__d('lil_crm', 'This is a primary email address'), 'type' => 'checkbox'));
		echo $this->LilForm->submit(__d('lil_crm', 'OK'));
		echo $this->LilForm->end();
	?>
</div>