<?php	
	echo '<div>';
	echo '<table cellspacing="0" cellpadding="0" id="travel-orders-expenses-table" class="index-static">';
	echo '<thead><tr class="ui-toolbar ui-widget-header ui-corner-top">';
	printf('<th class="left">%s</th>', __d('lil_travel_orders', 'Date'));
	printf('<th class="left">%s</th>', __d('lil_travel_orders', 'Description'));
-	printf('<th class="right">%s</th>', __d('lil_travel_orders', 'Price'));
	print ('<th class="center actions">&nbsp;</th>');
	echo '</tr></thead>';
	
	$loop_items = array();
	if (empty($this->data['TravelOrdersExpense']) || (!$loop_items = $this->data['TravelOrdersExpense'])) {
		$loop_items = array(1);
	}
	
	echo '<tbody>';
	
	$grand_total = 0;
	foreach ($loop_items as $k => $item) {
		// echo empty line
		echo '<tr>';
		
		printf('<td>%s</td>',
			$this->LilForm->input(
				'TravelOrdersExpense.' . $k . '.dat_expense',
				array(
					'label' => false,
					'class' => 'travel-orders-expense-dat_expense',
					'type'  => 'date',
				)
			)
		);
		
		printf('<td>%s</td>',
			$this->LilForm->input(
				'TravelOrdersExpense.' . $k . '.descript',
				array(
					'label' => false,
					'size'  => 50,
					'class' => 'travel-orders-expense-descript',
				)
			)
		);
		
		$grand_total += $this->Form->value('TravelOrdersExpense.' . $k . '.price');
		printf('<td class="right">%s</td>', $this->LilForm->input(
			'TravelOrdersExpense.' . $k . '.price',
			array(
				'type' => 'float',
				'label' => false,
				'class' => 'right travel-orders-expense-price',
				
				'error' => __d('lil_travel_orders', 'Blank')
			)
		));
		
		printf('<td class="center">%s</td>',
			sprintf('<span class="travel-orders-expense-id">%s</span>',
		 		$this->LilForm->input('TravelOrdersExpense.' . $k . '.id', array('type' => 'hidden'))
	 		) .
	 		sprintf('<span class="travel-orders-expense-order_id">%s</span>',
		 		$this->LilForm->input('TravelOrdersExpense.' . $k . '.order_id', array('type' => 'hidden'))
	 		) .
			$this->Html->link(
				$this->Html->image('/lil_travel_orders/img/remove.gif', array('alt' => __d('lil_travel_orders', 'Remove Item'))),
				array(
					'admin'      => true,
					'plugin'     => false,
					'controller' => 'travel_orders_expenses',
					'action'     => 'delete',
					$this->Form->value('TravelOrdersExpense.' . $k . '.id')
				),
				array(
					'escape'  => false,
					'onclick' => 'removeTravelOrdersExpensesRow(this); return false;',
					'class'   => 'travel-orders-expense-remove',
				)
			)
		); 
		
		echo '</tr>';
	}
	echo '</tbody>';
	
	// table FOOTER with grand total and add new row link
	echo '<tfoot><tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">';
	printf(
		'<td colspan="1">%s</td>',
		$this->Html->link(
			__d('lil_travel_orders', 'Add new Expense'),
			'javascript:void(0);',
			array('onclick' => 'addTravelOrdersExpensesRow()')
		)
	);
	printf(
		'<td  colspan="1" class="right strong">%1$s:</td>',
		__d('lil_travel_orders', 'Total Expenses')
	);
	printf(
		'<td class="strong right" id="travel-orders-expenses-total">%s</td>',
		$this->LilFloat->format($grand_total)
	);
	printf(
		'<td class="left strong">%s</td>',
		$this->LilFloat->moneySymbol()
	);
	echo '</tr></tfoot>';
	
	echo '</table></div>';