<style type="text/css">
	.center {
		text-align: center;
	}
	
	.right {
		text-align: right;
	}
	
	.label {
		font-weight: bold;
		color: #404040;
	}
	
	th {
		font-weight: bold;
		background-color: #c0c0c0;
	}
</style>
<div id="invoice-client">
<table>
	<tr>
		<td class="label"><?php echo __d('lil_invoices', 'Client'); ?>:</td>
	</tr>
	<tr>
		<td>
		<?php
			if (empty($data['Client']['id'])) {
				echo __d('lil_invoices', 'Business to Client');
			} else {
				echo $this->Html->clean($data['Client']['title']);
			}
		?>
		</td>
	</tr>
<?php
	if (!empty($data['Client']['PrimaryAddress'])) {
?>
	<tr>
		<td><?php echo $this->Html->clean($data['Client']['PrimaryAddress']['street']); ?></td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td>
		<?php
			echo $this->Html->clean(implode(
				' ', 
				array(
					$data['Client']['PrimaryAddress']['zip'],
					$data['Client']['PrimaryAddress']['city']
				)
			));
		?>
		</td>
	</tr>
	<tr>
		<td><?php echo $this->Html->clean($data['Client']['PrimaryAddress']['country']); ?></td>
	</tr>
<?php
	}
?>
</table>
</div>

<div id="invoice-client-taxno">
<?php
	if (!empty($data['Client']['tax_no'])) {
		printf('<span class="label">%1$s:</span> %2$s',
			($data['Client']['tax_status']) ? __d('lil_invoices', 'TAX payee no.') : __d('lil_invoices', 'TAX no.'),
			$this->Html->clean($data['Client']['tax_no'])
		);
	}
?>
</div>

<div id="invoice-body">
<h1><?php
	echo strtr($data['Counter']['layout_title'], array(
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

</div>
<?php
	$this->layout = false;
	$html = ob_get_contents();
	ob_clean();
	
	App::uses('PdfInvoice', 'LilInvoices.Lib');
	$a = new PdfInvoice($html, $data, $this->Lil->currentUser());
?>