<style type="text/css">
	.label {
		font-weight: bold;
		color: #404040;
	}
</style>
<div class="head">
	<h1><?php printf(__d('lil_invoices', 'Invoice no: %s'), $this->Html->clean($data['Invoice']['no'])); ?></h1>
</div>

<div id="invoice-client" class="big">
	<span class="label"><?php echo __d('lil_invoices', 'Client'); ?>: </span>
	<?php echo $this->Html->clean($data['Client']['title']); ?>
</div>

<?php
	if (!empty($data['Client']['PrimaryAddress'])) {
?>
<div id="invoice-client-address">
	<span class="label"><?php echo __d('lil_invoices', 'Address'); ?>: </span>
	<?php
		echo implode(',', Set::filter(array(
			$this->Html->clean($data['Client']['PrimaryAddress']['street']),
			$this->Html->clean(implode(
				' ', 
				array(
					$data['Client']['PrimaryAddress']['zip'],
					$data['Client']['PrimaryAddress']['city']
				)
			)),
			$this->Html->clean($data['Client']['PrimaryAddress']['country'])
		)));
	?>
</div>
<?php
	}
?>
<br />
<?php
	if (!empty($data['Client']['tax_no'])) {
		printf('<span class="label">%1$s:</span> %2$s',
			($data['Client']['tax_status']) ? __d('lil_invoices', 'TAX payee no.') : __d('lil_invoices', 'TAX no.'),
			$this->Html->clean($data['Client']['tax_no'])
		);
	}
?>

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
		foreach ($data['Attachment'] as $atch) {
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