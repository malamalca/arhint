<?php
	$this->set('title_for_layout', __d('lil_expenses', 'Payments'));
?>
<div class="index">
	<table cellspacing="0" cellpadding="0" id="table-index-payments" class="index" width="100%">
		<thead>
			<tr>
				<th class="center"><?php echo __d('lil_expenses', 'Source'); ?></th>
				<th class="center"><?php echo __d('lil_expenses', 'Date'); ?></th>
				<th><?php echo __d('lil_expenses', 'Description'); ?></th>
				<th><?php echo __d('lil_expenses', 'Payment'); ?></th>
				<th><?php echo __d('lil_expenses', 'Saldo'); ?></th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		<?php
			$total_positive = 0; $total_negative = 0;
			foreach ($payments as $p) {
				echo '<tr>';
					echo '<td class="center">' . $p['Payment']['source'] . '</td>';
					echo '<td class="center">' . $this->LilDate->format($p['Payment']['dat_happened']) . '</td>';
					printf('<td>%s</td>',
						$this->Html->link($p['Payment']['descript'], array(
							'action' => 'edit',
							$p['Payment']['id']
						))
					);
					printf('<td class="right %1$s">%2$s</td>',
						($p['Payment']['amount'] < 0) ? 'negative' : 'positive',
						$this->LilFloat->format($p['Payment']['amount'])
					);
					
					if ($p['Payment']['amount'] < 0) {
						$total_negative += $p['Payment']['amount'];
					} else {
						$total_positive += $p['Payment']['amount'];
					}
					
					$saldo += $p['Payment']['amount'];
					printf('<td class="right %1$s">%2$s</td>',
						($saldo < 0) ? 'negative' : 'positive',
						$this->LilFloat->format($saldo)
					);
					
					// this is invisible column for background by date sorting
					printf('<td class="center">%1$s</td>', $p['Payment']['created']);

				echo '</tr>' . PHP_EOL;
			}
		?>
		</tbody>
		<tfoot>
			<tr>
				<th colspan="3"><?php echo __d('lil_expenses', 'Recapitulation'); ?> :</th>
				<th class="right">
				<?php
					echo '<span>' . $this->LilFloat->money($total_positive) . '</span>';
					echo '<span> / </span>';
					echo '<span class="negative">' . $this->LilFloat->money($total_negative) . '</span>';
				?>
				&nbsp;</th>
				<th colspan="1">&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		$('#table-index-payments').data(
			"settings",	{
				"aaSorting" : [[1, 'desc']],
				"aoColumnDefs": [
					{ "sType": "lil_date", "aTargets": [ 1 ] },
					{ "sType": "lil_float", "aTargets": [ 3, 4 ] },
					{ "bVisible": false, "aTargets": [ 5 ] }, // hide created field
					//{ "iDataSort": 5, "aTargets": [ 1 ] } // sort by created instead of date
				 ]
			}
		);
	});
</script>