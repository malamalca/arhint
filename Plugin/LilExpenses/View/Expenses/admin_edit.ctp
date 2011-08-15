<?php $this->set('title_for_layout', __d('lil_expenses', 'Add or Edit Expense')); ?>

<div class="form">
<?php
	echo $this->LilForm->create('Expense');
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('referer', array('type' => 'hidden'));
	echo $this->LilForm->input('kind', array('type' => 'hidden', 'default' => 'custom'));
	

	echo $this->LilForm->input('title', array('label' => __d('lil_expenses', 'Title') . ':'));
	echo $this->LilForm->input('dat_happened', array(
		'label'	=> __d('lil_expenses', 'Date') . ':',
		'type'	=> 'date'
	));

	echo $this->LilForm->input('total', array(
		'label' => __d('lil_expenses', 'Total') . ':',
		'type'  => 'money'
	));
	
	// auto create payment 
	if (!$this->Form->value('Expense.id')) {
		echo $this->LilForm->input('payment', array(
			'label' => __d('lil_expenses', 'Payment') . ':',
			'type'  => 'select',
			'empty' => '-- ' . __d('lil_expenses', 'do not create payment') . ' --',
			'options' => array(
				'c' => __d('lil_expenses', 'paid from company account'),
				'p' => __d('lil_expenses', 'paid from private account'),
				'o' => __d('lil_expenses', 'paid from other source')
			)
		));
	}
	
	echo $this->LilForm->input('project_id', array(
		'label' => __d('lil_expenses', 'Project') . ':',
		'empty' => true,
		'default' => $this->Lil->currentArea->get('id')
	));
	
	echo $this->LilForm->input('user_id', array(
		'label' => __d('lil_expenses', 'User') . ':',
		'empty' => true,
	));
	
	echo $this->LilForm->submit(__d('lil_expenses', 'Save'));
	echo $this->LilForm->end();
?>
</div>