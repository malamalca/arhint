<?php
$expenses_index = array(
	'title_for_layout' => __d('lil_expenses', 'Expenses and Income'),
	'menu' => array(
		'add' => array(
			'title' => __d('lil_expenses', 'Add', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_expenses',
				'controller' => 'expenses',
				'action'     => 'add',
			)
		)
	),
	'table' => array(
		'element' => array(
			'parameters' => array(
				'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 
				'id' => 'AdminExpensesIndex', 'class' => 'index'
			),
			'head' => array('rows' => array(0 => array('columns' => array(
				'kind' => array(
					'parameters' => array('class' => 'center'),
					'html' => __d('lil_expenses', 'Kind')
				),
				'descript' => array(
					'html' => __d('lil_expenses', 'Description')
				),
				'project' => array(
					'html' => __d('lil_expenses', 'Project')
				),
				'user' => array(
					'html' => __d('lil_expenses', 'User')
				),
				'date' => array(
					'parameters' => array('class' => 'center'),
					'html' => __d('lil_expenses', 'Date')
				),
				'total' => array(
					'parameters' => array('class' => 'right'),
					'html' => __d('lil_expenses', 'Total')
				),
			)))),
			'foot' => array('rows' => array(0 => array('columns' => array(
				0 => array(
					'parameters' => array('class' => 'right', 'colspan' => 5),
					'html' => __d('lil_expenses', 'Total Sum') . ': '
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
foreach ($expenses as $exp) {
	$link = ''; $icon = "";
	switch ($exp['Expense']['model']) {
		case 'Invoice':
			$i_caption = '%1$s <span class="light">(%2$s)</span>';
			
			$link = sprintf($i_caption, 
				$link = $this->Html->link(
					$exp['Invoice']['no'],
					array(
						'admin'      => true,
						'plugin'     => 'lil_invoices',
						'controller' => 'invoices',
						'action'     => 'view',
						$exp['Expense']['foreign_id'],
					)
				),
				$exp['Invoice']['title']
			);
			$icon = $this->Html->image('invoice.png');
			
			break;
		default:
			$link = $this->Html->link(
				$exp['Expense']['title'],
				array(
					'admin'      => true,
					'plugin'     => 'lil_expenses',
					'controller' => 'expenses',
					'action'     => 'view',
					$exp['Expense']['id'],
				)
			);
			break;
	}

	$expenses_index['table']['element']['body']['rows'][]['columns'] = array(
		'kind' => array(
			'parameters' => array('class' => 'center'),
			'html' => $icon
		),
		'descript' => array(
			'html' => $link
		),
		'project' => array(
			'html' => $this->Text->truncate($this->Html->clean($exp['Project']['name']), 30)
		),
		'user' => array(
			'html' => $this->Text->truncate($this->Html->clean($exp['User']['title']), 30)
		),
		'date' => array(
			'parameters' => array('class' => 'center'),
			'html' => $this->LilDate->format($exp['Expense']['dat_happened'])
		),
		'total' => array(
			'parameters' => array('class' => sprintf('right%s', ($exp['Expense']['total'] < 0) ? ' negative' : '')),
			'html' => $this->LilFloat->format($exp['Expense']['total'])
		),
	);
	
	$total += $exp['Expense']['total'];
}

$expenses_index['table']['element']['foot']['rows'][0]['columns']['total'] = array(
	'parameters' => array('class' => sprintf('right%s', ($total < 0) ? ' negative' : '')),
	'html' => $this->LilFloat->money($total)
);

$this->Lil->index($this->callPluginHandlers('lil_expenses_index_expenses', $expenses_index));

?>
<script type="text/javascript">
	$(document).ready(function() {
		$('#AdminExpensesIndex').data(
			"settings",	{
				"aaSorting" : [[4, 'desc']],
				"aoColumnDefs": [
					{ "sType": "lil_date", "aTargets": [ 4 ] },
					{ "sType": "lil_float", "aTargets": [ 5 ] },
				 ]
			}
		);
	});
</script>