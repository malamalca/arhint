<div id="invoice-details">
	<span class="label"><?php echo __d('lil_invoices', 'Date of issue'); ?>: </span>
	<?php echo $this->LilDate->format($data['Invoice']['dat_issue']); ?>
	<br />
	
	<span class="label"><?php echo __d('lil_invoices', 'Service date'); ?>: </span>
	<?php echo $this->LilDate->format($data['Invoice']['dat_service']); ?>
	<br />
	
	<span class="label"><?php echo __d('lil_invoices', 'Expiration date'); ?>: </span>
	<?php echo $this->LilDate->format($data['Invoice']['dat_expire']); ?>
	<br />
</div>
<br />
<div id="invoice-total" class="big strong">
	<span class="label"><?php echo __d('lil_invoices', 'Total'); ?>: </span>
	<?php echo $this->LilFloat->money($data['Invoice']['total']); ?>
</div>
<br />

<?php
	if (!empty($data['Invoice']['descript'])) {
		printf('<div id="invoice-foot">%1$s</div><br />', $this->Lil->autop($data['Invoice']['descript']));
	}
?>

<?php
	if (!empty($data['Attachment'])) {
?>
<div id="invoice-attachments">
	<span class="label"><?php __d('lil_invoices', 'Attachments'); ?>: </span>
	<?php
		foreach ($data['LilAttachment'] as $atch) {
			echo '<div>';
			printf('%1$s (%2$s)',
				$this->Html->link($atch['original'], array(
					'action' => 'attachment',
					$atch['id'],
					$atch['original']
				)),
				$this->Number->toReadableSize($atch['filesize'])
			);
			echo '</div>';
		}
	?>
</div>
<?php
	}
?>