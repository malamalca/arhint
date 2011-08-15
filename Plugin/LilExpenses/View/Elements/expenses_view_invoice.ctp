<div id="invoice-payments">
	<h2><?php echo __d('lil_invoices', 'Payments'); ?></h2>

	<table class="index-static" id="view-invoice-payments" cellspacing="0" cellpadding="0">
		<thead>
			<tr class="ui-toolbar ui-widget-header ui-corner-top">
				<th class="center"><?php echo __d('lil_invoices', 'Date'); ?></th>
				<th class="center"><?php echo __d('lil_invoices', 'Account'); ?></th>
				<th class="right"><?php echo __d('lil_invoices', 'Amount'); ?></th>
				<th class="center">&nbsp;</th>
				<th class="center">&nbsp;</th>
			</tr>
		</thead>
	<?php
		App::uses('Sanitize', 'Utility');
		
		if (empty($payments)) {
			printf('<tr><td colspan="5" class="light">%s</td></tr>', __d('lil_invoices', 'No payments found.'));
		} else {
			$sources = array(
				'c' => __d('lil_invoices', 'company'),
				'p' => __d('lil_invoices', 'private'),
				'o' => __d('lil_invoices', 'other')
			);
			
			foreach ($payments as $p) {
				printf('<tr>');
				printf('<td class="center">%s</td>', $this->LilDate->format($p['Payment']['dat_happened']));
				printf('<td class="center">%s</td>', $sources[$p['Payment']['source']]);
				printf('<td class="right">%s</td>', $this->LilFloat->format($p['Payment']['amount']));
				printf('<td class="center">%s</td>', $this->Lil->editLink(
					array('plugin' => 'lil_expenses', 'controller' => 'payments', 'action' => 'edit', $p['Payment']['id']),
					array('class' => 'payment-edit')
				));
				printf('<td class="center">%s</td>', $this->Lil->deleteLink(array('plugin' => 'lil_expenses', 'controller' => 'payments', 'action' => 'delete', $p['Payment']['id'])));
				printf('</tr>');
			}
		}
		printf('<tfoot><tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">');
		printf(
			'<td colspan="5">%s</td>',
			$this->Html->link(
				__d('lil_invoices', 'Add new Payment'),
				array(
					'plugin'     => 'lil_expenses',
					'controller' => 'payments',
					'admin'      => true,
					'action'     => 'add',
					'expense'    => $expense_id
				),
				array('id' => 'payment-add')
			)
		);
		printf('</tr></tfoot>');
	?>
	</table>
</div>
<?php
	// javascripts
	$js_c = '$("%1$s").click(function(){popupPayment(\'%2$s\', $(this).attr("href")); return false;});';
	$this->Lil->jsReady(sprintf($js_c, '#payment-add', __d('lil_invoices', 'Add a new Payment')));
	$this->Lil->jsReady(sprintf($js_c, '.payment-edit', __d('lil_invoices', 'Edit Payment')));