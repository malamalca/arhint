<?php

use Cake\Routing\Router;
use Cake\Utility\Hash;

$pageTitle = __d('projects', 'Workhours');

// FILTER by user
$popupUsers = ['items' => [[
    'title' => __d('projects', 'All Users'),
    'active' => $filter->get('user') === null,
    'url' => ['?' => ['q' => $filter->buildQuery('user', null)]],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($users as $user) {
    $popupUsers['items'][] = [
        'title' => $user->name,
        'url' => ['?' => ['q' => $filter->buildQuery('user', (string)$user)]],
        'active' => $filter->check('user', $user->name),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupUsers = $this->Lil->popup('users', $popupUsers, true);

// FILTER by project
$popupProjects = ['items' => [[
    'title' => __d('projects', 'All Projects'),
    'active' => $filter->get('project') === null,
    'url' => ['?' => ['q' => $filter->buildQuery('project', null)]],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($projects as $project) {
    $popupProjects['items'][] = [
        'title' => h($project),
        'url' => ['?' => ['q' => $filter->buildQuery('project', (string)$project->title)]],
        'active' => $filter->check('project ', (string)$project->title),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupProjects = $this->Lil->popup('projects', $popupProjects, true);

// SORTING
$popupSort = ['items' => [
    [
        'title' => __d('projects', 'Created on'),
        'active' => $filter->checkLeft('sort', 'created') || !$filter->get('sort'),
        'url' => ['?' => ['q' => $filter->buildQuery(
            'sort',
            !$filter->checkRight('sort', '-desc') ? null :
                'created' . ($filter->checkRight('sort', '-desc') ? '-desc' : ''),
        )]],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => __d('projects', 'Last Updated'),
        'active' => $filter->checkLeft('sort', 'updated'),
        'url' => ['?' => ['q' => $filter->buildQuery(
            'sort',
            'updated' . ($filter->checkRight('sort', '-desc') ? '-desc' : ''),
        )]],
        'params' => ['class' => 'nowrap'],
    ],
    '-',
    [
        'title' => '<i class="material-icons">arrow_upward</i>' .
            (substr((string)$filter->get('sort'), 0, 8) == 'comments' ? __d('projects', 'Ascending') : __d('projects', 'Oldest')),
        'active' => !$filter->checkRight('sort', '-desc'),
        'url' => ['?' => ['q' => $filter->buildQuery(
            'sort',
            $filter->checkLeft('sort', 'created') ? null :
                (strstr((string)$filter->get('sort'), '-', true) ?: $filter->get('sort')),
        )]],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => '<i class="material-icons">arrow_downward</i>' .
            (substr((string)$filter->get('sort'), 0, 8) == 'comments' ? __d('projects', 'Descending') : __d('projects', 'Newest')),
        'active' => $filter->checkRight('sort', '-desc'),
        'url' => ['?' => ['q' => $filter->buildQuery(
            'sort',
            (explode('-', (string)$filter->get('sort'))[0] ?: 'created') . '-desc',
        )]],
        'params' => ['class' => 'nowrap'],
    ],
]];
$popupSort = $this->Lil->popup('sort', $popupSort, true);

$tableIndex = [
    'title_for_layout' => $pageTitle,
    'actions' => ['lines' => [$popupUsers, $popupProjects, $popupSort]],
    'menu' => [
        'add' => 1==1 ? null : [ // todo: add workhour
            'title' => __d('projects', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'Projects',
                'controller' => 'ProjectsWorkhours',
                'action' => 'edit',
                '?' => [
                    'project' => $filter['project'] ?: null,
                    'redirect' => Router::url(null, true),
                ],
            ],
            'params' => ['id' => 'add-workhour'],
        ],
    ],
    'pre' => '<div id="tasks-index">',
    'post' => '</div>',
    'panels' => [
        'search' => '<div id="tasks-search">' .
            sprintf('<form method="get" action="%s">', Router::url()) .
            sprintf('<input name="q" id="query" value="%s" />', htmlspecialchars($this->request->getQuery('q'))) .
            '<button type="submit" class="btn-small tonal" id="btn-search"><i class="material-icons">search</i></button>' .
            '</form>' .
            sprintf(
                '<a href="%2$s" class="btn-small filled" id="btn-add"><i class="material-icons">add</i>%1$s</a>',
                __d('projects', 'New Workhour'),
                $this->Url->build([
                    'action' => 'edit',
                    '?' => [
                        'redirect' => Router::url(null, true),
                    ],
                ]),
            ) .
            '</div>',
        'filter' => '<div id="tasks-filter"><ul>' .
            sprintf('<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-users">%s &#128899;</a></li>', __d('projects', 'User')) .
            sprintf('<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-projects">%s &#128899;</a></li>', __d('projects', 'Project')) .
            sprintf('<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-sort"><i class="material-icons">sort</i>%s &#128899;</a></li>', h(__d('projects', 'Newest'))) .
            '</ul>' .
            '<div class="checkbox"><input type="checkbox" id="select-all-tasks" /></div>' .
            '<div id="tasks-counters">' .
                $this->Html->link(
                    h(__d('projects', 'Open')) . sprintf('<span class="badge">%d</span></a>', $workhourCount['open']),
                    ['?' => ['q' => $filter->buildQuery('status', $filter->check('status', 'open') ? null : 'open')]],
                    ['class' => 'btn text' . ($filter->check('status', 'open') ? ' active' : ''), 'escape' => false],
                ) .
                $this->Html->link(
                    h(__d('projects', 'Closed')) . sprintf('<span class="badge">%d</span></a>', $workhourCount['closed']),
                    ['?' => ['q' => $filter->buildQuery('status', $filter->check('status', 'closed') ? null : 'closed')]],
                    ['class' => 'btn text' . ($filter->check('status', 'closed') ? ' active' : ''), 'escape' => false],
                ) .
            '</div>' .
            '</div>',
        'tasks' => [
            'params' => ['id' => 'tasks-list'],
            'lines' => [],
        ],
        'footer' => [
            'params' => ['id' => 'tasks-footer'],
            'lines' => [
                '<ul class="paginator">' .
                $this->Paginator->numbers(['first' => 1, 'last' => 1, 'modulus' => 3]) .
                '</ul>',
            ],
        ],
    ],
];

// errors
$errors = $filter->getErrors();
if (count($errors)) {
    $errors = Hash::extract($errors, '{*}.{*}');
    $errorsPanel = ['errors' =>
        '<div id="tasks-errors">' .
            '<div><i class="material-icons">warning</i>' .
            __dn('projects', 'Filter contains one issue:', 'Filter contains {0} issues:', count($errors), count($errors)) .
            '</div>' .
            '<ul>' .
            '<li>' . implode('</li><li>', $errors) . '</li>' .
            '</ul>' .
        '</div>',
    ];

    $this->Lil->insertIntoArray($tableIndex['panels'], $errorsPanel, ['after' => 'search']);
}

if ($projectsWorkhours->items()->isEmpty()) {
    $tableIndex['panels']['tasks']['lines'][] =
        '<div id="no-tasks-found">' .
        '<h4>' . __d('projects', 'No workhours found.') . '</h4>' .
        '<p>' . __d('projects', 'Try adjusting your search filters.') . '</p>' .
        '</div>';
} else {
    foreach ($projectsWorkhours as $workhour) {
        $canEdit = $this->getCurrentUser()->hasRole('admin') ||
            (empty($workhour->dat_confirmed) && ($this->getCurrentUser()->id == $workhour->user_id));

        $tableIndex['panels']['tasks']['lines'][] = $this->element('Projects.workhour_row', [
            'workhour' => $workhour,
            'canEdit' => $canEdit,
        ]);
    }
}

echo $this->Lil->panels($tableIndex, 'Projects.ProjectsWorkhours.index');
?>
<script type="text/javascript">
    var allTasksChecked = false;

    document.addEventListener("DOMContentLoaded", function() {
        const elems = document.querySelectorAll(".dropdown-trigger-costum");
        elems.forEach((dropdown) => {
            M.Dropdown.init(dropdown, {
                constrainWidth: false,
                coverTrigger: false,
            });
        });

        $("#menu-add-task, #btn-add").modalPopup({
            title: "<?= __d('projects', 'Add Task') ?>",
            onOpen: function(popup) {
                $("#title", popup).focus();
            }
        });

        $("#select-all-tasks").on("change", function(e) {
            $("div.task-row div.checkbox input").prop("checked", $(this).prop("checked"));
            allTasksChecked = true;
        });
        $("div.task-row div.checkbox input").on("change", function(e) {
            if (allTasksChecked) {
                $("#select-all-tasks").prop("checked", false);
            }
            allTasksChecked = false;
        });
    });
</script>