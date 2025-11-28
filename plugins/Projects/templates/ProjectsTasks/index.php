<?php

$pageTitle = sprintf(
    '<div class="small">%1$s</div>',
    $this->Html->link((string)$projects[$filter['project']], [
        'controller' => 'Projects',
        'action' => 'view',
        $filter['project'],
        '?' => ['tab' => 'milestones'],
    ])
);

// FILTER by user
$popupUsers = ['items' => [[
    'title' => __d('projects', 'All Users'),
    'active' => false,
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['user' => null])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($users as $user) {
    $popupUsers['items'][] = [
        'title' => $user->name,
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['user' => $user->id])],
        'active' => ($activeUser == $user->id),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupUsers = $this->Lil->popup('users', $popupUsers, true);

// FILTER by milestone
$popupMilestones = ['items' => [[
    'title' => __d('projects', 'All Milestones'),
    'active' => false,
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['milestone' => null])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($milestones as $milestone) {
    $popupMilestones['items'][] = [
        'title' => (string)$milestone,
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['milestone' => $milestone->id])],
        'active' => ($filter['milestone'] == $milestone->id),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupMilestones = $this->Lil->popup('milestones', $popupMilestones, true);

$tableIndex = [
    'title_for_layout' => $pageTitle,
    'actions' => ['lines' => [$popupUsers, $popupMilestones]],
    'menu' => [
        'add' => empty($filter['project']) ? null : [
            'title' => __d('projects', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'Projects',
                'controller' => 'ProjectsTasks',
                'action' => 'edit',
                '?' => ['project' => $filter['project'] ?: null],
            ],
        ],
    ],
    'pre' => '<div id="tasks-index">',
    'post' => '</div>',
    'panels' => [
        'search' => '<div id="tasks-search">' .
            sprintf('<input name="q" id="query" value="%s" />', $this->request->getQuery('q')) .
            '<button class="btn btn-small tonal" id="btn-search"><i class="material-icons">search</i></button>' .
            '<button class="btn btn-small" id="btn-add"><i class="material-icons">add</i>New Task</button>' .
            '</div>',
        'filter' => '<div id="tasks-filter"><ul>' .
            '<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-users">Author</a></li>' .
            '<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-milestones">Milestone</a></li>' .
            '<li><a href="#" class="btn text"><i class="material-icons">sort</i>Newest &#128899;</a></li>' .
            '</ul>' .
            '<div id="">Open 1  Closed 1</div>' .
            '</div>',
        'tasks' => [
            'params' => ['id' => 'tasks-list'],
            'lines' => [],
        ],
    ],
];

foreach ($projectsTasks as $task) {
    $canEdit = $this->getCurrentUser()->hasRole('admin');

    $tableIndex['panels']['tasks']['lines'][] = $this->element('Projects.task_row', [
        'task' => $task,
        'canEdit' => $canEdit,
    ]);
}

echo $this->Lil->panels($tableIndex, 'Projects.ProjectsTasks.index');
?>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const elems = document.querySelectorAll('.dropdown-trigger-costum');
        elems.forEach((dropdown) => {
            M.Dropdown.init(dropdown, {
                constrainWidth: false,
                coverTrigger: false
            });
        });
    });
</script>