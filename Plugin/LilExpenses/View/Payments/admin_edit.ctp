<?php
	$this->set('title_for_layout',
		sprintf('%1$s', $this->Form->value('Payment.id') ? __d('lil_expenses', 'Edit Payment') : __d('lil_expenses', 'Add Payment'))
	);
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_expenses', 'Delete', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_expenses',
				'controller' => 'payments',
				'action'     => 'delete',
				$this->Form->value('Payment.id')
			),
			'params' => array(
				'confirm' => __d('lil_expenses', 'Are you sure you want to delete this payment?')
			)
		)
	));
?>

<div class="form">
<?php
	echo $this->LilForm->create('Payment');
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('referer', array('type' => 'hidden'));
	
	if (!empty($this->data['PaymentsExpense'][0]['expense_id'])) {
		echo $this->LilForm->input('PaymentsExpense.0.expense_id', array(
			'type' => 'hidden',
		));
	}
	
	echo $this->LilForm->input('dat_happened', array(
		'type'  		=> 'date',
		'label'			=> __d('lil_expenses', 'Date') . ':',
	));

	echo $this->LilForm->input('amount', array(
		'label' => __d('lil_expenses', 'Payment') . ':',
		'type'  => 'money',
	));
	
	echo $this->LilForm->input('account_id', array(
		'label' => __d('lil_expenses', 'From/To Account') . ':',
		'type'  => 'select',
		'options' => $accounts,
	));
	
	echo $this->LilForm->input('descript', array(
		'label' => __d('lil_expenses', 'Description') . ':',
		'type'  => 'textarea',
	));
	
	echo $this->LilForm->submit(__d('lil_expenses', 'Save'), array('id' => 'submit-button'));
	echo $this->LilForm->end();
?>
</div>