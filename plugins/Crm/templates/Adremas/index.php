<?php

/** PROJECTS POPUP */
$activeProjectId = $this->getRequest()->getQuery('project');
$popupProjects = ['items' => [[
    'title' => __d('crm', 'All Projects'),
    'url' => ['?' => ['project' => null]],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($projects as $project) {
    $popupProjects['items'][] = [
        'title' => (string)$project,
        'url' => ['?' => ['project' => $project->id]],
        'active' => ($activeProjectId == $project->id),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupProjects = $this->Lil->popup('projects', $popupProjects, true);

/** PROJECTS FILTER LINK */
$projectsFilter = sprintf(
    '<button class="btn-small elevated" id="filter-projects" data-target="dropdown-projects">%1$s &#x25BC;</button>',
    $activeProjectId ? (string)$projects[$activeProjectId] : __d('crm', 'All Projects'),
);

/** INDEX TABLE */
$indexTable = [
    'title_for_layout' => __d('crm', 'Adremas'),
    'menu' => [
        'add' => [
            'title' => __d('crm', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'action' => 'edit',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdremasIndex',
        ],
        'head' => ['rows' => [['columns' => [
            'title' => $projectsFilter,
            'created' => $this->Paginator->sort('created', __d('crm', 'Created')),
            'actions' => [],
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'pagination' => [
                'params' => ['colspan' => 2, 'class' => 'left-align hide-on-small-only'],
                'html' => '<ul class="paginator">' . $this->Paginator->numbers([
                    'first' => '<<',
                    'last' => '>>',
                    'modulus' => 3]) . '</ul>',
            ],
            'actions' => '',
        ]]]],
    ],
];

foreach ($adremas as $adrema) {
    $indexTable['table']['body']['rows'][]['columns'] = [
        'title' => $this->Html->link($adrema->title, ['action' => 'view', $adrema->id]),
        'created' => (string)$adrema->created,
        'actions' => !$this->getCurrentUser()->hasRole('editor') ? '' : [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($adrema->id) . ' ' . $this->Lil->deleteLink($adrema->id),
        ],
    ];
}

echo $popupProjects;
echo $this->Lil->index($indexTable, 'Crm.Adremas.index');
echo '<script type="text/javascript">M.Dropdown.init(document.querySelectorAll("#filter-projects"), {coverTrigger: false, constrainWidth: false});</script>';