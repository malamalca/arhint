<?php
	$sources = array(
			'c' => __d('lil_expenses', 'Company Account'),
			'p' => __d('lil_expenses', 'Private Account'),
			'o' => __d('lil_expenses', 'Other')
	);
	
	$start_link = $this->Html->link(
		isset($filter['month']) ? $this->LilDate->format($filter['start']) : $this->LilDate->format($filter['start']),
		array('action' => 'filter'),
		array('id' => 'lil-payments-link-date-start')
	);
	$end_link = $this->Html->link(
		isset($filter['month']) ? $this->LilDate->format($filter['end']) : $this->LilDate->format($filter['end']),
		array('action' => 'filter'),
		array('id' => 'lil-payments-link-date-end')
	);
	
	$source_link = $this->Html->link(
		!empty($filter['source']) ? $sources[$filter['source']] : __d('lil_expenses', 'all sources'),
		array('action' => 'filter'),
		array('class' => 'popup_link', 'id' => 'popup_sources')
	);
	
	$fromto = array('from' => __d('lil_expenses', 'From'), 'to' => __d('lil_expenses', 'To'));
	$fromto_link = $this->Html->link(
		!empty($filter['type']) ? $fromto[$filter['type']] : __d('lil_expenses', 'from+to'),
		array('action' => 'filter'),
		array('class' => 'popup_link', 'id' => 'popup_fromto')
	);
	
	$title = sprintf('<h1>%1$s</h1>',
		__d('lil_expenses', 'Payments %4$s %3$s from %1$s to %2$s', 
			$start_link, $end_link, $source_link, $fromto_link
		)
	);
	
	$popup_fromto = '<div class="popup_fromto popup ui-widget ui-widget-content ui-helper-clearfix ui-corner-all"><ul>';
	$popup_fromto .= sprintf('<li%2$s>%1$s</li>',
			$this->Html->link(__d('lil_expenses', 'From and To'), array(
				'?' => array('filter' => array_merge($filter, array(
					'type' => null,
				)))
			)),
			empty($filter['type']) ? ' class="active"' : ''
		);
	$popup_fromto .= sprintf('<li%2$s>%1$s</li>',
			$this->Html->link(__d('lil_expenses', 'From'), array(
				'?' => array('filter' => array_merge($filter, array(
					'type' => 'from',
				)))
			)),
			!empty($filter['type']) && $filter['type'] == 'from' ? ' class="active"' : ''
		);
	$popup_fromto .= sprintf('<li%2$s>%1$s</li>',
			$this->Html->link(__d('lil_expenses', 'To'), array(
				'?' => array('filter' => array_merge($filter, array(
					'type' => 'to',
				)))
			)),
			!empty($filter['type']) && $filter['type'] == 'to' ? ' class="active"' : ''
		);
	$popup_fromto .= '</ul></div>';
	
	$popup_sources = '<div class="popup_sources popup ui-widget ui-widget-content ui-helper-clearfix ui-corner-all"><ul>';
	$popup_sources .= sprintf('<li%2$s>%1$s</li><li><hr /></li>',
			$this->Html->link(__d('lil_expenses', 'All sources'), array(
				'?' => array('filter' => array_merge($filter, array(
					'source' => null,
				)))
			)),
			empty($filter['source']) ? ' class="active"' : ''
		);
	foreach ($sources as $src => $src_name) {
		$popup_sources .= sprintf('<li%2$s>%1$s</li>',
			$this->Html->link($src_name, array(
				'?' => array('filter' => array_merge($filter, array(
					'source' => $src,
				)))
			)),
			$src == $filter['source'] ? ' class="active"' : ''
		);
	}
	$popup_sources .= '</ul></div>';

	$admin_index = array(
		'title_for_layout' => strip_tags($title),
		'head_for_layout' => false,
		'actions' => array(
			'pre' => '<div id="lil-tmtr-index">',
			'post' => '',
			'lines' => array(
				$title,
				$popup_sources, $popup_fromto,
				sprintf('<input type="hidden" value="%s" id="lil-payments-input-date-start" />', $filter['start']),
				sprintf('<input type="hidden" value="%s" id="lil-payments-input-date-end" />', $filter['end']),
			)
		),
		'table' => array(
			'pre' => '' . PHP_EOL,
			'post' => '</div>',
			'element' => array(
				'parameters' => array(
					'id'          => 'IndexPayments',
					'class'       => 'index',
				),
			)
		)
	);
	
	$admin_index['table']['element']['head'] = array(
		'rows' => array(
			0 => array(
				'parameters' => array('class' => null),
				'columns' => array(
					'source' => array(
						'parameters' => array('class' => 'center'),
						'html' => __d('lil_expenses', 'Source'),
					),
					'date' => array(
						'parameters' => array('class' => 'center'),
						'html' => __d('lil_expenses', 'Date'),
					),
					'descript' => array(
						'html' => __d('lil_expenses', 'Description')
					),
					'payment' => array(
						'html' => __d('lil_expenses', 'Payment')
					),
					'saldo' => array(
						'html' => __d('lil_expenses', 'Saldo')
					),
				)
			)
		)
	);
	
	$total_positive = 0; $total_negative = 0; $saldo = 0;
	if (!empty($payments)) foreach ($payments as $p) {
		if ($p['Payment']['amount'] < 0) {
			$total_negative += $p['Payment']['amount'];
		} else {
			$total_positive += $p['Payment']['amount'];
		}
		$saldo += $p['Payment']['amount'];
		
		$admin_index['table']['element']['body']['rows'][] = array(
			'data'       => $p,
			'columns'    => array(
				'source' => array(
					'parameters' => array('class' => 'center'),
					'html' => $p['Payment']['source']
				),
				'date' => array(
					'parameters' => array('class' => 'center'),
					'html' => $this->LilDate->format($p['Payment']['dat_happened'])
				),
				'descript' => array(
					'html' => $this->Html->link($p['Payment']['descript'], array(
						'action' => 'edit',
						$p['Payment']['id']
					))
				),
				'payment' => array(
					'parameters' => ($p['Payment']['amount'] < 0) ? array('class' => 'negative') : array('class' => 'positive'),
					'html' => $this->LilFloat->format($p['Payment']['amount'])
				),
				'saldo' => array(
					'parameters' => ($p['Payment']['amount'] < 0) ? array('class' => 'negative') : array('class' => 'positive'),
					'html' => $this->LilFloat->format($saldo)
				),
			)
		);
		

	}
	
	$admin_index['table']['element']['foot'] = array(
		'rows' => array(
			0 => array(
				'parameters' => array(),
				'columns' => array(
					'title' => array(
						'parameters' => array('colspan' => '3'),
						'html' => __d('lil_expenses', 'Recapitulation')
					),
					'recap' => array(
						'parameters' => array('class' => 'right'),
						'html' => '<span>' . $this->LilFloat->money($total_positive) . '</span>' .
							'<span> / </span>' .
							'<span class="negative">' . $this->LilFloat->money($total_negative) . '</span>'
					),
					'space' => array(
						'parameters' => array('colspan' => '1'),
						'html' => '&nbsp;'
					),
				)
			)
		)
	);
	
	$admin_index = $this->callPluginHandlers('admin_index_payments', $admin_index);
	$this->Lil->index($admin_index);
?>

<script type="text/javascript">
	var tmtrUrlStartEnd = "<?php echo Router::url(array(
		'plugin'     => 'lil_expenses',
		'controller' => 'payments',
		'admin'      => true,
		'action'     => 'index',
		'?'          => array_merge($filter, array('filter' => array('start' => '[[start]]', 'end' => '[[end]]')))
	)); ?>";
	
	function filterByDate(dateText, startOrEnd) {
		var rx_start = new RegExp("(\\%5B){2}start(\\%5D){2}", "i");
		var rx_end = new RegExp("(\\%5B){2}end(\\%5D){2}", "i");
		//$.get(tmtrUrlStartEnd.replace(rx, dateText), function(data) {
		//	$('#lil-tmtr-index').replaceWith(data);
		//});
		if (startOrEnd == 'start') {
			rpl_start = dateText;
			rpl_end = $('#lil-payments-input-date-end').val();
		} else {
			rpl_start = $('#lil-payments-input-date-start').val();
			rpl_end = dateText;
		}
		document.location.href = tmtrUrlStartEnd.replace(rx_start, rpl_start).replace(rx_end, rpl_end);
	}

	$(document).ready(function() {
		// dates picker
		$("#lil-payments-input-date-start").datepicker({
			dateFormat: 'yy-mm-dd',
			onSelect: function(dateString, inst) {
				filterByDate(dateString, 'start');
			},
			beforeShow: function(input, inst) {
				var pos_start = $("#lil-payments-link-date-start").position();
				inst.dpDiv.css({'marginLeft': pos_start.left - 20, 'marginTop': '-15px'});
			}
		});
		$("#lil-payments-link-date-start").click(function() {
			$("#lil-payments-input-date-start").datepicker('show');
			return false;
		});
		
		$("#lil-payments-input-date-end").datepicker({
			dateFormat: 'yy-mm-dd',
			onSelect: function(dateString, inst) {
				filterByDate(dateString, 'end');
			},
			beforeShow: function(input, inst) {
				var pos_end = $("#lil-payments-link-date-end").position();
				inst.dpDiv.css({'marginLeft': pos_end.left - 20, 'marginTop': '-15px'});
			}
		});
		$("#lil-payments-link-date-end").click(function() {
			$("#lil-payments-input-date-end").datepicker('show');
			return false;
		});
		
		$('#table-index-payments').data(
			"settings",	{
				"aaSorting" : [[1, 'desc']],
				"aoColumnDefs": [
					{ "sType": "lil_date", "aTargets": [ 1 ] },
					{ "sType": "lil_float", "aTargets": [ 3, 4 ] },
					{ "bVisible": false, "aTargets": [ 5 ] }, // hide created field
				 ]
			}
		);
	});
</script>