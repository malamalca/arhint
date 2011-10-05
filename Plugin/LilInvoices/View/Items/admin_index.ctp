<?php
	$this->set('title_for_layout', __d('lil_invoices', 'Items'));
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_invoices', 'Add', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_invoices',
				'controller' => 'items',
				'action'     => 'add',
			)
		)
	));
?>
<div class="head">
	<h1><?php __d('lil_invoices', 'Items'); ?></h1>
</div>
<div class="index">
	<?php
		if (empty($data)) {
			echo __d('lil_invoices', 'No items found', true);
		} else {
	?>
	<table cellspacing="0" cellpadding="0" class="index">
		<thead>
			<tr>
				<th class="left"><?php echo __d('lil_invoices', 'Description'); ?></th>
				<th class="right"><?php echo __d('lil_invoices', 'Qty'); ?></th>
				<th class="left"><?php echo __d('lil_invoices', 'Unit'); ?></th>
				<th class="right"><?php echo __d('lil_invoices', 'Price'); ?></th>
				<th class="right"><?php echo __d('lil_invoices', 'Tax %'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
			foreach ($data as $item) {
				printf('<tr>');
				printf('<td class="left">%s</td>', $this->Html->link(
					$item['Item']['descript'], array('action' => 'edit', $item['Item']['id'])));
				printf('<td class="right">%s</td>', $this->LilFloat->format($item['Item']['qty'], 2));
				printf('<td class="left">%s</td>', $this->Html->clean($item['Item']['unit']));
				printf('<td class="right">%s</td>', $this->LilFloat->money($item['Item']['price'], 2));
				printf('<td class="right">%s</td>', $this->LilFloat->format($item['Item']['tax'], 1));
				printf('</tr>');
			}
		?>
		</tbody>
	</table>
	<?php
		}
	?>
</div>