<h1><?php echo __d('lil_travel_orders', 'Travel Order'); ?></h1>

<h3 id="travel-order-title"><?php printf(__d('lil_travel_orders', 'Travel Order no: %s', $this->Html->clean($data['TravelOrder']['no']))); ?></h3>

<table id="travel-order-data">
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Traveller'); ?>:</td>
		<td><?php echo $this->Html->clean($data['Employee']['title']); ?></td>
	</tr>
	
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Traveler\'s primary address'); ?>:</td>
		<td>
		<?php
			if (!empty($data['Employee']['PrimaryAddress'])) echo $this->Html->clean(
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
			);
		?>
		</td>
	</tr>
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel Employee\'s work position'); ?>:</td>
		<td><?php echo $this->Html->clean($data['Employee']['job']); ?></td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Vehicle\'s owner'); ?>:</td>
		<td><?php echo $this->Html->clean($data['TravelOrder']['vehicle_owner']); ?></td>
	</tr>
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Vehicle\'s name'); ?>:</td>
		<td><?php echo $this->Html->clean($data['TravelOrder']['vehicle_title']); ?></td>
	</tr>
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Vehicle\'s registration'); ?>:</td>
		<td><?php echo $this->Html->clean($data['TravelOrder']['vehicle_registration']); ?></td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel ordered by'); ?>:</td>
		<td><?php echo $this->Html->clean($data['TravelOrder']['taskee']); ?></td>
	</tr>
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel Description'); ?>:</td>
		<td><?php echo $this->Html->clean($data['TravelOrder']['descript']); ?></td>
	</tr>
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel Task and Date'); ?>:</td>
		<td>
		<?php
			echo $this->Html->clean($data['TravelOrder']['task']);
			printf(' <span class="label">%s: </span>', __d('lil_travel_orders', 'task date'));
			echo $this->LilDate->format($data['TravelOrder']['dat_task']);
		?>
		</td>
	</tr>
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel Departure'); ?>:</td>
		<td><?php echo $this->LilDate->dateTimeFormat($data['TravelOrder']['departure']); ?></td>
	</tr>
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel Arrival'); ?>:</td>
		<td><?php echo $this->LilDate->dateTimeFormat($data['TravelOrder']['arrival']); ?></td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel\'s Payer'); ?>:</td>
		<td>
		<?php
			if (!empty($data['Payer']['PrimaryAddress'])) echo $this->Html->clean(
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
			);
		?>
		</td>
	</tr>
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel Advance'); ?>:</td>
		<td><?php echo $this->LilFloat->money($data['TravelOrder']['advance']); ?></td>
	</tr>
	
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Date of Advance Payment'); ?>:</td>
		<td>
		<?php
			if (!empty($data['TravelOrder']['dat_advance'])) echo $this->Time->i18nFormat($data['TravelOrder']['dat_advance']);
		?>
		</td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td class="label"><?php echo __d('lil_travel_orders', 'Travel Order Place and Date'); ?>:</td>
		<td>
		<?php
			echo $this->Html->clean($data['TravelOrder']['location']);
			echo ', ';
			echo $this->LilDate->format($data['TravelOrder']['dat_task']);
		?>
		</td>
	</tr>
	
</table>


<?php
	$total_workdays = 0;
	$total_km = 0;
	$total_expense = 0;
		
	if (!empty($data['TravelOrdersItem'])) {
?>
	<div id="travel-order-expenses">
	<h2><?php echo __d('lil_travel_orders', 'Travel Analytics'); ?></h2>
	<table>
		<tr>
			<th class="small"><?php echo __d('lil_travel_orders', 'Date'); ?></th>
			<th class="center small"><?php echo __d('lil_travel_orders', 'Origin'); ?>/<br /><?php echo __d('lil_travel_orders', 'Destination'); ?></th>
			<th class="center small"><?php echo __d('lil_travel_orders', 'Departure'); ?>/<br /><?php echo __d('lil_travel_orders', 'Arrival'); ?></th>
			<th class="center small"><?php echo __d('lil_travel_orders', 'Duration'); ?></th>
			<th class="right small"><?php echo __d('lil_travel_orders', 'No. days'); ?></th>
			<th class="right small"><?php echo __d('lil_travel_orders', 'Price per day'); ?></th>
			<th class="right small"><?php echo __d('lil_travel_orders', 'Total Workdays'); ?></th>
			<th class="right small"><?php echo __d('lil_travel_orders', 'No. KM'); ?></th>
			<th class="right small"><?php echo __d('lil_travel_orders', 'Price per KM'); ?></th>
			<th class="right small"><?php echo __d('lil_travel_orders', 'Total KM'); ?></th>
		</tr>
		<?php
			foreach ($data['TravelOrdersItem'] as $item) {
				printf('<tr>');
				printf('<td>%s</td>', $this->LilDate->format($item['dat_travel']));
				printf('<td class="center">%1$s/<br />%2$s</td>',
					$this->Html->clean($item['origin']),
					$this->Html->clean($item['destination'])
				);
				printf('<td class="center">%1$s<br />%2$s</td>',
					substr($item['departure'], 0, 5),
					substr($item['arrival'], 0, 5)
				);
				printf('<td class="center">%s</td>', 
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
		?>
		<tr>
			<th colspan="6" class="right"><?php echo __d('lil_travel_orders', 'Total Workdays'); ?>: </th>
			<th class="right"><?php echo $this->LilFloat->money($total_workdays); ?></th>
			<th colspan="2" class="right"><?php echo __d('lil_travel_orders', 'Total KM'); ?>: </th>
			<th class="right"><?php echo $this->LilFloat->money($total_km); ?></th>
		</tr>
	</table>

<?php
	if (!empty($data['TravelOrdersExpense'])) {
?>
	<h2><?php echo __d('lil_travel_orders', 'Travel Expenses'); ?></h2>
	<table>
		<tr>
			<th><?php echo __d('lil_travel_orders', 'Date'); ?></th>
			<th><?php echo __d('lil_travel_orders', 'Description'); ?></th>
			<th class="right"><?php echo __d('lil_travel_orders', 'Price'); ?></th>
		</tr>
		<?php
			foreach ($data['TravelOrdersExpense'] as $expense) {
				printf('<tr>');
				printf('<td>%s</td>', $this->LilDate->format($expense['dat_expense']));
				printf('<td>%s</td>', $this->Html->clean($expense['descript']));
				printf('<td class="right">%s</td>', $this->LilFloat->format($expense['price']));
				printf('</tr>');
				
				$total_expense += round($expense['price'], 2);
			}
		?>
		<tr>
			<th colspan="2" class="right"><?php echo __d('lil_travel_orders', 'Total Expenses'); ?>: </th>
			<th class="right"><?php echo $this->LilFloat->money($total_expense); ?></th>
		</tr>
	</table>
	

<?php
		}
?>
	</div>
<?php
	}
?>

	<table width="60%">
		<tr><th colspan="3" class="big"><?php echo __d('lil_travel_orders', 'Travel Order Recap'); ?></th></tr>
		<tr>
			<td><?php echo __d('lil_travel_orders', 'Total Workdays'); ?></td>
			<td>:</td>
			<td class="right"><?php echo $this->LilFloat->money($total_workdays); ?></td>
		</tr>
		<tr>
			<td><?php echo __d('lil_travel_orders', 'Total KM'); ?></td>
			<td>:</td>
			<td class="right"><?php echo $this->LilFloat->money($total_km); ?></td>
		</tr>
		<tr>
			<td><?php echo __d('lil_travel_orders', 'Total Expenses'); ?></td>
			<td>:</td>
			<td class="right"><?php echo $this->LilFloat->money($total_expense); ?></td>
		</tr>
		<tr>
			<td class="strong"><?php echo __d('lil_travel_orders', 'Total'); ?></td>
			<td>:</td>
			<td class="strong right"><?php echo $this->LilFloat->money($total_workdays + $total_km + $total_expense); ?></td>
		</tr>
		<tr>
			<td><?php echo __d('lil_travel_orders', 'Advance'); ?></td>
			<td>:</td>
			<td class="right"><?php echo $this->LilFloat->money($data['TravelOrder']['advance']); ?></td>
		</tr>
		<tr>
			<td class="strong"><?php echo __d('lil_travel_orders', 'Grand Total'); ?></td>
			<td>:</td>
			<td class="strong right"><?php echo $this->LilFloat->money($total_workdays + $total_km + $total_expense - $data['TravelOrder']['advance']); ?></td>
		</tr>
	</table>
<?php
	printf('<p>%1$s, %2$s</p>', $this->Html->clean($data['TravelOrder']['location']), $this->LilDate->format($data['TravelOrder']['dat_order']));
?>