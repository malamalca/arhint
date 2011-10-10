<?php
	$this->set('title_for_layout', $this->Html->clean($counter['TravelOrdersCounter']['title']));
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_travel_orders', 'Add', true),
			'visible' => $counter['TravelOrdersCounter']['active'],
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_travel_orders',
				'controller' => 'travel_orders',
				'action'     => 'add',
				'?'          => array('filter' => array('counter' => $counter['TravelOrdersCounter']['id']))
			)
		)
	));
?>
<div class="travel-orders index">
<?php
if (empty($data)) {
	echo __d('lil_travel_orders', 'No travel order found.');
} else {
?>
<table width="100%" cellpadding="0" cellspacing="0" class="index" id="table-index-travel-orders">
	<thead>
		<tr>
			<th class="right"><?php echo __d('lil_travel_orders', 'Cnt'); ?></th>
			<th class="left"><?php echo __d('lil_travel_orders', 'No'); ?></th>
			<th class="center"><?php echo __d('lil_travel_orders', 'Date'); ?></th>
			<th class="left"><?php echo __d('lil_travel_orders', 'Descript'); ?></th>
			<th class="left"><?php echo __d('lil_travel_orders', 'Employess'); ?></th>
			<th class="right"><?php echo __d('lil_travel_orders', 'Total'); ?></th>
		</tr>
	</thead>
<?php
	$i = 0;
	foreach ($data as $order) {
		$class = null;
?>
		<tr>
			<td class="right nowrap small"><?php echo $this->Html->clean($order['TravelOrder']['counter']); ?></td>
			<td class="left nowrap strong"><?php echo $this->Html->link($order['TravelOrder']['no'], array('action' => 'view', $order['TravelOrder']['id'])); ?></td>
			<td class="center nowrap"><?php echo $this->LilDate->Format($order['TravelOrder']['dat_order']); ?></td>
	        <td class="left small"><?php echo $this->Text->truncate($this->Html->clean($order['TravelOrder']['descript']), 30); ?></td>
	        <td class="left small"><?php echo $this->Text->truncate($this->Html->clean($order['Employee']['title']), 30); ?></td>
			<td class="right strong nowrap"><?php echo $this->LilFloat->money($order['TravelOrder']['total'], 2); ?></td>
		</tr>
<?php
	}
?>
	<tfoot>
		<tr>
			<td colspan="5" class="right strong"><?php echo __d('lil_travel_orders', 'Total Expense'); ?>:</td>
			<td class="right strong"><?php echo $this->LilFloat->money($total_sum); ?></td>
		</tr>
	</tfoot>
</table>
<?php
	}
?>
</div>