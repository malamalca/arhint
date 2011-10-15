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
		font-size: 0.8em;
		background-color: #c0c0c0;
	}
	
	td {
		font-size: 0.8em;
	}
</style>
<div id="invoice-client">
<table>
	<tr>
		<td class="label"><?php echo __d('lil_invoices', 'Client'); ?>:</td>
	</tr>
	<tr>
		<td><?php
			if (empty($data['Client']['id'])) {
				echo __d('lil_invoices', 'Business to Client');
			} else {
				echo $this->Html->clean($data['Client']['title']);
			}
		?></td>
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
	echo strtr($data['InvoicesCounter']['layout_title'], array(
		'[[no]]' => $this->Html->clean($data['Invoice']['no'])
	));
?></h1>

<div id="invoice-details">
	<span class="label"><?php echo __d('lil_invoices', 'Date of issue'); ?>: </span>
	<?php echo $this->Time->i18nFormat($data['Invoice']['dat_issue']); ?>
	<br />
	
	<span class="label"><?php echo __d('lil_invoices', 'Service date'); ?>: </span>
	<?php echo $this->Time->i18nFormat($data['Invoice']['dat_service']); ?>
	<br />
	
	<span class="label"><?php echo __d('lil_invoices', 'Expiration date'); ?>: </span>
	<?php echo $this->Time->i18nFormat($data['Invoice']['dat_expire']); ?>
	<br />
</div>

<table id="invoice-items" cellpadding="3">
	<tr>
		<td colspan="3"><?php echo __d('lil_invoices', 'Invoice Specification'); ?>:</td>
	</tr>
	<tr>
		<th width="25%" id="th-descript"><?php echo __d('lil_invoices', 'Item description'); ?></th>
		<th width="10%" class="right"><?php echo __d('lil_invoices', 'Qty'); ?></th>
		<th width="7%" class="center"><?php echo __d('lil_invoices', 'Unit'); ?></th>
		<th width="18%" class="right"><?php echo __d('lil_invoices', 'PPU'); ?></th>
		<th width="10%" class="right"><?php echo __d('lil_invoices', 'Tax [%]'); ?></th>
		<th width="15%" class="right"><?php echo __d('lil_invoices', 'Tax Amount'); ?></th>
		<th width="15%" class="right"><?php echo __d('lil_invoices', 'Total price'); ?></th>
	</tr>
	<?php
		$total_grand = $total_tax = $total_base = 0;
		$tax_spec = array();
		foreach ($data['InvoicesItem'] as $item) {
			$item_tax = round($item['price'] * $item['qty'] * $item['tax'] / 100, 2);
			echo '<tr>';
			echo '<td width="25%" id="td-descript">' . $this->Html->clean($item['descript']) . '</td>';
			echo '<td width="10%" class="right">' . $this->LilFloat->format($item['qty']) . '</td>';
			echo '<td width="7%" class="center">' . $this->Html->clean($item['unit']) . '</td>';
			echo '<td width="18%" class="right">' . $this->LilFloat->money($item['price'], 2) . '</td>';
			
			echo '<td width="10%" class="right">' . $this->LilFloat->format($item['tax'], 1) . '</td>';
			echo '<td width="15%" class="right">' . $this->LilFloat->money($item_tax, 2) . '</td>';
			
			echo '<td width="15%" class="right">' . $this->LilFloat->money($item['price'] * $item['qty'] + $item_tax, 2) . '</td>';
			echo '</tr>';
			
			if (!isset($tax_spec[$this->LilFloat->format($item['tax'], 1)])) $tax_spec[$this->LilFloat->format($item['tax'], 1)] = array('base' => 0, 'amount' => 0);
			$tax_spec[$this->LilFloat->format($item['tax'], 1)]['base'] += round($item['price'] * $item['qty'], 2);
			$tax_spec[$this->LilFloat->format($item['tax'], 1)]['amount'] += $item_tax;
			
			$total_tax += $item_tax;
			$total_base += ($item['price'] * $item['qty']);
			$total_grand += ($item_tax + $item['price'] * $item['qty']);
		}
	?>
	<tfoot>
	<tr>
		<th width="85%" class="right" colspan="6"><?php echo __d('lil_invoices', 'Tax Base'); ?>:</th>
		<th width="15%" class="right"><?php echo $this->LilFloat->money($total_base, 2); ?></th>
	</tr>
	<tr>
		<td width="50%" colspan="3"> </td>
		<th width="35%" class="right" colspan="3"><?php echo __d('lil_invoices', 'Tax Total'); ?>:</th>
		<th width="15%" class="right"><?php echo $this->LilFloat->money($total_tax, 2); ?></th>
	</tr>
	<tr>
		<td width="50%" colspan="4"> </td>
		<th width="35%" class="right" colspan="3"><?php echo __d('lil_invoices', 'Grand total'); ?>:</th>
		<th width="15%" class="right"><?php echo $this->LilFloat->money($total_grand, 2); ?></th>
	</tr>
	</tfoot>
</table>

<div id="invoice-foot">
	<table id="invoice-tax" cellpadding="3" width="50%">
		<tr>
			<td colspan="3"><?php echo __d('lil_invoices', 'Tax Specification'); ?>:</td>
		</tr>
		<tr>
			<th class="left" id="th-descript"><?php echo __d('lil_invoices', 'Tax %'); ?></th>
			<th class="right"><?php echo __d('lil_invoices', 'Tax Base'); ?></th>
			<th class="right"><?php echo __d('lil_invoices', 'Tax Amount'); ?></th>
		</tr>
		<?php
			$total_grand = $total_tax = $total_base = 0;
			foreach ($tax_spec as $tax_k => $tax) {
				echo '<tr>';
				echo '<td class="left">' . $tax_k . '</td>';
				echo '<td class="right">' . $this->LilFloat->money($tax['base'], 2) . '</td>';
				echo '<td class="right">' . $this->LilFloat->money($tax['amount'], 2) . '</td>';
				echo '</tr>';
				
				$total_tax += $tax['amount'];
			}
		?>
		<tfoot>
		<tr>
			<th class="right" colspan="2"><?php echo __d('lil_invoices', 'Tax Total'); ?>:</th>
			<th class="right"><?php echo $this->LilFloat->money($total_tax, 2); ?></th>
		</tr>
		</tfoot>
	</table>
	<?php echo $this->Lil->autop($data['Invoice']['descript']); ?>
</div>

</div>

<?php
	$this->layout = false;
	$html = ob_get_contents();
	ob_clean();
		
	App::uses('PdfInvoice', 'LilInvoices.Lib');
	$a = new PdfInvoice($html, $data, $this->Lil->currentUser());
?>