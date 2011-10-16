<h1><?php
	echo strtr($data['InvoicesCounter']['layout_title'], array(
		'[[no]]' => $this->Html->clean($data['Invoice']['no'])
	));
?></h1>

<div id="invoice-details">
	<span class="label"><?php echo __d('lil_invoices', 'Date of issue'); ?>: </span>
	<?php echo $this->Time->i18nFormat($data['Invoice']['dat_issue']); ?>
	<br />
	
	<span class="label"><?php echo __d('lil_invoices', 'Predicted service date'); ?>: </span>
	<?php echo $this->Time->i18nFormat($data['Invoice']['dat_service']); ?>
	<br />
	
	<span class="label"><?php echo __d('lil_invoices', 'Expiration date'); ?>: </span>
	<?php echo $this->Time->i18nFormat($data['Invoice']['dat_expire']); ?>
	<br />
</div>

<table id="invoice-items" cellpadding="4">
	<tr>
		<th width="35%" id="th-descript"><?php echo __d('lil_invoices', 'Item description'); ?></th>
		<th width="15%" class="right"><?php echo __d('lil_invoices', 'Amount'); ?></th>
		<th width="10%" class="center"><?php echo __d('lil_invoices', 'Unit'); ?></th>
		<th width="20%" class="right"><?php echo __d('lil_invoices', 'PPU'); ?></th>
		<th width="20%" class="right"><?php echo __d('lil_invoices', 'Total price'); ?></th>
	</tr>
	<?php
		$total = 0;
		foreach ($data['InvoicesItem'] as $item) {
			echo '<tr>';
			echo '<td width="35%" id="td-descript">' . $this->Html->clean($item['descript']) . '</td>';
			echo '<td width="15%" class="right">' . $this->LilFloat->format($item['qty']) . '</td>';
			echo '<td width="10%" class="center">' . $this->Html->clean($item['unit']) . '</td>';
			echo '<td width="20%" class="right">' . $this->LilFloat->money($item['price'], 2) . '</td>';
			echo '<td width="20%" class="right">' . $this->LilFloat->money($item['price'] * $item['qty'], 2) . '</td>';
			echo '</tr>';
			
			$total += ($item['price'] * $item['qty']);
		}
	?>
	<tfoot>
	<tr>
		<th width="80%" class="right" colspan="4"><?php echo __d('lil_invoices', 'Grand total'); ?>:</th>
		<th width="20%" class="right"><?php echo $this->LilFloat->money($total, 2); ?></th>
	</tr>
	</tfoot>
</table>

<div id="invoice-foot"><?php echo $this->Lil->autop($data['Invoice']['descript']); ?></div>