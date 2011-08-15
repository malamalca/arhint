<?php	
	echo '<div class="index2">';
	echo '<table cellspacing="0" cellpadding="0" id="invoice-analytics-table" class="index-static">';
	echo '<thead><tr class="ui-toolbar ui-widget-header ui-corner-top">';
	printf('<th class="actions">%s</th>', $this->Html->image('/lil_invoices/img/link.gif'));
	printf('<th class="left">%s</th>', __d('lil_invoices', 'Description'));
	printf('<th class="right">%s</th>', __d('lil_invoices', 'Quantity'));
	printf('<th class="left">%s</th>', __d('lil_invoices', 'Unit'));
-	printf('<th class="right">%s</th>', __d('lil_invoices', 'Price per Unit'));
-	printf('<th class="right">%s</th>', __d('lil_invoices', 'Unit Total'));
-	printf('<th class="right">%s</th>', __d('lil_invoices', 'Tax [%]'));
-	printf('<th class="right">%s</th>', __d('lil_invoices', 'Total with Tax'));
	print ('<th class="center">&nbsp;</th>');
	echo '</tr></thead>';
	
	$loop_items = array();
	if (empty($this->data['InvoicesItem']) || (!$loop_items = $this->data['InvoicesItem'])) {
		$loop_items = array(1);
	}
	
	echo '<tbody>';
	
	$grand_total = 0;
	$items_total = 0;
	foreach ($loop_items as $k => $item) {
		// echo empty line
		echo '<tr>';
		printf('<td class="center actions">%1$s</td>',
			$this->Html->image('/lil_invoices/img/link.gif', array(
				'style' => ($this->Form->value('InvoicesItem.' . $k . '.item_id')) ? '' : 'display: none;',
				'class' => 'image-item-check'
			))
		);
		printf('<td class="td-invoices-item-descript">%1$s %2$s</td>',
			$this->LilForm->input('InvoicesItem.' . $k . '.item_id', array('type'  => 'hidden')),
			$this->LilForm->input(
				'InvoicesItem.' . $k . '.descript',
				array(
					'label' => false,
					'size'  => 35,
					'class' => 'invoices-item-descript',
				)
			)
		);
		
		printf('<td>%s</td>', $this->LilForm->input(
			'InvoicesItem.' . $k . '.qty',
			array(
				'label' => false,
				'size'  => 4,
				'class' => 'right invoices-item-qty',
				'value' => $this->LilFloat->format($this->Form->value('InvoicesItem.' . $k . '.qty')),
				'error' => __d('lil_invoices', 'Blank')
			)
		));
		printf('<td>%s</td>', $this->LilForm->input(
			'InvoicesItem.' . $k . '.unit',
			array(
				'label' => false,
				'size'  => 5,
				'class' => 'invoices-item-unit',
				'error' => __d('lil_invoices', 'Blank')
			)
		));
		printf('<td class="right">%s</td>', $this->LilForm->input(
			'InvoicesItem.' . $k . '.price',
			array(
				'label' => false,
				'size'  => 12,
				'class' => 'right invoices-item-price',
				'value' => $this->LilFloat->format(
					$this->Form->value('InvoicesItem.' . $k . '.price')
				),
				'error' => __d('lil_invoices', 'Blank')
			)
		));
		
		echo '<td class="right invoices-item-total">';
			echo $this->LilForm->input('InvoicesItem.' . $k . '.id', array('type' => 'hidden'));
			
			$item_total = 
				$this->Form->value('InvoicesItem.' . $k . '.price') * 
				$this->Form->value('InvoicesItem.' . $k . '.qty');
			$items_total += $item_total;

			printf('<span>%s</span>', $this->LilFloat->format($item_total));
		echo '</td>';
		
		printf('<td class="right">%s</td>', $this->LilForm->input(
			'InvoicesItem.' . $k . '.tax',
			array(
				'label' => false,
				'size'  => 6,
				'class' => 'right invoices-item-tax',
				'value' => $this->LilFloat->format($this->Form->value('InvoicesItem.' . $k . '.tax'), 1),
				'error' => __d('lil_invoices', 'Blank')
			)
		));
		
		echo '<td class="right invoices-line-total">';
			$line_total = $item_total + $item_total *
				$this->Form->value('InvoicesItem.' . $k . '.tax') / 100;
			$grand_total += $line_total;	
			
			printf('<span>%s</span>', $this->LilFloat->format($line_total));
 		echo '</td>';
		
		printf('<td class="center">%s</td>', $this->Html->link(
			$this->Html->image('/lil_invoices/img/remove.gif', array('alt' => __d('lil_invoices', 'Remove Item'))),
			array(
				'admin'      => true,
				'plugin'     => false,
				'controller' => 'invoices_items',
				'action'     => 'delete',
				$this->Form->value('InvoicesItem.' . $k . '.id')
			),
			array(
				'escape'  => false,
				'onclick' => 'removeInvoiceAnalyticsRow(this); return false;',
				'class'   => 'invoices-item-remove',
			)
		)); 
		
		echo '</tr>';
	}
	echo '</tbody>';
	
	// table FOOTER with grand total and add new row link
	echo '<tfoot><tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">';
	printf(
		'<th colspan="2">%s</th>',
		$this->Html->link(
			__d('lil_invoices', 'Add new Item'),
			'javascript:void(0);',
			array('onclick' => 'addInvoiceAnalyticsRow()')
		)
	);
	printf('<th  colspan="3" class="right">%1$s:</th>', __d('lil_invoices', 'Grand Total'));
	printf('<th class="right" id="invoice-analytics-items-total">%s</th>', $this->LilFloat->format($items_total));
	printf('<th class="right">&nbsp;</th>');
 	printf('<th class="right" id="invoice-analytics-grand-total">%s</th>', $this->LilFloat->format($grand_total));
	printf('<th class="left">%s</th>', $this->LilFloat->moneySymbol());
	echo '</tr></tfoot>';
	
	echo '</table></div>';