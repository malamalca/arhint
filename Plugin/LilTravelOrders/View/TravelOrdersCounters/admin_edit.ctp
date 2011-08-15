<?php
	$this->set('title_for_layout', $title_for_layout = __d('lil_travel_orders', 'Edit Counters', true));
?>
<div class="head">
	<h1><?php __d('lil_travel_orders', 'Edit Counters'); ?></h1>
</div>
<div class="form">
<?php
	echo $this->LilForm->create('TravelOrdersCounter', array('type' => 'file'));
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('referer', array('type' => 'hidden'));
	
	// menu must be inside form scope
	if ($this->Form->value('TravelOrdersCounter.id')) {
		$this->set('main_menu', array(
			'delete' => array(
				'title' => __d('lil_travel_orders', 'Delete', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_travel_orders',
					'controller' => 'travel_orders_counters',
					'action'     => 'delete',
					$this->Form->value('TravelOrdersCounter.id')
				),
				'params' => array(
					'confirm' => __d('lil_travel_orders', 'Are you sure you want to delete this counter?')
				)
			)
		));
	}
	
	echo '<fieldset>';
	echo '<legend>' . __d('lil_travel_orders', 'Basics', true) . '</legend>';
	
	echo $this->LilForm->input('title', array(
		'label' => __d('lil_travel_orders', 'Title', true) . ':',
	));
	
	echo $this->LilForm->input('counter', array(
		'label' => __d('lil_travel_orders', 'Counter Status', true) . ':',
	));
	echo $this->LilForm->input('expense', array(
		'type'  => 'checkbox',
		'label' => __d('lil_travel_orders', 'This counter creates income/expense.', true),
	));
	echo $this->LilForm->input('active', array(
		'type'  => 'checkbox',
		'label' => __d('lil_travel_orders', 'This counter is active', true),
	));
	echo '</fieldset>';
	
	echo '<fieldset>';
	echo '<legend>' . __d('lil_travel_orders', 'Travel Order Templates', true) . '</legend>';
	
	echo $this->LilForm->input('mask', array(
		'label' => __d('lil_travel_orders', 'Mask for Travel Order no', true) . ':',
	));
	
	echo $this->LilForm->input('layout_title', array(
		'label'   => __d('lil_travel_orders', 'Layout title format', true) . ':',
		'default' => sprintf(__d('lil_travel_orders', 'Travel Order [[no]]'))
	));
	
	echo $this->LilForm->input('header', array(
		'label'   => __d('lil_travel_orders', 'Header image', true) . ':',
		'type'    => $this->Form->value('Counter.header') ? 'text' : 'file'
	));
	
	echo $this->LilForm->input('footer', array(
		'label' => __d('lil_travel_orders', 'Footer Image', true) . ':',
		'type'    => $this->Form->value('Counter.footer') ? 'text' : 'file'
	));
	echo '</fieldset>';

	echo $this->LilForm->submit(__d('lil_travel_orders', 'Save', true));
	
	echo $this->LilForm->end();
?>
</div>