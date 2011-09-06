<?php
	$this->set('title_for_layout', $title_for_layout = __d('lil_travel_orders', 'Add or edit a Travel Order', true));
?>
<div class="head">
	<h1><?php echo $title_for_layout; ?></h1>
</div>
<div class="travel-order-form form">
<?php
	echo $this->LilForm->create('TravelOrder', array(
		'type' => 'file', 
		'url' => array(
			'action' => $this->Form->value('TravelOrder.id') ? 'edit' : 'add',
		)
	));
	
	echo $this->LilForm->input('id', array('id' => 'TravelOrderId'));
	echo $this->LilForm->input('referer', array('type' => 'hidden'));
	echo $this->LilForm->input('counter_id', array('type' => 'hidden'));
?>

	<fieldset>
 		<legend><?php __d('lil_travel_orders', 'Basics');?></legend>
 		
<?php
		
		echo $this->LilForm->input('no', array(
			'label'    => __d('lil_travel_orders', 'Travel Order no', true) . ':',
			'default'  => isset($counter['order_no']) ? $counter['order_no'] : '',
			'disabled' => !$this->Lil->currentUser->role('admin') && !empty($counter['InvoicesCounter']['mask']),
			'class'    => 'big'
		));
		
		echo $this->LilForm->input('location', array(
			'label'    => __d('lil_travel_orders', 'Travel Order Signature Location', true) . ':',
			'disabled' => !$this->Lil->currentUser->role('admin'),
		));
		echo $this->LilForm->input('dat_order', array(
			'label'			=> __d('lil_travel_orders', 'Travel Order Signature Date', true) . ':',
			'dateFormat'	=> Configure::read('dateFormat'),
			'separator'		=> Configure::read('dateSeparator'),
		));
	?>
	</fieldset>
	<fieldset>
		<legend><?php __d('lil_travel_orders', 'Travel Data');?></legend>
	<?php
		echo $this->LilForm->input('dat_task', array(
			'label'			=> __d('lil_travel_orders', 'Travel Date', true) . ':',
			'dateFormat'	=> Configure::read('dateFormat'),
			'separator'		=> Configure::read('dateSeparator'),
		));
		
		if ($this->Lil->isAdmin()) {
			echo $this->LilForm->input('employee_id', array(
				'label'   => __d('lil_travel_orders', 'Traveller', true) . ':',
				'default' => $this->Lil->currentUser->get('id'),
				'options' => $users
			));
		} else {
			echo $this->LilForm->input('employee_id', array(
				'type'    => 'hidden',
				'default' => $this->Lil->currentUser->get('id')
			));
		}
		
		echo $this->LilForm->input('taskee', array(
			'label'    => __d('lil_travel_orders', 'Travel ordered by', true) . ':',
		));
		
		echo $this->LilForm->input('task', array(
			'label'    => __d('lil_travel_orders', 'Travel Task', true) . ':',
		));
		
		echo $this->LilForm->input('descript', array(
			'label'    => __d('lil_travel_orders', 'Travel Description', true) . ':',
			'default' => $counter['TravelOrdersCounter']['template_descript']
		));
		
		
		echo $this->LilForm->input('departure', array(
			'label'			=> __d('lil_travel_orders', 'Travel Departure', true) . ':',
			'dateFormat'	=> Configure::read('dateFormat'),
			'separator'		=> Configure::read('dateSeparator'),
			'timeFormat'	=> Configure::read('timeFormat'),
			'interval'		=> 5
		));
		echo $this->LilForm->input('arrival', array(
			'label'			=> __d('lil_travel_orders', 'Travel Arrival', true) . ':',
			'dateFormat'	=> Configure::read('dateFormat'),
			'separator'		=> Configure::read('dateSeparator'),
			'timeFormat'	=> Configure::read('timeFormat'),
			'interval'		=> 5
		));
?>
	</fieldset>
	<fieldset>
		<legend><?php __d('lil_travel_orders', 'Vehicle');?></legend>
	<?php
		echo $this->LilForm->input('vehicle_title', array(
			'label' => __d('lil_travel_orders', 'Vehicle\'s name', true) . ':',
		));
		echo $this->LilForm->input('vehicle_registration', array(
			'label' => __d('lil_travel_orders', 'Vehicle\'s registration', true) . ':',
		));
		echo $this->LilForm->input('vehicle_owner', array(
			'label' => __d('lil_travel_orders', 'Vehicle\'s owner', true) . ':',
		));
	?>
	</fieldset>
	<fieldset>
		<legend><?php __d('lil_travel_orders', 'Travel Expenses');?></legend>
		<?php
			echo $this->LilForm->input('payer_id', array('type' => 'hidden'));
			echo $this->LilForm->input('payer', array(
				'label' => __d('lil_travel_orders', 'Travel\'s Payer', true) . ':',
				'type'  => 'text',
				'div'   => array('class' => 'input text' . (!empty($this->validationErrors['TravelOrder']['payer_id']) ? ' error' : '')),
				'after' => ' '.$this->Html->image('ico_contact_check.gif', array(
					'style' => ($this->Form->value('TravelOrder.payer_id')) ? '' : 'display: none;',
					'id'    => 'ImageContactCheck'
				))
			));
			
			echo $this->LilForm->input('advance', array(
				'label' => __d('lil_travel_orders', 'Travel Advance', true) . ':',
				'class' => 'right',
				'value' => $this->LilFloat->format($this->Form->value('TravelOrder.advance'), 2)
			));
			echo $this->LilForm->input('dat_advance', array(
				'label'			=> __d('lil_travel_orders', 'Date of Advance Payment', true) . ':',
				'empty'			=> true,
				'dateFormat'	=> Configure::read('dateFormat'),
				'separator'		=> Configure::read('dateSeparator'),
			));
		?>
	</fieldset>
	<fieldset>
		<legend><?php __d('lil_travel_orders', 'Travel Analytics');?></legend>
	<?php
			echo $this->element('travel_orders' . DS . 'edit_items');
	?>
	</fieldset>
		<fieldset>
		<legend><?php __d('lil_travel_orders', 'Travel Expenses');?></legend>
	<?php
			echo $this->element('travel_orders' . DS . 'edit_expenses');
	?>
	</fieldset>
	<?php
		echo $this->LilForm->submit(__d('lil_travel_orders', 'Save', true));
		echo $this->LilForm->end();
	?>
</div>
<script type="text/javascript">
	// constants for scripts
	<?php
		App::uses('Sanitize', 'Utility');
	?>
	var isUserAdmin = <?php echo $this->Lil->currentUser->role('admin') ? 'true' : 'false'; ?>;
	
	<?php
		$this->Html->script('app.travel_order_edit', array('inline' => false));
		
		echo $this->element('js' . DS . 'autocomplete_client', array('autocomplete_client' => array('title' => '#TravelOrderPayer', 'id' => '#TravelOrderPayerId')));
		
		echo $this->element('js' . DS . 'toggle_expenses', array('toggle_expenses' => array('project_id' => '#TravelOrderProjectId', 'id' => '#TravelOrderExpenseId')));
		echo $this->element('js' . DS . 'toggle_tasks');
		
		// do a date pickers
		echo $this->element('js' . DS . 'datepicker', array('element_id' => 'TravelOrderDatTask'));
		echo $this->element('js' . DS . 'datepicker', array('element_id' => 'TravelOrderDatAdvance'));
		echo $this->element('js' . DS . 'datepicker', array('element_id' => 'TravelOrderDatOrder'));
		echo $this->element('js' . DS . 'datepicker', array('element_id' => 'TravelOrderArrival'));
		echo $this->element('js' . DS . 'datepicker', array('element_id' => 'TravelOrderDeparture'));
		echo $this->element('js' . DS . 'datepicker', array('element_id' => 'TaskDeadline'));
	?>
</script>