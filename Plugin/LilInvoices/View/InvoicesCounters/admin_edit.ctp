<?php
	$this->set('title_for_layout', $title_for_layout = __d('lil_invoices', 'Edit Counters', true));
?>
<div class="head">
	<h1><?php __d('lil_invoices', 'Edit Counters'); ?></h1>
</div>
<div class="form">
<?php
	echo $this->LilForm->create('InvoicesCounter', array('type' => 'file'));
	
	// menu must be inside form scope
	if ($this->Form->value('InvoicesCounter.id')) {
		$this->set('main_menu', array(
			'delete' => array(
				'title' => __d('lil_invoices', 'Delete', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_invoices',
					'controller' => 'counters',
					'action'     => 'delete',
					$this->Form->value('InvoicesCounter.id')
				),
				'params' => array(
					'confirm' => __d('lil_invoices', 'Are you sure you want to delete this counter?')
				)
			)
		));
	}
	
	
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('referer', array('type' => 'hidden'));
	
	
	echo '<fieldset>';
	echo '<legend>' . __d('lil_invoices', 'Basics', true) . '</legend>';
	
	echo $this->LilForm->input('kind', array(
		'label'   => __d('lil_invoices', 'Kind', true) . ':',
		'options' => array(
			'issued' => __d('lil_invoices', 'Issued Invoices', true),
			'received' => __d('lil_invoices', 'Received Invoices', true),
		)
	));
	
	echo $this->LilForm->input('title', array(
		'label' => __d('lil_invoices', 'Title', true) . ':',
	));
	
	echo $this->LilForm->input('counter', array(
		'label' => __d('lil_invoices', 'InvoicesCounter Status', true) . ':',
	));
	echo $this->LilForm->input('expense', array(
		'type'  => 'checkbox',
		'label' => __d('lil_invoices', 'This counter creates income/expense.', true),
	));
	echo $this->LilForm->input('active', array(
		'type'  => 'checkbox',
		'label' => __d('lil_invoices', 'This counter is active', true),
	));
	echo '</fieldset>';
	
	echo '<fieldset>';
	echo '<legend>' . __d('lil_invoices', 'Invoice Templates', true) . '</legend>';
	
	echo $this->LilForm->input('mask', array(
		'label' => __d('lil_invoices', 'Mask for Invoice no', true) . ':',
	));
	
	echo $this->LilForm->input('template_descript', array(
		'label' => __d('lil_invoices', 'Template for Invoice description', true) . ':',
		'type'    => 'textarea',
	));
	echo '</fieldset>';
	
	echo '<fieldset>';
	echo '<legend>' . __d('lil_invoices', 'Layout Settings', true) . '</legend>';
	
	echo $this->LilForm->input('layout_title', array(
		'label'   => __d('lil_invoices', 'Layout title', true) . ':',
		'default' => '[[no]]'
	));
	
	echo $this->LilForm->input('layout', array(
		'label' => __d('lil_invoices', 'Layout', true) . ':',
		'options' => $layouts
	));
	
	echo $this->LilForm->input('header', array(
		'label'   => __d('lil_invoices', 'Header image', true) . ':',
		'type'    => $this->Form->value('InvoicesCounter.header') ? 'text' : 'file'
	));
	
	echo $this->LilForm->input('footer', array(
		'label' => __d('lil_invoices', 'Footer Image', true) . ':',
		'type'    => $this->Form->value('InvoicesCounter.footer') ? 'text' : 'file'
	));
	echo '</fieldset>';

	echo $this->LilForm->submit(__d('lil_invoices', 'Save', true));
	
	echo $this->LilForm->end();
?>
</div>