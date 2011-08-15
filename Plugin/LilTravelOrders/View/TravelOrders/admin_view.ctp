<?php
	$this->set('title_for_layout', sprintf(__d('lil_travel_orders', 'Travel Order #%1$s %2$s'),
		$this->Html->clean($data['TravelOrder']['no']),
		'<span class="light">(' . $this->Html->clean($data['TravelOrdersCounter']['title']) . ')</span>'
	));
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_travel_orders', 'Edit', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_travel_orders',
				'controller' => 'travel_orders',
				'action'     => 'edit',
				$data['TravelOrder']['id'],
				'?'          => array('filter' => array('counter' => $data['TravelOrder']['counter_id']))
			)
		),
		'delete' => array(
			'title' => __d('lil_travel_orders', 'Delete', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_travel_orders',
				'controller' => 'travel_orders',
				'action'     => 'delete',
				$data['TravelOrder']['id'],
			),
			'params' => array(
				'confirm' => __d('lil_travel_orders', 'Are you sure you want to delete this travel order?')
			)
		),
		'print' => array(
			'title' => __d('lil_invoices', 'Print', true),
			'visible' => !empty($data['TravelOrdersCounter']['layout']),
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_travel_orders',
				'controller' => 'travel_orders',
				'action'     => 'print',
				$data['TravelOrder']['id'],
			)
		)
		
	));

	printf('<div class="view-panel"><span class="label">%1$s: </span>%2$s</div>',
		__d('lil_travel_orders', 'Travel Date'),
		$this->LilDate->format($data['TravelOrder']['dat_order'])
	);
	print ('<br />');
	
	print ('<div class="view-panel" id="travel-order-employee">');
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Traveller'),
		$this->Html->clean($data['Employee']['title'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Primary address'),
		(empty($data['Employee']['PrimaryAddress'])) ? '' : $this->Html->clean(
			implode(', ', Set::Filter(array(
				$data['Employee']['PrimaryAddress']['street'],
				implode(' ', Set::Filter(
					array(
						$data['Employee']['PrimaryAddress']['zip'],
						$data['Employee']['PrimaryAddress']['city']
					)
				)),
				$data['Employee']['PrimaryAddress']['country']
			)))
		)
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Work position'),
		$this->Html->clean($data['Employee']['job'])
	);
	print ('</div>');
	print ('<br />');
	
	print ('<div class="view-panel" id="travel-order-travel">');
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Ordered by'),
		$this->Html->clean($data['TravelOrder']['taskee'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Description'),
		$this->Html->clean($data['TravelOrder']['descript'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Task'),
		$this->Html->clean($data['TravelOrder']['task'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Departure Time'),
		$this->LilDate->dateTimeFormat($data['TravelOrder']['departure'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Arrival Time'),
		$this->LilDate->dateTimeFormat($data['TravelOrder']['arrival'])
	);
	print ('</div>');
	print ('<br />');
	
	print ('<div class="view-panel" id="travel-order-vehicle">');
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Vehicle\'s descript'),
		$this->Html->clean($data['TravelOrder']['vehicle_title'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Registration no'),
		$this->Html->clean($data['TravelOrder']['vehicle_registration'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Vehicle\'s owner'),
		$this->Html->clean($data['TravelOrder']['vehicle_owner'])
	);
	print ('</div>');
	print ('<br />');
	
	print ('<div class="view-panel" id="travel-order-expenses">');
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Travel\'s Payer'),
		empty($data['Payer']['PrimaryAddress']) ? '' : $this->Html->clean(
			implode(', ', Set::Filter(array(
				$data['Payer']['title'],
				$data['Payer']['PrimaryAddress']['street'],
				implode(' ', Set::Filter(
					array(
						$data['Payer']['PrimaryAddress']['zip'],
						$data['Payer']['PrimaryAddress']['city']
					)
				)),
				$data['Payer']['PrimaryAddress']['country']
			)))
		)
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Travel Advance'),
		$this->LilFloat->money($data['TravelOrder']['advance'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Advance Paid on'),
		empty($data['TravelOrder']['dat_advance']) ? '' :
			$this->LilDate->format($data['TravelOrder']['dat_advance'])
	);
	print ('</div>');
	print ('<br />');
	
	print ('<div class="view-panel" id="travel-order-foot">');
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Signature Location'),
		$this->Html->clean($data['TravelOrder']['location'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Signature Date'),
		$this->LilDate->format($data['TravelOrder']['dat_order'])
	);
	print ('</div>');
	print ('<br />');



	$total_workdays = 0;
	$total_km = 0;
	$total_expense = 0;
		
	if (!empty($data['TravelOrdersItem'])) {
?>
	<h2><?php echo __d('lil_travel_orders', 'Travel Analytics'); ?></h2>
	<table cellspacing="0" cellpadding="0" class="index-static">
		<thead>
			<tr class="ui-toolbar ui-widget-header ui-corner-top">
				<th><?php echo __d('lil_travel_orders', 'Date'); ?></th>
				<th><?php echo __d('lil_travel_orders', 'Origin'); ?>/<br /><?php __d('lil_travel_orders', 'Destination'); ?></th>
				<th class="center"><?php echo __d('lil_travel_orders', 'Departure'); ?>/<br /><?php __d('lil_travel_orders', 'Arrival'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'Duration'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'No. days'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'Price per day'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'Total Workdays'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'No. KM'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'Price per KM'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'Total KM'); ?></th>
			</tr>
		</thead>
		<?php
			foreach ($data['TravelOrdersItem'] as $item) {
				printf('<tr>');
				printf('<td>%s</td>', $this->LilDate->format($item['dat_travel']));
				printf('<td>%1$s/<br />%2$s</td>',
					$this->Html->clean($item['origin']),
					$this->Html->clean($item['destination'])
				);
				printf('<td class="center">%1$s<br />%2$s</td>',
					$item['departure'],
					$item['arrival']
				);
				printf('<td class="right">%s</td>', 
					$this->LilDate->toHoursAndMinutes(
						strtotime($item['arrival']) - strtotime($item['departure']),
						'<br />'
					)
				);
				printf('<td class="right">%s</td>', $this->LilFloat->format($item['workdays']));
				printf('<td class="right">%s</td>', $this->LilFloat->format($item['workday_price']));
				printf('<td class="right">%s</td>', $this->LilFloat->format($item['workdays'] * $item['workday_price']));
				
				printf('<td class="right">%s</td>', $this->LilFloat->format($item['km']));
				printf('<td class="right">%s</td>', $this->LilFloat->format($item['km_price']));
				printf('<td class="right">%s</td>', $this->LilFloat->format($item['km'] * $item['km_price']));
				
				printf('</tr>');
				
				$total_workdays += round($item['workdays'] * $item['workday_price'], 2);
				$total_km += round($item['km'] * $item['km_price'], 2);
			}
			
		printf('<tfoot><tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">');
		?>
			<th colspan="6" class="right"><?php echo __d('lil_travel_orders', 'Total Workdays'); ?>: </th>
			<th class="right"><?php echo $this->LilFloat->money($total_workdays); ?></th>
			<th colspan="2" class="right"><?php echo __d('lil_travel_orders', 'Total KM'); ?>: </th>
			<th class="right"><?php echo $this->LilFloat->money($total_km); ?></th>
		</tr></tfoot>
	</table>
<?php
	}
?>
<?php
	if (!empty($data['TravelOrdersExpense'])) {
		print ('<br />');
?>
	<h2><?php echo __d('lil_travel_orders', 'Travel Expenses'); ?></h2>
	<table cellspacing="0" cellpadding="0" class="index-static">
		<thead>
			<tr class="ui-toolbar ui-widget-header ui-corner-top">
				<th><?php echo __d('lil_travel_orders', 'Date'); ?></th>
				<th><?php echo __d('lil_travel_orders', 'Description'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'Price'); ?></th>
			</tr>
		</thead>
		<?php
			foreach ($data['TravelOrdersExpense'] as $expense) {
				printf('<tr>');
				printf('<td>%s</td>', $this->LilDate->format($expense['dat_expense']));
				printf('<td>%s</td>', $this->Html->clean($expense['descript']));
				printf('<td class="right">%s</td>', $this->LilFloat->format($expense['price']));
				printf('</tr>');
				
				$total_expense += round($expense['price'], 2);
			}
		printf('<tfoot><tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">');
		?>
			<th colspan="2" class="right"><?php echo __d('lil_travel_orders', 'Total Expenses'); ?>: </th>
			<th class="right"><?php echo $this->LilFloat->money($total_expense); ?></th>
		</tr></tfoot>
	</table>
<?php
	print ('<br />');
	}
?>

<h2><?php echo __d('lil_travel_orders', 'Travel Order Recap'); ?></h2>
<?php
	print ('<div class="view-panel" id="travel-order-recap">');
	printf('<span class="label">%1$s: </span><span class="light">%2$s</span><br />',
		__d('lil_travel_orders', 'Total Workdays'),
		$this->LilFloat->money($total_workdays)
	);
	printf('<span class="label">%1$s: </span><span class="light">%2$s</span><br />',
		__d('lil_travel_orders', 'Total KM'),
		$this->LilFloat->money($total_km)
	);
	printf('<span class="label big">%1$s: </span><span class="light">%2$s</span><br />',
		__d('lil_travel_orders', 'Total Expenses'),
		$this->LilFloat->money($total_expense)
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Total'),
		$this->LilFloat->money($total_workdays + $total_km + $total_expense)
	);
	printf('<span class="label">%1$s: </span><span class="light">%2$s</span><br />',
		__d('lil_travel_orders', 'Advance'),
		$this->LilFloat->money($data['TravelOrder']['advance'])
	);
	printf('<span class="label">%1$s: </span>%2$s<br />',
		__d('lil_travel_orders', 'Grand Total'),
		$this->LilFloat->money($total_workdays + $total_km + $total_expense - $data['TravelOrder']['advance'])
	);
	print ('</div>');
	print ('<br />');
	
?>