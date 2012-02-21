<?php
	$start_link = $this->Html->link(
		isset($filter['month']) ? $this->LilDate->format($filter['start']) : $this->LilDate->format($filter['start']),
		array('action' => 'filter'),
		array('id' => 'lil-invoices-link-date-start')
	);
	$end_link = $this->Html->link(
		isset($filter['month']) ? $this->LilDate->format($filter['end']) : $this->LilDate->format($filter['end']),
		array('action' => 'filter'),
		array('id' => 'lil-invoices-link-date-end')
	);
	$counter_link = $this->Html->link(
		$counter['InvoicesCounter']['title'],
		array('action' => 'filter'),
		array('id' => 'popup_counter', 'class' => 'popup_link')
	);
	
	$title = sprintf('<h1>%1$s</h1>',
		__d('lil_invoices', '%3$s from %1$s to %2$s', 
			$start_link, $end_link, $counter_link
		)
	);
	
	$popup_counters = array('items' => array());
	foreach ($counters as $cntr) {
		$popup_counters['items'][] = array(
			'title' => $cntr['InvoicesCounter']['title'],
			'url'   => array(
				'?' => array('filter' => array_merge($filter, array(
					'counter' => $cntr['InvoicesCounter']['id'],
				)))
			)
		);
	}
	
	$this->Lil->popup('counter', $popup_counters);
	$this->set('title_for_layout', $this->Html->clean($counter['InvoicesCounter']['title']));
	
	$invoices_index = array(
		'title_for_layout' => $this->Html->clean($counter['InvoicesCounter']['title']),
		'head_for_layout' => false,
		'actions' => array(
			'pre' => '<div>',
			'post' => '',
			'lines' => array(
				$title, 
				sprintf('<input type="hidden" value="%s" id="lil-invoices-input-date-start" />', $filter['start']),
				sprintf('<input type="hidden" value="%s" id="lil-invoices-input-date-end" />', $filter['end']),
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
	
	$this->Lil->index($this->callPluginHandlers('arhim_index_invoices', $invoices_index));
?>
<script type="text/javascript">
	var startEndUrl = "<?php echo Router::url(array(
		'plugin'     => 'arhim',
		'controller' => 'racuni',
		'action'     => 'index',
		'?'          => Set::merge(array('filter' => $filter), array('filter' => array('start' => '[[start]]', 'end' => '[[end]]')))
	)); ?>";
	
	function filterByDate(dateText, startOrEnd) {
		var rx_start = new RegExp("(\\%5B){2}start(\\%5D){2}", "i");
		var rx_end = new RegExp("(\\%5B){2}end(\\%5D){2}", "i");
		//$.get(tmtrUrlStartEnd.replace(rx, dateText), function(data) {
		//	$('#lil-tmtr-index').replaceWith(data);
		//});
		if (startOrEnd == 'start') {
			rpl_start = dateText;
			rpl_end = $('#lil-invoices-input-date-end').val();
		} else {
			rpl_start = $('#lil-invoices-input-date-start').val();
			rpl_end = dateText;
		}
		document.location.href = startEndUrl.replace(rx_start, rpl_start).replace(rx_end, rpl_end);
	}
	
	$(document).ready(function() {
		////////////////////////////////////////////////////////////////////////////////////////////
		$("#lil-invoices-input-date-start").datepicker({
			dateFormat: 'yy-mm-dd',
			onSelect: function(dateString, inst) {
				filterByDate(dateString, 'start');
			},
			beforeShow: function(input, inst) {
				var pos_start = $("#lil-invoices-link-date-start").position();
				inst.dpDiv.css({'marginLeft': pos_start.left - 20, 'marginTop': '-15px'});
			}
		});
		$("#lil-invoices-link-date-start").click(function() {
			$("#lil-invoices-input-date-start").datepicker('show');
			return false;
		});
		$("#lil-invoices-input-date-end").datepicker({
			dateFormat: 'yy-mm-dd',
			onSelect: function(dateString, inst) {
				filterByDate(dateString, 'end');
			},
			beforeShow: function(input, inst) {
				var pos_end = $("#lil-invoices-link-date-end").position();
				inst.dpDiv.css({'marginLeft': pos_end.left - 20, 'marginTop': '-15px'});
			}
		});
		$("#lil-invoices-link-date-end").click(function() {
			$("#lil-invoices-input-date-end").datepicker('show');
			return false;
		});
		
	});
</script>