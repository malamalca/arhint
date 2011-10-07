<?php if (!empty($payments)) { ?>
<div class="lil_panel_full">
<h1><?php echo __d('lil_expenses', 'Current Month\'s CashFlow'); ?></h1>
<table class="index-static" width="100%" cellspacing="0" cellpadding="0">
	<thead>
		<tr class="ui-toolbar ui-widget-header ui-corner-tl ui-corner-tr">
			<th class="center"><?php echo __d('lil_expenses', 'Account'); ?></th>
			<th class="center"><?php echo __d('lil_expenses', 'Date'); ?></th>
			<th class="left"><?php echo __d('lil_expenses', 'Descript'); ?></th>
			<th class="right"><?php echo __d('lil_expenses', 'Payment'); ?></th>
			<th class="right"><?php echo __d('lil_expenses', 'Saldo'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
		if (empty($payments)) {
			echo '<tr><td colspan="6" class="light">' . __d('lil_expenses', 'No payments found') . '</td></tr>';
		} else {
			$total_positive = 0; $total_negative = 0;
			foreach ($payments as $p) {
				echo '<tr>';
					
					echo '<td class="center">' . $accounts[$p['Payment']['account_id']] . '</td>';
					echo '<td class="center">' . $this->LilDate->format($p['Payment']['dat_happened']) . '</td>';
					echo '<td class="left">' . $this->Html->clean($p['Payment']['descript']) . '</td>';

					printf('<td class="right%1$s">%2$s',
						($p['Payment']['amount'] < 0) ? ' negative' : '',
						$this->LilFloat->format($p['Payment']['amount'])
					);
					if ($p['Payment']['amount'] < 0) $total_negative += $p['Payment']['amount']; else $total_positive += $p['Payment']['amount']; 
					
					$saldo += $p['Payment']['amount'];
					printf('<td class="right%1$s">%2$s',
						($saldo < 0) ? ' negative' : '',
						$this->LilFloat->format($saldo)
					);
				echo '</tr>';
			}
		}
	?>
	</tbody>
	<tfoot>
		<tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">
			<th colspan="3" class="right"><?php echo __d('lil_expenses', 'Recapitulation'); ?> :</th>
			<th class="right nowrap"><?php echo '<span>' . $this->LilFloat->format($total_positive) . '</span><span> / </span><span class="negative">' . $this->LilFloat->format($total_negative) . '</span>'; ?></th>
			<th colspan="2">&nbsp;</th>
		</tr>
	</tfoot>
</table>
<br /><br />
</div>
<?php } ?>

<div class="lil_panel_full">
	<h1><?php echo __d('lil_expenses', 'Recent Expenses'); ?></h1>
	<table class="index-static" width="100%" cellpadding="0" cellspacing="0">
		<thead>
			<tr class="ui-toolbar ui-widget-header ui-corner-tl ui-corner-tr">
				<th class="left">&nbsp;</th>
				<th class="left"><?php echo __d('lil_expenses', 'Description'); ?></th>
				<th class="left"><?php echo __d('lil_expenses', 'Project'); ?></th>
				<th class="left"><?php echo __d('lil_expenses', 'User'); ?></th>
				<th class="center"><?php echo __d('lil_expenses', 'Date'); ?></th>
				<th class="right"><?php echo __d('lil_expenses', 'Total'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
			$sum = 0;
			if (empty($expenses)) {
				echo '<tr><td colspan="6" class="light">' . __d('lil_expenses', 'No expenses found') . '</td></tr>';
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
									'plugin'     => null,
									'controller' => 'expenses',
									'action'     => 'view',
									$exp['Expense']['id'],
								)
							);
					}
					
					echo '<tr>';
						echo '<td class="center">' . $icon . '</td>';
						echo '<td>' . $link . '</td>';
						echo '<td>' . $this->Text->truncate($this->Html->clean($exp['Project']['name']), 30) . '</td>';
						echo '<td>' . $this->Text->truncate($this->Html->clean($exp['User']['title']), 30) . '</td>';
						echo '<td class="center">' . $this->LilDate->format($exp['Expense']['dat_happened']) . '</td>';
						printf('<td class="right%1$s">%2$s',
							($exp['Expense']['total'] < 0) ? ' negative' : '',
							$this->LilFloat->money($exp['Expense']['total'], 2)
						);
					echo '</tr>';
					
					$sum += $exp['Expense']['total'];
				}
			}
 		?>
		</tbody>
		<tfoot>
			<tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">
				<th colspan="5" class="right"><?php echo __d('lil_expenses', 'Total'); ?> :</th>
				<th class="right"><?php echo $this->LilFloat->money($sum, 2); ?></th>
			</tr>
		</tfoot>
	</table>
</div>