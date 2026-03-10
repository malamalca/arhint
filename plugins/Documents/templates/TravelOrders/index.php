<?php
use Cake\Routing\Router;
use Documents\Model\Entity\TravelOrder;

$counterLink = $this->Html->link(
    $counter->title,
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-counters', 'data-target' => 'dropdown-counters'],
);
$popupCounters = [];
foreach ($counters as $cntr) {
    $menuItem = [
        'title' => h($cntr->title),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['counter' => $cntr->id])],
        'active' => $this->getRequest()->getQuery('counter') == $cntr->id,
    ];
    $popupCounters['items'][] = $menuItem;
}
$popupCounters = $this->Lil->popup('counters', $popupCounters, true);

// EMPLOYEE FILTER DROPDOWN
$popupEmployees = ['items' => [[
    'title' => __d('documents', 'All Employees'),
    'active' => $docFilter->get('employee') === null,
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
        'q' => $docFilter->buildQuery('employee', null),
    ])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($employees as $emp) {
    $popupEmployees['items'][] = [
        'title' => h($emp->name),
        'active' => $docFilter->check('employee', $emp->name),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery(
                'employee',
                $docFilter->check('employee', $emp->name) ? null : $emp->name,
            ),
        ])],
        'params' => ['class' => 'nowrap'],
    ];
}
$popupEmployees = $this->Lil->popup('employees', $popupEmployees, true);

// STATUS FILTER DROPDOWN
$statusLabels = TravelOrder::statusLabels();
$currentStatus = $docFilter->get('status');
$statusFilterLabel = is_string($currentStatus) && isset($statusLabels[$currentStatus])
    ? $statusLabels[$currentStatus]
    : __d('documents', 'Status');
$popupStatuses = ['items' => [[
    'title' => __d('documents', 'All Statuses'),
    'active' => $currentStatus === null || in_array($currentStatus, ['open', 'closed'], true),
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
        'q' => $docFilter->buildQuery('status', null),
    ])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($statusLabels as $statusKey => $statusTitle) {
    $popupStatuses['items'][] = [
        'title' => h($statusTitle),
        'active' => $docFilter->check('status', $statusKey),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery(
                'status',
                $docFilter->check('status', $statusKey) ? null : $statusKey,
            ),
        ])],
        'params' => ['class' => 'nowrap'],
    ];
}
$popupStatuses = $this->Lil->popup('statuses', $popupStatuses, true);

// SORT DROPDOWN
$sortLabel = match ($docFilter->get('sort')) {
    'oldest' => __d('documents', 'Oldest'),
    'date-desc' => __d('documents', 'Newest by Date'),
    'date-asc' => __d('documents', 'Oldest by Date'),
    'employee' => __d('documents', 'By Employee'),
    default => __d('documents', 'Newest'),
};
$popupSort = ['items' => [
    [
        'title' => __d('documents', 'Newest'),
        'active' => $docFilter->get('sort') === null || $docFilter->check('sort', 'newest'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', null),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => __d('documents', 'Oldest'),
        'active' => $docFilter->check('sort', 'oldest'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'oldest'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    '-',
    [
        'title' => __d('documents', 'Newest by Date'),
        'active' => $docFilter->check('sort', 'date-desc'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'date-desc'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => __d('documents', 'Oldest by Date'),
        'active' => $docFilter->check('sort', 'date-asc'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'date-asc'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    '-',
    [
        'title' => __d('documents', 'By Employee'),
        'active' => $docFilter->check('sort', 'employee'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'employee'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
]];
$popupSort = $this->Lil->popup('sort', $popupSort, true);

$title = $counterLink;

$tableIndex = [
    'title_for_layout' => $title,
    'actions' => [
        'lines' => [
            $popupCounters,
        ],
    ],
    'menu' => [
        'add' => [
            'title' => __d('documents', 'Add'),
            'visible' => $counter->active && $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'plugin' => 'Documents',
                'controller' => 'TravelOrders',
                'action' => 'edit',
                '?' => ['counter' => $counter->id],
            ],
        ],
        'print' => [
            'title' => __d('documents', 'Print'),
            'visible' => true,
            'url' => [
                'plugin' => 'Documents',
                'controller' => 'TravelOrders',
                'action' => 'preview',
                '?' => array_merge($filter, ['search' => '__term__']),
            ],
            'params' => ['id' => 'MenuItemPrint'],
        ],
    ],
    'pre' => '<div id="panel-index">',
    'post' => '</div>',
    'panels' => [
        'search' => '<div id="panel-search">' .
            sprintf('<form method="get" action="%s">', Router::url()) .
            sprintf(
                '<input name="q" id="query" value="%s" />',
                htmlspecialchars((string)$this->getRequest()->getQuery('q', '')),
            ) .
            '<button type="submit" class="btn-small tonal" id="btn-search">' .
            '<i class="material-icons">search</i></button>' .
            '</form>' .
            ($counter->active && $this->getCurrentUser()->hasRole('editor') ?
                sprintf(
                    '<a href="%2$s" class="btn-small filled" id="btn-add"><i class="material-icons">add</i>%1$s</a>',
                    __d('documents', 'New'),
                    $this->Url->build([
                        'plugin' => 'Documents',
                        'controller' => 'TravelOrders',
                        'action' => 'edit',
                        '?' => ['counter' => $counter->id],
                    ]),
                ) : '') .
            '</div>',
        'filter' => '<div id="panel-filter">' .
            '<div class="checkbox"><input type="checkbox" id="select-all-orders" /></div>' .
            '<div id="panel-counters">' .
                $this->Html->link(
                    h(__d('documents', 'Open')) .
                        sprintf('<span class="badge">%d</span>', $openCount),
                    ['?' => array_merge($this->getRequest()->getQuery(), [
                        'q' => $docFilter->buildQuery('status', $docFilter->check('status', 'open') ? null : 'open'),
                    ])],
                    [
                        'class' => 'btn text' . ($docFilter->check('status', 'open') ? ' active' : ''),
                        'escape' => false,
                    ],
                ) .
                $this->Html->link(
                    h(__d('documents', 'Closed')) .
                        sprintf('<span class="badge">%d</span>', $closedCount),
                    ['?' => array_merge($this->getRequest()->getQuery(), [
                        'q' => $docFilter->buildQuery(
                            'status',
                            $docFilter->check('status', 'closed') ? null : 'closed',
                        ),
                    ])],
                    [
                        'class' => 'btn text' . ($docFilter->check('status', 'closed') ? ' active' : ''),
                        'escape' => false,
                    ],
                ) .
            '</div>' .
            '<ul>' .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum"'
                . ' data-target="dropdown-employees">%s &#128899;</a></li>',
                __d('documents', 'Employee'),
            ) .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum%s"'
                . ' data-target="dropdown-statuses">%s &#128899;</a></li>',
                is_string($currentStatus) && !in_array($currentStatus, ['open', 'closed', null], true) ? ' active' : '',
                h($statusFilterLabel),
            ) .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-sort">'
                . '<i class="material-icons">sort</i>%s &#128899;</a></li>',
                h($sortLabel),
            ) .
            '</ul>' .
            $popupEmployees .
            $popupStatuses .
            $popupSort .
            '</div>',
        'rows' => [
            'params' => ['id' => 'panel-list'],
            'lines' => [],
        ],
        'footer' => [
            'params' => ['id' => 'panel-footer'],
            'lines' => [
                '<ul class="paginator">' .
                $this->Paginator->numbers(['first' => 1, 'last' => 1, 'modulus' => 3]) .
                '</ul>',
            ],
        ],
    ],
];

if ($data->items()->isEmpty()) {
    $tableIndex['panels']['rows']['lines'][] =
        '<div id="no-rows-found">' .
        '<h4>' . __d('documents', 'No travel orders found.') . '</h4>' .
        '<p>' . __d('documents', 'Try adjusting your search filters.') . '</p>' .
        '</div>';
} else {
    foreach ($data as $travelOrder) {
        $tableIndex['panels']['rows']['lines'][] = $this->element('Documents.travel_order_row', [
            'travelOrder' => $travelOrder,
            'docFilter' => $docFilter,
        ]);
    }
}

echo $this->Lil->panels($tableIndex, 'Documents.TravelOrders.index');
?>
<script type="text/javascript">
    var allOrdersChecked = false;

    document.addEventListener("DOMContentLoaded", function() {
        // dropdown triggers
        const elems = document.querySelectorAll(".dropdown-trigger-costum");
        elems.forEach((dropdown) => {
            M.Dropdown.init(dropdown, {
                constrainWidth: false,
                coverTrigger: false,
            });
        });

        <?php if ($counter->active && $this->getCurrentUser()->hasRole('editor')) : ?>
        $("#btn-add").modalPopup({
            title: "<?= __d('documents', 'New Travel Order') ?>",
            onOpen: function(popup) {
                $("#title", popup).focus();
            },
        });
        <?php endif; ?>

        // select-all checkbox
        $("#select-all-orders").on("change", function(e) {
            $("div.panel-row div.checkbox input").prop("checked", $(this).prop("checked"));
            allOrdersChecked = true;
        });
        $("div.panel-row div.checkbox input").on("change", function(e) {
            if (allOrdersChecked) {
                $("#select-all-orders").prop("checked", false);
            }
            allOrdersChecked = false;
        });

        // print/export
        $('#MenuItemPrint').click(function(e) {
            let rx_term = new RegExp("__term__", "i");
            let searchTerm = $("#query").val();
            let url = $(this).prop("href").replace(rx_term, encodeURIComponent(searchTerm));
            document.location.href = url;
            e.preventDefault();
            return false;
        });
    });
</script>
