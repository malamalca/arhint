<?php
$user_edit = array(
	'title_for_layout' =>
		($this->Form->value('Item.id') ? __d('lil_invoices', 'Edit Item') : __d('lil_invoices', 'Add Item')),
	'menu' => array(
		'delete' => array(
			'title' => __d('lil_invoices', 'Delete', true),
			'visible' => $this->LilForm->value('Item.id'),
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_invoices',
				'controller' => 'items',
				'action'     => 'delete',
				$this->LilForm->value('Item.id'),
			),
			'params' => array(
				'confirm' => __d('lil_invoices', 'Are you sure you want to delete this item?')
			)
		),
	),
	'form' => array(
		'pre' => '<div class="form">',
		'post' => '</div>',
		'lines' => array(
			'form_start' => array(
				'class'      => $this->LilForm,
				'method'     => 'create',
				'parameters' => array('model' => 'Item')
			),
			'id' => array(
				'class'      => $this->LilForm,
				'method'     => 'hidden',
				'parameters' => array('field' => 'id')
			),
			'referer' => array(
				'class'      => $this->LilForm,
				'method'     => 'hidden',
				'parameters' => array('field' => 'referer')
			),
			'descript' => array(
				'class'      => $this->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field'   => 'descript',
					'options' => array(
						'label' => __d('lil_invoices', 'Description') . ':',
						'error' => __d('lil_invoices', 'Description is required.'),
					)
				)
			),
			'qty' => array(
				'class'      => $this->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field'   => 'qty',
					'options' => array(
						'type'  => 'float', 'places' => 1,
						'label' => __d('lil_invoices', 'Quantity') . ':',
					)
				)
			),
			'unit' => array(
				'class'      => $this->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field'   => 'unit',
					'options' => array(
						'label' => __d('lil_invoices', 'Unit') . ':',
					)
				)
			),
			'price' => array(
				'class'      => $this->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field'   => 'price',
					'options' => array(
						'type'  => 'float',
						'label' => __d('lil_invoices', 'Price') . ':',
					)
				)
			),
			'tax' => array(
				'class'      => $this->LilForm,
				'method'     => 'input',
				'parameters' => array(
					'field'   => 'tax',
					'options' => array(
						'type'  => 'float', 'places' => 1,
						'label' => __d('lil_invoices', 'Tax [%]') . ':',
					)
				)
			),
			'submit' => array(
				'class'      => $this->LilForm,
				'method'     => 'submit',
				'parameters' => array(
					'label' => __d('lil_invoices', 'Save')
				)
			),
			'form_end' => array(
				'class'      => $this->LilForm,
				'method'     => 'end',
				'parameters' => array()
			),
		)
	)
);
			
$user_edit = $this->callPluginHandlers('lil_invoices_form_edit_item', $user_edit);
$this->Lil->form($user_edit);