<?php

// FILTER by active
$activeLink = $this->Html->link(
    empty($filter['inactive']) ? __d('lil_projects', 'Active') : __d('lil_projects', 'All'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-active', 'data-target' => 'dropdown-active']
);
$popupActive = ['items' => [
    ['title' => __d('lil_projects', 'Active'), 'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['inactive' => null])]],
    ['title' => __d('lil_projects', 'All'), 'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['inactive' => 1])]],
]];

$popupActive = $this->Lil->popup('active', $popupActive, true);

$filterTitle = __d('lil_projects', '{0} Projects', [$activeLink]);

$index = [
    'title_for_layout' => $filterTitle,
    'menu' => [
        'add' => [
            'title' => __d('lil_projects', 'Add'),
            'visible' => true,
            'url' => ['action' => 'add']
        ],
    ],
    'actions' => ['lines' => [$popupActive]],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0,
            'class' => 'index-static', 'id' => 'ProjectsIndex'
        ],
        'head' => ['rows' => [['columns' => [
            'image' => ['params' => ['class' => 'center hide-on-small-only'], 'html' => '&nbsp;'],
            'title' => __d('lil_projects', 'Title'),
            'status' => ['params' => ['class' => 'center hide-on-small-only'], 'html' => __d('lil_projects', 'Status')],
            'actions' => '',
            'log' => ['params' => ['class' => 'left'], 'html' => __d('lil_projects', 'Last Log')],
        ]]]],
    ]
];

foreach ($projects as $project) {
    $projectStatus = $projectsStatuses[$project->status_id] ?? '';

    $lastLogDescript = '';
    if (!empty($project->last_log)) {
        $lastLogDescript = sprintf(
            '<span class="small">%2$s, %3$s</span><div>%1$s</div>',
            h($project->last_log->descript),
            $project->last_log->created,
            $project->last_log->user->name,
        );
    }

    $index['table']['body']['rows'][]['columns'] = [
        'image' => [
            'params' => ['class' => 'center hide-on-small-only'],
            'html' => empty($project->ico) ? '' : $this->Html->image(['action' => 'picture', $project->id, 'thumb'], ['style' => 'height: 50px;', 'class' => 'project-avatar'])
        ],
        'title' =>
        $this->Html->link($project->no, ['action' => 'view', $project->id], ['class' => 'small']) . '<br />' .
            $this->Html->link($project->title, ['action' => 'view', $project->id], ['class' => 'big']),
        'status' => [
            'params' => ['class' => 'center hide-on-small-only'],
            'html' => $projectStatus ? ('<div class="chip z-depth-1">' . h($projectStatus) . '</div>') : '',
        ],
        'actions' => $this->Html->link(
            '<i class="material-icons chevron">chat_bubble_outline</i>',
            ['controller' => 'ProjectsLogs', 'action' => 'add', '?' => ['project' => $project->id]],
            ['escape' => false, 'class' => 'btn btn-small btn-floating add-projects-log']
        ),
        'log' => $lastLogDescript,
    ];
}

echo $this->Lil->index($index, 'LilProjects.Projects.index');
?>
<script type="text/javascript">

    $(document).ready(function() {
        $(".add-projects-log").each(function() {
            $(this).modalPopup({
                title: "<?= __d('lil_projects', 'Add Log') ?>",
                onOpen: function(popup) { $("#projects-logs-descript", popup).focus(); }
            });
        });
    });
</script>
