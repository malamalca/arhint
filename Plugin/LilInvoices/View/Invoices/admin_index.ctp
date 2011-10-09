<?php
	$invoices_index = array(
		'title_for_layout' => $this->Html->clean($counter['InvoicesCounter']['title']),
		'menu' => array(
			'add' => array(
				'title' => __d('lil_invoices', 'Add', true),
				'visible' => $counter['InvoicesCounter']['active'],
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_invoices',
					'controller' => 'invoices',
					'action'     => 'add',
					'?'          => array('filter' => array('counter' => $counter['InvoicesCounter']['id']))
				)
			)
		),
		'table' => array(
			'element' => array(
				'parameters' => array(
					'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 
					'id' => 'AdminInvoicesIndex', 'class' => 'index'
				),
				'head' => array('rows' => array(0 => array('columns' => array(
					'cnt' => array(
						'parameters' => array('class' => 'center'),
						'html' => __d('lil_invoices', 'Cnt')
					),
					'no' => array(
						'html' => __d('lil_invoices', 'No')
					),
					'date' => array(
						'parameters' => array('class' => 'center'),
						'html' => __d('lil_invoices', 'Issued')
					),
					'title' => array(
						'html' => __d('lil_invoices', 'Title')
					),
					'client' => array(
						'html' => __d('lil_invoices', 'Client')
					),
					'total' => array(
						'parameters' => array('class' => 'right'),
						'html' => __d('lil_invoices', 'Total')
					),
				)))),
				'foot' => array('rows' => array(0 => array('columns' => array(
					0 => array(
						'parameters' => array('class' => 'right', 'colspan' => 5),
						'html' => __d('lil_invoices', 'Total Sum') . ': '
					),
					'total' => array(
						'parameters' => array('class' => 'right'),
						'html' => '&nbsp;'
					),
				))))
			)
		)
	);
	
	$total = 0;
	foreach ($data as $invoice) {
		$invoices_index['table']['element']['body']['rows'][]['columns'] = array(
			'cnt' => array(
				'parameters' => array('class' => 'center'),
				'html' => $this->Html->clean($invoice['Invoice']['counter'])
			),
			'no' => array(
				'html' => $this->Html->link($invoice['Invoice']['no'], array('action' => 'view', $invoice['Invoice']['id']))
			),
			'date' => array(
				'parameters' => array('class' => 'center nowrap'),
				'html' => $this->LilDate->format($invoice['Invoice']['dat_issue'])
			),
			'title' => array(
				'html' => $this->Html->clean($invoice['Invoice']['title']) . 
					// attachment
					($invoice['Invoice']['invoices_attachment_count'] == 0 ? '' : 
						' ' . $this->Html->image('/lil_invoices/img/attachment.png'))
			),
			'client' => array(
				'html' => $this->Text->truncate($this->Html->clean($invoice['Client']['title']), 30)
			),
			'total' => array(
				'parameters' => array('class' => 'right'),
				'html' => $this->LilFloat->money($invoice['Invoice']['total'], 2)
			),
		);
		$total += $invoice['Invoice']['total'];
	}
	$invoices_index['table']['element']['foot']['rows'][0]['columns']['total']['html'] = $this->LilFloat->money($total);
	
	$this->Lil->index($this->callPluginHandlers('lil_invoices_index_invoices', $invoices_index));
?>
<script type="text/javascript">
	$(document).ready(function() {
		$('#AdminInvoicesIndex').data(
			"settings",	{
				"aaSorting" : [[0, 'desc']],
				"aoColumnDefs": [
					{ "sType": "lil_date", "aTargets": [ 2 ] },
					{ "sType": "lil_float", "aTargets": [ 5 ] },
				]
			}
		);
	});
</script>