<?php

use Cake\Routing\Router;
use Cake\Utility\Hash;

$pageTitle = sprintf(
    '<div class="small">%1$s</div>',
    $this->Html->link((string)$project, [
        'controller' => 'Projects',
        'action' => 'view',
        $project->id,
        '?' => ['tab' => 'milestones'],
    ]),
);

// FILTER by user
$popupUsers = ['items' => [[
    'title' => __d('projects', 'All Users'),
    'active' => $filter->get('user') === null,
    'url' => [
        $project->id,
        '?' => ['q' => $filter->buildQuery('user', null)],
    ],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($users as $user) {
    $popupUsers['items'][] = [
        'title' => $user->name,
        'url' => [
            $project->id,
            '?' => ['q' => $filter->buildQuery('user', (string)$user)],
        ],
        'active' => $filter->check('user', $user->name),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupUsers = $this->Lil->popup('users', $popupUsers, true);

// FILTER by milestone
$popupMilestones = ['items' => [[
    'title' => __d('projects', 'All Milestones'),
    'active' => $filter->get('milestone') === null,
    'url' => [
        $project->id,
        '?' => ['q' => $filter->buildQuery('milestone', null)],
    ],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($milestones as $milestone) {
    $popupMilestones['items'][] = [
        'title' => (string)$milestone,
        'url' => [
            $project->id,
            '?' => ['q' => $filter->buildQuery('milestone', (string)$milestone)],
        ],
        'active' => $filter->check('milestone', (string)$milestone),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupMilestones = $this->Lil->popup('milestones', $popupMilestones, true);

// SORTING
$popupSort = ['items' => [
    [
        'title' => __d('projects', 'Created on'),
        'active' => $filter->checkLeft('sort', 'created') || !$filter->get('sort'),
        'url' => [$project->id, '?' => ['q' => $filter->buildQuery(
            'sort',
            !$filter->checkRight('sort', '-desc') ? null :
                'created' . ($filter->checkRight('sort', '-desc') ? '-desc' : ''),
        )]],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => __d('projects', 'Last Updated'),
        'active' => $filter->checkLeft('sort', 'updated'),
        'url' => [$project->id, '?' => ['q' => $filter->buildQuery(
            'sort',
            'updated' . ($filter->checkRight('sort', '-desc') ? '-desc' : ''),
        )]],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => __d('projects', 'Total Comments'),
        'active' => $filter->checkLeft('sort', 'comments'),
        'url' => [$project->id, '?' => ['q' => $filter->buildQuery(
            'sort',
            'comments' . ($filter->checkRight('sort', '-desc') ? '-desc' : ''),
        )]],
        'params' => ['class' => 'nowrap'],
    ],
    '-',
    [
        'title' => '<i class="material-icons">arrow_upward</i>' .
            (substr((string)$filter->get('sort'), 0, 8) == 'comments' ? __d('projects', 'Ascending') : __d('projects', 'Oldest')),
        'active' => !$filter->checkRight('sort', '-desc'),
        'url' => [$project->id, '?' => ['q' => $filter->buildQuery(
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
        'url' => [$project->id, '?' => ['q' => $filter->buildQuery(
            'sort',
            (explode('-', (string)$filter->get('sort'))[0] ?: 'created') . '-desc',
        )]],
        'params' => ['class' => 'nowrap'],
    ],
]];
$popupSort = $this->Lil->popup('sort', $popupSort, true);

$tableIndex = [
    'title_for_layout' => $pageTitle,
    'actions' => ['lines' => [$popupUsers, $popupMilestones, $popupSort]],
    'menu' => [
        'add' => [
            'title' => __d('projects', 'Add Task'),
            'visible' => true,
            'url' => [
                'plugin' => 'Projects',
                'controller' => 'ProjectsTasks',
                'action' => 'edit',
                '?' => [
                    'project' => $project->id,
                    'redirect' => Router::url(null, true),
                ],
            ],
            'params' => ['id' => 'menu-add-task'],
        ],
    ],
    'pre' => '<div id="tasks-index">',
    'post' => '</div>',
    'panels' => [
        'search' => '<div id="tasks-search">' .
            sprintf('<form method="get" action="%s">', Router::url()) .
            sprintf('<input name="q" id="query" value="%s" />', htmlspecialchars($this->request->getQuery('q'))) .
            '<button type="submit" class="btn btn-small tonal" id="btn-search"><i class="material-icons">search</i></button>' .
            '</form>' .
            sprintf(
                '<a href="%2$s" class="btn btn-small" id="btn-add"><i class="material-icons">add</i>%1$s</a>',
                __d('projects', 'New Task'),
                $this->Url->build([
                    'plugin' => 'Projects',
                    'controller' => 'ProjectsTasks',
                    'action' => 'edit',
                    '?' => [
                        'project' => $project->id,
                        'redirect' => Router::url(null, true),
                    ],
                ]),
            ) .
            '</div>',
        'filter' => '<div id="tasks-filter"><ul>' .
            sprintf('<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-users">%s &#128899;</a></li>', __d('projects', 'Author')) .
            sprintf('<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-milestones">%s &#128899;</a></li>', __d('projects', 'Milestone')) .
            sprintf('<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-sort"><i class="material-icons">sort</i>%s &#128899;</a></li>', h(__d('projects', 'Newest'))) .
            '</ul>' .
            '<div class="checkbox"><input type="checkbox" id="select-all-tasks" /></div>' .
            '<div id="tasks-counters">' .
                $this->Html->link(
                    h(__d('projects', 'Open')) . sprintf('<span class="badge">%d</span></a>', $tasksCount['open']),
                    [
                        $project->id,
                        '?' => ['q' => $filter->buildQuery('status', $filter->check('status', 'open') ? null : 'open')],
                    ],
                    [
                        'class' => 'btn text' . ($filter->check('status', 'open') ? ' active' : ''),
                        'escape' => false,
                    ],
                ) .
                $this->Html->link(
                    h(__d('projects', 'Closed')) . sprintf('<span class="badge">%d</span></a>', $tasksCount['closed']),
                    [
                        $project->id,
                        '?' => ['q' => $filter->buildQuery('status', $filter->check('status', 'closed') ? null : 'closed')],
                    ],
                    [
                        'class' => 'btn text' . ($filter->check('status', 'closed') ? ' active' : ''),
                        'escape' => false,
                    ],
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

if ($projectsTasks->isEmpty()) {
    $tableIndex['panels']['tasks']['lines'][] =
        '<div id="no-tasks-found">' .
        '<h4>' . __d('projects', 'No tasks found.') . '</h4>' .
        '<p>' . __d('projects', 'Try adjusting your search filters.') . '</p>' .
        '</div>';
} else {
    foreach ($projectsTasks as $task) {
        $canEdit = $this->getCurrentUser()->hasRole('admin');

        $tableIndex['panels']['tasks']['lines'][] = $this->element('Projects.task_row', [
            'task' => $task,
            'canEdit' => $canEdit,
        ]);
    }
}

echo $this->Lil->panels($tableIndex, 'Projects.ProjectsTasks.index');
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