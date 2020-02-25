<?php

$index = [
	'title_for_layout' => __d('lil_projects', 'Projects'),
	'menu' => [
		'add' => [
			'title' => __d('lil_projects', 'Add'),
			'visible' => true,
			'url' => ['action' => 'add']
		],
	],
	'table' => [
		'parameters' => [
			'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0,
			'class' => 'index-static'
		],
		'head' => ['rows' => [['columns' => [
			'no' => __d('lil_projects', 'No.'),
            'title' => __d('lil_projects', 'Title'),
            'image' => ['params' => ['class' => 'center'], 'html' => __d('lil_projects', 'Image')],
			'expenses' => __d('lil_projects', 'Expenses'),
			'workhours' => ['params' => ['class' => 'center'], 'html' => __d('lil_projects', 'Work Hours/Cost')],
            'total' => __d('lil_projects', 'Total'),
            'actions' => ''
		]]]],
		'foot' => ['rows' => [['columns' => [
			'counter' => ['html' => '&nbsp;', 'params' => ['colspan' => 7]]
		]]]],
	]
];

function formatDuration($duration)
{
	$hours = floor($duration / 3600);
	$minutes = $duration % 60;
	return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
}

foreach ($projects as $project) {
    $index['table']['body']['rows'][]['columns'] = [
		'no' => $this->Html->link($project->no, ['action' => 'view', $project->id]),
        'title' => h($project->title),
        'image' =>  [
            'params' => ['class' => 'center'],
            'html' => empty($project->ico) ? '' : $this->Html->image(['action' => 'picture', $project->id, 'thumb'], ['style' => 'height: 50px;'])
        ],
		'expenses' => '',
		'workhours' => ['params' => ['class' => 'center'],
			'html' => isset($workhours[$project->id]) ? formatDuration($workhours[$project->id]) : '-'],
        'total' => '',
        'actions' => $this->Lil->editLink($project->id) . ' ' . $this->Lil->deleteLink($project->id)
    ];
}

echo $this->Lil->index($index, 'LilProjects.Projects.index');
