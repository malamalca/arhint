<?php
$area_exepnses_table = array(
	'actions' => array(
		'pre' => sprintf('<h2>%s</h2>', __d('lil_expenses', 'Area\'s Expenses and Income'))
	),
	'table' => array(
		'element' => array(
			'parameters' => array('cellspacing' => 0, 'cellpadding' => 0, 'class' => 'index-static', 'width' => '600'),
			'head' => array(
				'rows' => array(0 => array('columns' => array(
					'date' => array('parameters' => array('class' => 'center'), 'html' => __d('lil_expenses', 'Date')),
					'title' => __d('lil_expenses', 'Title'),
					'total' => array('parameters' => array('class' => 'right'), 'html' => __d('lil_expenses', 'Total')),
				)))
			),
			'foot' => array(
				'rows' => array(0 => array('columns' => array(
					'date' => array(
						'parameters' => array('class' => 'right', 'colspan' => 2),
						'html' => __d('lil_expenses', 'Total') . ':'
					),
					'total' => array('parameters' => array('class' => 'right'), 'html' => '&nbsp;'),
				)))
			),
		)
	)
);

$total = 0;
foreach ($data as $exp) {
	$area_exepnses_table['table']['element']['body']['rows'][]['columns'] = array(
		'date' => array(
			'parameters' => array('class' => 'center'),
			'html' => $this->LilDate->format($exp['Expense']['dat_happened'])
		),
		'title' => $this->Html->link($exp['Expense']['title'], array(
			'plugin' => 'lil_invoices',
			'controller' => 'invoices',
			'admin' => true,
			'action' => 'view',
			$exp['Expense']['foreign_id'])
		),
		'total' => array(
			'parameters' => array('class' => 'right'),
			'html' => $this->LilFloat->money($exp['Expense']['total'])
		)
	);
	$total += $exp['Expense']['total'];
}
$area_exepnses_table['table']['element']['foot']['rows'][0]['columns']['total']['html'] = $this->LilFloat->money($total);

$this->Lil->index($area_exepnses_table);