<?php
	$this->set('title_for_layout', __d('lil_travel_orders', 'Counters'));
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_travel_orders', 'Add', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_travel_orders',
				'controller' => 'travel_orders_counters',
				'action'     => 'add',
			)
		)
	));
?>
<div class="index">
	<table cellspacing="0" cellpadding="0" class="index">
		<thead>
			<tr>
				<th class="left"><?php echo __d('lil_travel_orders', 'Title'); ?></th>
				<th class="right"><?php echo __d('lil_travel_orders', 'InvoicesCounter Status'); ?></th>
				<th class="left"><?php echo __d('lil_travel_orders', 'Mask'); ?></th>
				<th class="left"><?php echo __d('lil_travel_orders', 'Template'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
			if (empty($data)) {
				echo '<tr><td colspan="6" class="light">' . __d('lil_travel_orders', 'No counters found', true) . '</td></tr>';
			} else {
				foreach ($data as $item) {
					echo '<tr>';
						printf('<td class="left%1$s">%2$s',
							$item['TravelOrdersCounter']['active'] ? '' : ' light',
							$this->Html->link($item['TravelOrdersCounter']['title'], array(
								'action' => 'edit',
								$item['TravelOrdersCounter']['id']
							))
						);
						echo '</td>';
						printf('<td class="right%s">', ($item['TravelOrdersCounter']['active'] ? '' : ' light'));
							echo $this->Html->clean($item['TravelOrdersCounter']['counter']);
						echo '</td>';
						printf('<td class="left%s">', ($item['TravelOrdersCounter']['active'] ? '' : ' light'));
							echo $this->Html->clean($item['TravelOrdersCounter']['mask']);
						echo '</td>';
						printf('<td class="left%s">', ($item['TravelOrdersCounter']['active'] ? '' : ' light'));
							echo $this->Html->clean($item['TravelOrdersCounter']['layout']);
						echo '</td>';
					echo '</tr>';
				}
			}
		?>
		</tbody>
	</table>
</div>