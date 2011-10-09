<?php
	$invoice_view = array(
		'title_for_layout' => sprintf(__d('lil_invoices', 'Invoice #%1$s <span class="light">(%2$s :: #%3$s)</span>'),
			$this->Html->clean($data['Invoice']['no']),
			$this->Html->clean($data['InvoicesCounter']['title']),
			$this->Html->clean($data['InvoicesCounter']['counter'])
		),
		'menu' => array(
			'add' => array(
				'title' => __d('lil_invoices', 'Edit', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_invoices',
					'controller' => 'invoices',
					'action'     => 'edit',
					$data['Invoice']['id'],
					'?'          => array('filter' => array('counter' => $data['Invoice']['counter_id']))
				)
			),
			'delete' => array(
				'title' => __d('lil_invoices', 'Delete', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_invoices',
					'controller' => 'invoices',
					'action'     => 'delete',
					$data['Invoice']['id'],
				),
				'params' => array(
					'confirm' => __d('lil_invoices', 'Are you sure you want to delete this invoice?')
				)
			),
			'print' => array(
				'title' => __d('lil_invoices', 'Print', true),
				'visible' => !empty($data['InvoicesCounter']['layout']),
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_invoices',
					'controller' => 'invoices',
					'action'     => 'print',
					$data['Invoice']['id'],
				)
			)
		),
		'panels' => array(
			'title' => array(
				'id' => 'invoice-title',
				'lines' => array(
					0 => array(
						'label' => __d('lil_invoices', 'Title') . ':',
						'text'  => $this->Html->clean($data['Invoice']['title'])
					),
					
				)
			),
			'client' => array(
				'id' => 'invoice-client',
				'lines' => array(
					0 => array(
						'label' => __d('lil_invoices', 'Client') . ':',
						'text'  =>
							$this->Html->clean($data['Client']['title']) . ' ' .
							$this->Html->link($this->Html->image('link.gif'),
								array(
									'plugin'     => 'lil_crm',
									'controller' => 'contacts',
									'action'     => 'view',
									$data['Client']['id']
								),
								array('escape' => false)
							)
					),
					1 => empty($data['Client']['PrimaryAddress']) ? null : array(
						'text'  =>
							implode(',', Set::filter(array(
								$this->Html->clean($data['Client']['PrimaryAddress']['street']),
								$this->Html->clean(implode(
									' ', 
									array(
										$data['Client']['PrimaryAddress']['zip'],
										$data['Client']['PrimaryAddress']['city']
									)
								)),
								$this->Html->clean($data['Client']['PrimaryAddress']['country'])
							)))
					),
				)
			),
			'tax_no' => empty($data['Client']['PrimaryAddress']) ? null : array(
				'id' => 'invoice-address',
				'lines' => array(
					0 => array(
						'label' => (($data['Client']['tax_status']) ? __d('lil_invoices', 'TAX payee no.') : __d('lil_invoices', 'TAX no.')) . ':',
						'text'  => $this->Html->clean($data['Client']['tax_no'])
					),
				)
			),
			'details' => empty($data['Client']['PrimaryAddress']) ? null : array(
				'id' => 'invoice-details',
				'lines' => array(
					0 => array(
						'label' => __d('lil_invoices', 'Date of issue') . ':',
						'text'  => $this->LilDate->format($data['Invoice']['dat_issue'])
					),
					1 => array(
						'label' => __d('lil_invoices', 'Service date') . ':',
						'text'  => $this->LilDate->format($data['Invoice']['dat_service'])
					),
					2 => array(
						'label' => __d('lil_invoices', 'Expiration date') . ':',
						'text'  => $this->LilDate->format($data['Invoice']['dat_expire'])
					),
				)
			),
			'total' => array(
				'id' => 'invoice-total',
				'lines' => array(
					0 => array(
						'label' => __d('lil_invoices', 'Total') . ':',
						'text'  => $this->LilFloat->money($data['Invoice']['total'])
					),
					
				)
			),
			'foot' => array(
				'id' => 'invoice-foot',
				'lines' => array(
					0 => $this->Lil->autop($data['Invoice']['descript']),
					
				)
			),
		)
	);
	
	$items_body = array(); $items_total = 0; $grand_total = 0;
	foreach ($data['InvoicesItem'] as $itm) {
		$item_total = $itm['price'] * $itm['qty'];
		$line_total = $item_total + round($item_total * $itm['tax'] / 100, 2);
		$grand_total += $line_total;
		
		$items_body['rows'][] = array('columns' => array(
			'descript' => $this->Html->clean($itm['descript']),
			'qty' => array(
				'parameters' => array('class' => 'right'),
				'html' => $this->LilFloat->format($itm['qty'])
			),
			'unit' => array(
				'html' => $this->Html->clean($itm['unit'])
			),
			'price' => array(
				'parameters' => array('class' => 'right'),
				'html' => $this->LilFloat->format($itm['price'])
			),
			'item_total' => array(
				'parameters' => array('class' => 'right'),
				'html' => $this->LilFloat->format($item_total)
			),
			'tax' => array(
				'parameters' => array('class' => 'right'),
				'html' => $this->LilFloat->format($itm['tax'])
			),
			'line_total' => array(
				'parameters' => array('class' => 'right'),
				'html' => $this->LilFloat->format($line_total)
			),
		));
		$items_total += $item_total;
	}
	
	$items = array(
		'element' => array(
			'parameters' => array('cellspacing' => "0", 'cellpadding' => "0", 'id' => "invoice-analytics-table", 'class' => "index-static"),
			'head' => array(
				'rows' => array(
					0 => array(
						'columns' => array(
					 		__d('lil_invoices', 'Description'),
							array('parameters' => array('class' => 'right'), 'html' => __d('lil_invoices', 'Quantity')),
							array('html' => __d('lil_invoices', 'Unit')),
							array('parameters' => array('class' => 'right'), 'html' => __d('lil_invoices', 'Price per Unit')),
							array('parameters' => array('class' => 'right'), 'html' => __d('lil_invoices', 'Unit Total')),
							array('parameters' => array('class' => 'right'), 'html' => __d('lil_invoices', 'Tax [%]')),
							array('parameters' => array('class' => 'right'), 'html' => __d('lil_invoices', 'Total with Tax')),
						)
					)
				)
			),
			'body' => $items_body,
			'foot' => array(
				'rows' => array(
					0 => array(
						'columns' => array(
					 		array(
							 	'parameters' => array('class' => 'right', 'colspan' => 4),
							 	'html' => __d('lil_invoices', 'Grand Total')
							),
							array(
								'parameters' => array('class' => 'right'),
							 	'html' => $this->LilFloat->money($items_total)
							),
							array(
							 	'html' => '&nbsp;'
							),
							array(
							 	'parameters' => array('class' => 'right', 'id' => 'invoice-analytics-grand-total'),
							 	'html' => $this->LilFloat->money($grand_total)
							),
						)
					)
				)
			)
		)
	);
	
	if ($data['InvoicesCounter']['kind'] == 'issued') {
		$invoice_view['panels']['items_title'] = sprintf('<h2>%s</h2>', __d('lil_invoices', 'Analytics'));
		$invoice_view['panels']['items']['id'] = 'invoice-view-items-table';
		$invoice_view['panels']['items']['table'] = $items;
	}
	
	$invoice_view = $this->callPluginHandlers('view_invoice', array('data' => $data, 'contents' => $invoice_view));
	$this->Lil->panels($invoice_view['contents']);
?>

<?php
	if (!empty($data['InvoicesAttachment'])) {
?>
<div id="invoice-attachments">
	<h2><?php echo __d('lil_invoices', 'Attachments'); ?></h2>
	<?php
		foreach ($data['InvoicesAttachment'] as $atch) {
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
	<br />
</div>
<?php
	}
?>