<?php
	$this->set('title_for_layout', __d('lil_expenses', 'Expenses and Income'));
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_expenses', 'Add', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_expenses',
				'controller' => 'expenses',
				'action'     => 'add',
			)
		)
	));
?>
<div class="index">
	<table width="100%" cellspacing="0" cellpadding="0" class="index" id="table-index-expenses">
		<thead>
			<tr>
				<th class="center"><?php echo __d('lil_expenses', 'Kind'); ?></th>
				<th class="left"><?php echo __d('lil_expenses', 'Description'); ?></th>
				<th class="left"><?php echo __d('lil_expenses', 'Project'); ?></th>
				<th class="left"><?php echo __d('lil_expenses', 'User'); ?></th>
				<th class="center"><?php echo __d('lil_expenses', 'Date'); ?></th>
				<th class="right"><?php echo __d('lil_expenses', 'Amount'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
			$sum = 0;
			if (empty($expenses)) {
				echo '<tr><td colspan="8" class="light">' . __d('lil_expenses', 'No expenses found') . '</td></tr>';
			} else {
				foreach ($expenses as $exp) {
					$link = ''; $icon = "";
					switch ($exp['Expense']['model']) {
						case 'Invoice':
							$i_caption = '%1$s <span class="light">(%2$s)</span>';
							
							$link = sprintf($i_caption, 
								$link = $this->Html->link(
									$exp['Invoice']['no'],
									array(
										'admin'      => true,
										'plugin'     => 'lil_invoices',
										'controller' => 'invoices',
										'action'     => 'view',
										$exp['Expense']['foreign_id'],
									)
								),
								$exp['Invoice']['title']
							);
							$icon = $this->Html->image('invoice.png');
							
							break;
						default:
							$link = $this->Html->link(
								$exp['Expense']['title'],
								array(
									'admin'      => true,
									'plugin'     => 'lil_expenses',
									'controller' => 'expenses',
									'action'     => 'view',
									$exp['Expense']['id'],
								)
							);
							break;
					}
					
					echo '<tr>';
						printf('<td class="center min-width">%s</td>', $icon);
						printf('<td>%s</td>', $link);
						echo '<td>' . $this->Text->truncate($this->Html->clean($exp['Project']['name']), 30) . '</td>';
						echo '<td>' . $this->Text->truncate($this->Html->clean($exp['User']['title']), 30) . '</td>';
						echo '<td class="center">' . $this->LilDate->format($exp['Expense']['dat_happened']) . '</td>';
						printf('<td class="right%1$s">%2$s',
							($exp['Expense']['total'] < 0) ? ' negative' : '',
							$this->LilFloat->format($exp['Expense']['total'])
						);
					echo '</tr>';
					
					$sum += $exp['Expense']['total'];
				}
			}
		?>
		</tbody>
		<tfoot>
			<tr>
				<th colspan="4" class="right big"><?php echo __d('lil_expenses', 'Total'); ?> :&nbsp;</th>
				<th class="right big<?php if ($total_sum < 0) echo ' negative'; ?>">
				<?php echo $this->LilFloat->money($total_sum); ?>
				</th>
			</tr>
		</tfoot>
	</table>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		$('#table-index-expenses').data(
			"settings",	{
				"aaSorting" : [[4, 'desc']],
				"aoColumnDefs": [
					{ "sType": "lil_date", "aTargets": [ 4 ] },
					{ "sType": "lil_float", "aTargets": [ 5 ] },
				 ]
			}
		);
	});
</script>