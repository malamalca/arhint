<?php
	$title = '';
	$filter['start'] = '';
	$filter['end'] = '';
	
	$admin_index = array(
		'title_for_layout' => strip_tags($title),
		'head_for_layout' => false,
		'actions' => array(
			'pre' => '<div id="lil-tmtr-index">',
			'post' => '</div>',
			'lines' => array(
				$title,
				sprintf('<input type="hidden" value="%s" id="lil-tmtr-input-date-start" />', $filter['start']),
				sprintf('<input type="hidden" value="%s" id="lil-tmtr-input-date-end" />', $filter['end']),
			)
		),
		'table' => array(
			'pre' => '<div class="index">' . PHP_EOL,
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
	foreach ($payments as $p) {
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
	$(document).ready(function() {
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