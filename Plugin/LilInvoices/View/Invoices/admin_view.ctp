<?php
	$invoice_view = array(
		'title' => sprintf(__d('lil_invoices', 'Invoice #%1$s <span class="light">(%2$s)</span>'),
			$this->Html->clean($data['Invoice']['no']),
			$this->Html->clean($data['Counter']['title'])
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
				'visible' => !empty($data['Counter']['layout']),
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
						'label' => __d('lil_invoices', 'Title'),
						'text'  => $this->Html->clean($data['Invoice']['title'])
					),
					
				)
			),
			'client' => array(
				'id' => 'invoice-client',
				'lines' => array(
					0 => array(
						'label' => __d('lil_invoices', 'Client'),
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
					
				)
			),
			'address' => empty($data['Client']['PrimaryAddress']) ? null : array(
				'id' => 'invoice-address',
				'lines' => array(
					0 => array(
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
						'label' => ($data['Client']['tax_status']) ? __d('lil_invoices', 'TAX payee no.') : __d('lil_invoices', 'TAX no.'),
						'text'  => $this->Html->clean($data['Client']['tax_no'])
					),
				)
			),
			'<br />',
			'details' => empty($data['Client']['PrimaryAddress']) ? null : array(
				'id' => 'invoice-details',
				'lines' => array(
					0 => array(
						'label' => __d('lil_invoices', 'Date of issue'),
						'text'  => $this->LilDate->format($data['Invoice']['dat_issue'])
					),
					1 => array(
						'label' => __d('lil_invoices', 'Service date'),
						'text'  => $this->LilDate->format($data['Invoice']['dat_service'])
					),
					2 => array(
						'label' => __d('lil_invoices', 'Expiration date'),
						'text'  => $this->LilDate->format($data['Invoice']['dat_expire'])
					),
				)
			),
			'<br />',
			'total' => array(
				'id' => 'invoice-total',
				'lines' => array(
					0 => array(
						'label' => __d('lil_invoices', 'Total'),
						'text'  => $this->LilFloat->money($data['Invoice']['total'])
					),
					
				)
			),
			'<br />',
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
		
		$items_body[] = array('columns' => array(
			'descript' => array(
				'contents' => $this->Html->clean($itm['descript'])
			),
			'qty' => array(
				'params' => array('class' => 'right'),
				'contents' => $this->LilFloat->format($itm['qty'])
			),
			'unit' => array(
				'contents' => $this->Html->clean($itm['unit'])
			),
			'price' => array(
				'params' => array('class' => 'right'),
				'contents' => $this->LilFloat->format($itm['price'])
			),
			'item_total' => array(
				'params' => array('class' => 'right'),
				'contents' => $this->LilFloat->format($item_total)
			),
			'tax' => array(
				'params' => array('class' => 'right'),
				'contents' => $this->LilFloat->format($itm['tax'])
			),
			'line_total' => array(
				'params' => array('class' => 'right'),
				'contents' => $this->LilFloat->format($line_total)
			),
		));
		
		
		$items_total += $item_total;
		

	}
	
	$items = array(
		'params' => array('cellspacing' => "0", 'cellpadding' => "0", 'id' => "invoice-analytics-table", 'class' => "index-static"),
		'parts' => array(
			'thead' => array(0 => array(
				'params' => array('class' => 'ui-toolbar ui-widget-header ui-corner-top'),
				'columns' => array(
			 		array('contents' => __d('lil_invoices', 'Description')),
					array('params' => array('class' => 'right'), 'contents' => __d('lil_invoices', 'Quantity')),
					array('contents' => __d('lil_invoices', 'Unit')),
					array('params' => array('class' => 'right'), 'contents' => __d('lil_invoices', 'Price per Unit')),
					array('params' => array('class' => 'right'), 'contents' => __d('lil_invoices', 'Unit Total')),
					array('params' => array('class' => 'right'), 'contents' => __d('lil_invoices', 'Tax [%]')),
					array('params' => array('class' => 'right'), 'contents' => __d('lil_invoices', 'Total with Tax')),
				)
			)),
			'tbody' => $items_body,
			'tfoot' => array(0 => array(
				'params' => array('class' => 'ui-toolbar ui-widget-header ui-corner-bl ui-corner-br'),
				'columns' => array(
			 		array(
					 	'params' => array('class' => 'right', 'colspan' => 4),
					 	'contents' => __d('lil_invoices', 'Grand Total')
					),
					array(
					 	'contents' => $this->LilFloat->money($items_total)
					),
					array(
					 	'contents' => '&nbsp;'
					),
					array(
					 	'params' => array('class' => 'right', 'id' => 'invoice-analytics-grand-total'),
					 	'contents' => $this->LilFloat->money($grand_total)
					),
				)
			))
		)
	);
	
	if ($data['Invoice']['kind'] == 'issued') {
		$invoice_view['panels']['items_title'] = sprintf('<h2>%s</h2>', __d('lil_invoices', 'Analytics'));
		$invoice_view['panels']['items']['id'] = 'invoice-view-items-table';
		$invoice_view['panels']['items']['table'] = $items;
		$invoice_view['panels']['items_title_after'] = '<br />';
	}
	
	App::uses('LilPluginRegistry', 'Lil.Lil'); $registry = LilPluginRegistry::getInstance();
	
	$invoice_view = $registry->callPluginHandlers($this, 'view_invoice', array('data' => $data, 'contents' => $invoice_view));
	
	$this->set('title_for_layout', $invoice_view['contents']['title']);
	$this->set('main_menu', $invoice_view['contents']['menu']);
	
	foreach ($invoice_view['contents']['panels'] as $panel) {
		if (is_array($panel)) {
			printf('<div%1$s class="invoice-view-panel">',
				isset($panel['id']) ? ' id="'.$panel['id'].'"' : ''
			);
			
			if (isset($panel['lines']) && is_array($panel['lines'])) {
				foreach ($panel['lines'] as $line) {
					if (is_array($line)) {
						if (isset($line['label'])) printf('<span class="label">%s</span>', $line['label']);
						if (isset($line['text'])) print($line['text']);
					} else {
						print($line);
					}
					print('<br />');
				}
			}
			
			if (isset($panel['table']) && is_array($panel['table'])) {
				$params = '';
				if (isset($panel['table']['params'])) {
					foreach ($panel['table']['params'] as $pp_k => $pp_v) {
						if (!empty($params)) $params .= ' ';
						$params .= $pp_k . '="' . $pp_v . '"';
					}
					if (!empty($params)) $params = ' ' . $params;
				}
				printf('<table%s>', $params);
				
				foreach ($panel['table']['parts'] as $p_k => $p_c) {
					printf('<%s>', $p_k);
					
					foreach($p_c as $row) {
						$params = '';
						if (isset($row['params'])) {
							foreach ($row['params'] as $pp_k => $pp_v) {
								if (!empty($params)) $params .= ' ';
								$params .= $pp_k . '="' . $pp_v . '"';
							}
							if (!empty($params)) $params = ' ' . $params;
						}
						printf('<tr%s>', !empty($params) ? $params : '');
						foreach ($row['columns'] as $col) {
							$col_type = 'td';
							if (($p_k == 'thead') || ($p_k == 'tfoot')) $col_type = 'th';
							
							$params = '';
							if (isset($col['params'])) {
								foreach ($col['params'] as $pp_k => $pp_v) {
									if (!empty($params)) $params .= ' ';
									$params .= $pp_k . '="' . $pp_v . '"';
								}
								if (!empty($params)) $params = ' ' . $params;
							}
							printf('<%1$s%2$s>%3$s</%1$s>', $col_type, !empty($params) ? $params : '', $col['contents']);
						}
						print ('</tr>');
					}
					
					printf('</%s>', $p_k);
				}
				print('</table>');
			}
			
			print('</div>');
		} else print($panel);
	}
	
?>

<?php
	if (!empty($data['Attachment'])) {
?>
<div id="invoice-attachments">
	<h2><?php echo __d('lil_invoices', 'Attachments'); ?></h2>
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
	<br />
</div>
<?php
	}
?>