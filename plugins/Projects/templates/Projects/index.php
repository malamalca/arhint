<?php
use Cake\Routing\Router;
use Projects\Lib\ProjectsFuncs;

// FILTER by active
$activeLink = $this->Html->link(
    empty($filter['inactive']) ? __d('projects', 'Active') : __d('projects', 'All'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-active', 'data-target' => 'dropdown-active']
);
$popupActive = ['items' => [
    ['title' => __d('projects', 'Active'), 'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['inactive' => null])]],
    ['title' => __d('projects', 'All'), 'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['inactive' => 1])]],
]];
$popupActive = $this->Lil->popup('active', $popupActive, true);

// FILTER by status
$activeStatus = $this->getRequest()->getQuery('status');
$statusLink = $this->Html->link(
    $projectsStatuses[$activeStatus] ?? __d('projects', 'All Statuses'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-status', 'data-target' => 'dropdown-status']
);
$popupStatus = ['items' => [[
    'title' => __d('projects', 'All Statuses'),
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['status' => null])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($projectsStatuses as $statusId => $statusTitle) {
    $popupStatus['items'][] = [
        'title' => $statusTitle,
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['status' => $statusId])],
        'active' => ($activeStatus == $statusId),
        'params' => ['class' => 'nowrap'],
    ];
}
$popupStatus = $this->Lil->popup('status', $popupStatus, true);

// PAGE title
$filterTitle = __d('projects', '{0} Projects for {1}', [$activeLink, $statusLink]);
$index = [
    'title_for_layout' => $filterTitle,
    'menu' => [
        'add' => [
            'title' => __d('projects', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('admin'),
            'url' => [
                'action' => 'edit',
            ],
        ],
    ],
    'actions' => ['lines' => [$popupActive, $popupStatus]],
    'table' => [
        'pre' => $this->Arhint->searchPanel($this->getRequest()->getQuery('search', '')),
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0,
            'class' => 'index-static', 'id' => 'ProjectsIndex',
        ],
        'head' => ['rows' => [[
            'columns' => [
                'image' => ['params' => ['class' => 'center hide-on-small-only'], 'html' => '&nbsp;'],
                'title' => __d('projects', 'Title'),
                'status' => ['params' => ['class' => 'center hide-on-small-only'], 'html' => __d('projects', 'Status')],
                'actions' => '',
                'log' => ['params' => ['class' => 'left'], 'html' => __d('projects', 'Last Log')],
            ],
        ]]],
        'foot' => ['rows' => [['columns' => [
            'paginator' => [
                'params' => ['colspan' => 5],
                'html' => '<ul class="paginator">' . $this->Paginator->numbers([
                    'first' => '<<',
                    'last' => '>>',
                    'modulus' => 3]) . '</ul>',
            ],
        ]]]],
    ],
];

foreach ($projects as $project) {
    $projectStatus = $projectsStatuses[$project->status_id] ?? '';

    $lastLogDescript = '';
    if (!empty($project->last_log)) {
        $lastLogDescript = sprintf(
            '<span class="small">%2$s, %3$s</span><div>%1$s</div>',
            h($project->last_log->descript),
            $project->last_log->created,
            $project->last_log->user->name
        );
    }

    $index['table']['body']['rows'][]['columns'] = [
        'image' => [
            'params' => ['class' => 'center hide-on-small-only'],
            'html' => $this->Html->image(
                //['action' => 'picture', $project->id, 'thumb'],
                'data:image/png;base64, ' . base64_encode(ProjectsFuncs::thumb($project)),
                ['style' => 'height: 50px;', 'class' => 'project-avatar', 'quote' => false]
            ),
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
            ['controller' => 'ProjectsLogs', 'action' => 'edit', '?' => ['project' => $project->id]],
            ['escape' => false, 'class' => 'btn btn-small btn-floating add-projects-log']
        ),
        'log' => [
            'params' => ['class' => 'last-log'],
            'html' => $lastLogDescript,
        ]
    ];
}

echo $this->Lil->index($index, 'Projects.Projects.index');
?>
<script type="text/javascript">
    var searchUrl = "<?php echo Router::url([
        'plugin' => 'Projects',
        'controller' => 'Projects',
        'action' => 'index',
        '?' => array_merge($this->request->getQuery(), ['search' => '__term__']),
    ]); ?>";

    var popupElement = null;

    $(document).ready(function() {
        ////////////////////////////////////////////////////////////////////////////////////////////
        // Filter for documents
        $(".search-panel input").on("input", function(e) {
            var rx_term = new RegExp("__term__", "i");
            $.get(searchUrl.replace(rx_term, encodeURIComponent($(this).val())), function(response) {
                let tBody = response.substring(response.indexOf("<table class=\"index"), response.indexOf("</table>")+8);
                $("#ProjectsIndex").html(tBody);

                $(".add-projects-log").each(function() {
                    $(this).modalPopup({
                        title: "<?= __d('projects', 'Add Log') ?>",
                        onOpen: function(popup) { $("#projects-logs-descript", popup).focus(); }
                    });
                });
            });
        });

        $(".add-projects-log").each(function() {
            $(this).on("click", function(e) {
                popupElement = $(this);
            });
            $(this).modalPopup({
                title: "<?= __d('projects', 'Add Log') ?>",
                processSubmit: true,
                onOpen: function(popup) { $("#projects-logs-descript", popup).focus(); },
                onHtml: function(data, popup) {
                    $("td.last-log", popupElement.closest("tr")).html(data);
                    popup.instance.close();
                }
            });
        });

        $("#SearchBox").focus();
    });
</script>
