<?php
/**
 * LilPlan: The Project Management System
 *
 * @copyright     Copyright 2010, MalaMalca (http://malamalca.com)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */
?>
<?php
	if ($this->Form->value('Contact.id')) {
		echo $this->element('head');
	} else {
		if ($this->Form->value('Contact.kind') == 'C') {
			$title_for_layout = __d('lil_crm', 'Add a Company');
		} else {
			$title_for_layout = __d('lil_crm', 'Add a Person');
		}
		$this->set(compact('title_for_layout'));
	} 
?>
<div class="contacts form">
<?php 
		echo $this->Form->create('Contact', array(
			'url'  => array('action' => 'edit'),
			'type' => 'file',
			'id'   => 'FormEditContact'
		));
		
		echo $this->Form->input('id',      array('type' => 'hidden'));
		echo $this->Form->input('kind',    array('type' => 'hidden', 'default' => 'T'));
		echo $this->Form->input('referer', array('type' => 'hidden'));
		
		if ($this->Form->value('Contact.kind') == 'C') {
			printf('<fieldset>');
			printf('<legend>%s</legend>', __d('lil_crm', 'Company Data'));
			printf($this->LilForm->input('title', array(
				'label' => __d('lil_crm', 'Title') . ':',
				'class' => 'big'
			)));
			
			printf($this->LilForm->input('tax_no', array(
				'label' => __d('lil_crm', 'Tax no.') . ':',
				'class' => 'big'
			)));
			
			printf($this->LilForm->input('tax_status', array(
				'label' => __d('lil_crm', 'Tax status'),
				'type'  => 'checkbox',
				'class' => 'big'
			)));
			printf($this->LilForm->input('descript', array(
				'type'  => 'textarea',
				'label' => __d('lil_crm', 'Description') . ':',
				'div'   => array('id' => 'edit-contact-descript')
			)));
			printf('</fieldset>');
		} else {
			printf('<fieldset>');
			printf('<legend>%s</legend>', __d('lil_crm', 'Personal Data'));
			printf($this->LilForm->input('name', array(
				'label' => __d('lil_crm', 'Name') . ':',
				'div' => array('id' => 'edit-contact-name')
			)));
			printf($this->LilForm->input('surname', array(
				'label' => __d('lil_crm', 'Surname') . ':',
				'div' => array('id' => 'edit-contact-surname')
			)));
			printf($this->LilForm->input('descript', array(
				'type'  => 'textarea',
				'label' => __d('lil_crm', 'Description') . ':',
				'div' => array('id' => 'edit-contact-descript')
			)));
			printf('</fieldset>');
			
			
			printf('<fieldset>');
			printf('<legend>%s</legend>', __d('lil_crm', 'Work Position'));
			printf($this->LilForm->input('Company.id', array('type' => 'hidden')));
			printf($this->LilForm->input('Company.kind', array('type' => 'hidden', 'value' => 'C')));
			printf($this->LilForm->input('Company.title', array(
				'label' => __d('lil_crm', 'Company Name') . ':',
				'after' => ' '.$this->Html->image('/lil_crm/img/ico_contact_check.gif', array(
					'style' => 'display: none;',
					'id' => 'ImageContactCheck'
				)),
				'div' => array('id' => 'edit-contact-company')
			)));
			printf($this->LilForm->input('job', array(
				'label' => __d('lil_crm', 'Job Description') . ':',
				'div' => array('id' => 'edit-contact-job')
			)));
			printf('</fieldset>');
		
		}
		
		if (!$this->Form->value('Contact.id')) {
			printf('<fieldset><legend>%s</legend>', __d('lil_crm', 'Primary Address'));
			echo $this->LilForm->input('ContactsAddress.0.primary', array('type' => 'hidden', 'value' => true));
			echo $this->LilForm->input('ContactsAddress.0.street', array(
				'label'=> __d('lil_crm', 'Street') . ':',
				'div' => array('id' => 'edit-contact-address-street')
			));
			
			echo $this->LilForm->input('ContactsAddress.0.zip', array(
				'label' => __d('lil_crm', 'ZIP') . ':',
				'div' => array('id' => 'edit-contact-address-zip')
			));
			echo $this->LilForm->input('ContactsAddress.0.city', array(
				'label' => __d('lil_crm', 'City') . ':',
				'div' => array('id' => 'edit-contact-address-city')
			));
			
			echo $this->LilForm->input('ContactsAddress.0.country', array(
				'label' => __d('lil_crm', 'State') . ':',
				'div' => array('id' => 'edit-contact-address-country')
			));
			
			printf('</fieldset>');
		}
		
		echo $this->LilForm->submit(__d('lil_crm', 'Save'));
		echo $this->LilForm->end();
?>
<style>
.ui-autocomplete {
	height: 200px !important;
	max-height: 200px !important;
	overflow-y: scroll;
	overflow-x: hidden;
}
/* IE 6 doesn't support max-height
* we use height instead, but this forces the menu to always be this tall
*/
* html .ui-autocomplete {
	height: 200px;
}
</style>
<script type="text/javascript">
	$(document).ready(function() {
		// CLIENTS AUTOCOMPLETE
		<?php
			echo $this->element('js' . DS . 'autocomplete_contact', array('autocomplete_client' => array(
				'image' => '#ImageContactCheck',
				'id'    => '#CompanyId',
				'title' => '#CompanyTitle',
				'kind'  => 'c'
			)));
		?>
		
		// SHOW CHECKMARK FOR CLIENT IF IT'S ID EXISTS
		if ($('#CompanyId').val() !== "") {
			$('#ImageContactCheck').show();
		}
	});
</script>
</div>