<?php	
	echo '<div>';
	echo '<table cellspacing="0" cellpadding="0" id="travel-orders-analytics-table" class="index-static">';
	echo '<thead><tr class="ui-toolbar ui-widget-header ui-corner-top">';
	printf('<th class="left small">%s</th>', __d('lil_travel_orders', 'Date'));
	printf('<th class="left small">%1$s/<br />%2$s</th>', __d('lil_travel_orders', 'Origin'), __d('lil_travel_orders', 'Destination'));
	printf('<th class="left small">%1$s/<br />%2$s</th>', __d('lil_travel_orders', 'Departure'), __d('lil_travel_orders', 'Arrival'));
	printf('<th class="right small">%s</th>', __d('lil_travel_orders', 'Duration'));
	
	printf('<th class="left small">%s</th>', __d('lil_travel_orders', 'No. days'));
	printf('<th class="left small">%s</th>', __d('lil_travel_orders', 'Price per day'));
	printf('<th class="right small">%s</th>', __d('lil_travel_orders', 'Total Workdays'));
	
	printf('<th class="left small">%s</th>', __d('lil_travel_orders', 'No. KM'));
	printf('<th class="left small">%s</th>', __d('lil_travel_orders', 'Price per KM'));
	printf('<th class="right small">%s</th>', __d('lil_travel_orders', 'Total KM'));
	
	print ('<th class="actions">&nbsp;</th>');
	echo '</tr></thead>';
	
	$loop_items = array();
	if (empty($this->data['TravelOrdersItem']) || (!$loop_items = $this->data['TravelOrdersItem'])) {
		$loop_items = array(1);
	}
	
	echo '<tbody>';
	
	$workday_total = 0; $workdays_total = 0;
	$km_total = 0; $kms_total = 0;
	foreach ($loop_items as $k => $item) {
		// echo empty line
		echo '<tr>';
		
		printf('<td class="left">%s</td>',
			$this->LilForm->input(
				'TravelOrdersItem.' . $k . '.dat_travel',
				array(
					'label' => false,
					'class' => 'travel-orders-item-dat_travel',
					'type'  => 'date',
				)
			)
		);
		
		printf('<td>%1$s%2$s</td>',
			$this->LilForm->input(
				'TravelOrdersItem.' . $k . '.origin',
				array(
					'label' => false,
					'size'  => 15,
					'class' => 'travel-orders-item-origin',
				)
			),
			$this->LilForm->input(
				'TravelOrdersItem.' . $k . '.destination',
				array(
					'label' => false,
					'size'  => 15,
					'class' => 'travel-orders-item-destination',
				)
			)
		);
		
		printf('<td class="nowrap">%1$s%2$s</td>',
			$this->LilForm->input(
				'TravelOrdersItem.' . $k . '.departure',
				array(
					'label' => false,
					'type'  => 'time',
					'interval' => 5,
					'class' => 'travel-orders-item-departure',
				)
			),
			$this->LilForm->input(
				'TravelOrdersItem.' . $k . '.arrival',
				array(
					'label' => false,
					'type'  => 'time',
					'interval' => 5,
					'class' => 'travel-orders-item-arrival',
				)
			)
		);
		
		printf('<td class="right td-travel-orders-item-duration">%1$s</td>',
			$this->LilDate->toHoursAndMinutes(
				strtotime($this->Form->value('TravelOrdersItem.' . $k . '.arrival')) -
				strtotime($this->Form->value('TravelOrdersItem.' . $k . '.departure')),
				'<br />'
			)
		);
		
		printf('<td>%s</td>', $this->LilForm->input(
			'TravelOrdersItem.' . $k . '.workdays',
			array(
				'label' => false,
				'type'  => 'float',
				'class' => 'travel-orders-item-workdays',
			)
		));
		
		printf('<td>%s</td>', $this->LilForm->input(
			'TravelOrdersItem.' . $k . '.workday_price',
			array(
				'label' => false,
				'type'  => 'float',
				'class' => 'travel-orders-item-workday_price',
			)
		));
		
		$workday_total = 
			$this->Form->value('TravelOrdersItem.' . $k . '.workdays') * 
			$this->Form->value('TravelOrdersItem.' . $k . '.workday_price');
		$workdays_total += $workday_total;
		printf('<td class="right td-travel-orders-item-workday-total">%s</td>',
			$this->LilFloat->format($workday_total)
		);
		
		printf('<td class="right">%s</td>', $this->LilForm->input(
			'TravelOrdersItem.' . $k . '.km',
			array(
				'label'  => false,
				'type'   => 'float',
				'places' => 1,
				'class'  => 'travel-orders-item-km',
			)
		));
		
		printf('<td class="right">%s</td>', $this->LilForm->input(
			'TravelOrdersItem.' . $k . '.km_price',
			array(
				'label' => false,
				'type'   => 'float',
				'class' => 'travel-orders-item-km_price',
			)
		));
		
		$km_total = 
			$this->Form->value('TravelOrdersItem.' . $k . '.km') * 
			$this->Form->value('TravelOrdersItem.' . $k . '.km_price');
		$kms_total += $km_total;
		printf('<td class="right td-travel-orders-item-km-total">%s</td>',
			$this->LilFloat->format($km_total)
		);
		
		printf('<td class="center">%s</td>',
			sprintf('<span class="travel-orders-item-id">%s</span>',
		 		$this->LilForm->input('TravelOrdersItem.' . $k . '.id', array('type' => 'hidden'))
	 		) .
	 		sprintf('<span class="travel-orders-item-order_id">%s</span>',
		 		$this->LilForm->input('TravelOrdersItem.' . $k . '.order_id', array('type' => 'hidden'))
	 		) .
			$this->Html->link(
				$this->Html->image('/lil_travel_orders/img/remove.gif', array('alt' => __d('lil_travel_orders', 'Remove Item'))),
				array(
					'admin'      => true,
					'plugin'     => false,
					'controller' => 'travel_orders_items',
					'action'     => 'delete',
					$this->Form->value('TravelOrdersItem.' . $k . '.id')
				),
				array(
					'escape'  => false,
					'onclick' => 'removeTravelOrdersItemsRow(this); return false;',
					'class'   => 'travel-orders-item-remove',
				)
			)
		); 
		
		echo '</tr>';
	}
	echo '</tbody>';
	
	// table FOOTER with grand total and add new row link
	echo '<tfoot><tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">';
	printf(
		'<td colspan="2">%s</td>',
		$this->Html->link(
			__d('lil_travel_orders', 'Add new'),
			'javascript:void(0);',
			array('onclick' => 'addTravelOrdersItemsRow()')
		)
	);
	printf(
		'<td  colspan="4" class="right strong small">%1$s:</td>',
		__d('lil_travel_orders', 'Total Workdays')
	);
	printf(
		'<td class="strong right nowrap small" id="travel-orders-analytics-workdays-total">%s</td>',
		$this->LilFloat->money($workdays_total)
	);
	printf('<td colspan="2" class="right strong small">%s:</td>', __d('lil_travel_orders', 'Total KM'));
 	printf(
 		'<td class="strong right nowrap small" id="travel-orders-analytics-kms-total">%s</td>',
 		$this->LilFloat->money($kms_total)
	);
	printf('<td class="left strong small">&nbsp;</td>');
	echo '</tr></tfoot>';
	
	echo '</table></div>';