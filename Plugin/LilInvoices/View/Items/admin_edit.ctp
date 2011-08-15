<?php
	$this->set('title_for_layout', $title_for_layout = __d('lil_invoices', 'Edit Items'));
?>
<div class="head">
	<h1><?php __d('lil_invoices', 'Edit Items'); ?></h1>
</div>
<div class="form">
<?php
	echo $this->LilForm->create('Item', array('type' => 'file'));
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('referer', array('type' => 'hidden'));
	
	echo $this->LilForm->input('descript', array(
		'label' => __d('lil_invoices', 'Description') . ':',
	));
	
	echo $this->LilForm->input(
		'qty',
		array(
			'type'  => 'float',
			'label' => __d('lil_invoices', 'Quantity') . ':',
		)
	);
	
	echo $this->LilForm->input(
		'unit',
		array(
			'label' => __d('lil_invoices', 'Unit') . ':',
		)
	);
	
	echo $this->LilForm->input(
		'price',
		array(
			'type'  => 'money',
			'label' => __d('lil_invoices', 'Price') . ':',
		)
	);
	
	echo $this->LilForm->input(
		'tax',
		array(
			'type'  => 'float',
			'label' => __d('lil_invoices', 'Tax') . ':',
		)
	);

	echo $this->LilForm->submit(__d('lil_invoices', 'Save'));
	
	echo $this->LilForm->end();
?>
</div>