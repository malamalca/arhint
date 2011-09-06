<?php
	$this->set('title_for_layout', __d('lil_invoices', 'Counters'));
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_invoices', 'Add', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_invoices',
				'controller' => 'counters',
				'action'     => 'add',
			)
		)
	));
?>
<div class="head">
	<h1><?php __d('lil_invoices', 'Counters'); ?></h1>
</div>
<div class="index">
	<table cellspacing="0" cellpadding="0" class="index">
		<thead>
			<tr>
				<th class="left"><?php echo __d('lil_invoices', 'Title'); ?></th>
				<th class="right"><?php echo __d('lil_invoices', 'InvoicesCounter Status'); ?></th>
				<th class="left"><?php echo __d('lil_invoices', 'Mask'); ?></th>
				<th class="left"><?php echo __d('lil_invoices', 'Template'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
			if (empty($data)) {
				echo '<tr><td colspan="6" class="light">' . __d('lil_invoices', 'No counters found', true) . '</td></tr>';
			} else {
				foreach ($data as $item) {
					echo '<tr>';
						printf('<td class="left%1$s">%2$s',
							$item['InvoicesCounter']['active'] ? '' : ' light',
							$this->Html->link($item['InvoicesCounter']['title'], array(
								'action' => 'edit',
								$item['InvoicesCounter']['id']
							))
						);
						echo '</td>';
						printf('<td class="right%s">', ($item['InvoicesCounter']['active'] ? '' : ' light'));
							echo $this->Html->clean($item['InvoicesCounter']['counter']);
						echo '</td>';
						printf('<td class="left%s">', ($item['InvoicesCounter']['active'] ? '' : ' light'));
							echo $this->Html->clean($item['InvoicesCounter']['mask']);
						echo '</td>';
						printf('<td class="left%s">', ($item['InvoicesCounter']['active'] ? '' : ' light'));
							echo $this->Html->clean($item['InvoicesCounter']['layout']);
						echo '</td>';
					echo '</tr>';
				}
			}
		?>
		</tbody>
	</table>
</div>