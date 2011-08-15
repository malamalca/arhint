<?php
	//$this->set('title_for_layout', sprintf(__d('lil_expenses', 'Payments for: %s'), $data['Expense']['title']));
	$this->set('title_for_layout', sprintf(__d('lil_expenses', 'Income or Expense')));
	$this->set('main_menu', array(
		'edit' => array(
			'title' => __d('lil_expenses', 'Edit', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_expenses',
				'controller' => 'expenses',
				'action'     => 'edit',
				$data['Expense']['id'],
			)
		),
		'delete' => array(
			'title' => __d('lil_expenses', 'Delete', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_expenses',
				'controller' => 'expenses',
				'action'     => 'delete',
				$data['Expense']['id'],
			),
			'params' => array(
				'confirm' => __d('lil_invoices', 'Are you sure you want to delete this invoice?')
			)
		)		
	));
?>
<div class="index2">
	<?php
		printf('<div class="view-panel"><span class="label">%1$s</span>%2$s</div>',
			__d('lil_expenses', 'Title'),
			$this->Html->clean($data['Expense']['title'])
		);
		
		printf('<div class="view-panel"><span class="label">%1$s</span>%2$s</div>',
			__d('lil_expenses', 'Date'),
			$this->LilDate->format($data['Expense']['dat_happened'])
		);
		
		printf('<div class="view-panel"><span class="label">%1$s</span>%2$s</div>',
			__d('lil_expenses', 'Total'),
			$this->LilFloat->money($data['Expense']['total'])
		);
		if (!empty($data['Expense']['project_id'])) {
			printf('<div class="view-panel"><span class="label">%1$s</span>%2$s</div>',
				__d('lil_expenses', 'Project'),
				$this->Html->clean($data['Project']['name'])
			);
		}
		if (!empty($data['Expense']['user_id'])) {
			printf('<div class="view-panel"><span class="label">%1$s</span>%2$s</div>',
				__d('lil_expenses', 'User'),
				$this->Html->clean($data['User']['title'])
			);
		}

	?>
	<br />

	<table cellspacing="0" cellpadding="0" class="index-static" id="view-expense-payments">
		<thead>
			<tr class="ui-toolbar ui-widget-header ui-corner-top">
				<th class="center"><?php echo __d('lil_expenses', 'Date'); ?></th>
				<th class="center"><?php echo __d('lil_expenses', 'Account'); ?></th>
				<th class="right"><?php echo __d('lil_expenses', 'Amount'); ?></th>
				<th class="center">&nbsp;</th>
				<th class="center">&nbsp;</th>
			</tr>
		</thead>
	<?php
		$sources = array('c' => __d('lil_expenses', 'company'), 'p' => __d('lil_expenses', 'private'), 'o' => __d('lil_expenses', 'other'));
		
		if (empty($data['Payment'])) {
			printf('<tr><td colspan="5" class="light">%s</td></tr>', __d('lil_expenses', 'No payments found.'));
		} else {
			foreach ($data['Payment'] as $p) {
				printf('<tr>');
				printf('<td class="center">%s</td>', $this->LilDate->format($p['dat_happened']));
				printf('<td class="center">%s</td>', $sources[$p['source']]);
				printf('<td class="right">%s</td>', $this->LilFloat->format($p['amount']));
				printf('<td class="center">%s</td>', $this->Lil->editLink(array('controller' => 'payments', 'action' => 'edit', $p['id'])));
				printf('<td class="center">%s</td>', $this->Lil->deleteLink(array('controller' => 'payments', 'action' => 'delete', $p['id'])));
				printf('</tr>');
			}
		}
		printf('<tfoot><tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">');
		printf(
			'<td colspan="5">%s</td>',
			$this->Html->link(
				__d('lil_expenses', 'Add new Payment'),
				array(
					'controller' => 'payments',
					'action'     => 'add',
					'expense'    => $data['Expense']['id']
				),
				array(
					'onclick' => 'addPaymentToInvoice(); return false;'
				)
			)
		);
		printf('</tr></tfoot>');
	?>
	</table>
	
	<?php
		echo $this->element('js' . DS . 'popup_payment');
	?>
</div>