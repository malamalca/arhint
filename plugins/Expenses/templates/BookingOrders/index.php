<?php

/**
 * @var \App\View\AppView $this
 * @var iterable<\Expenses\Model\Entity\BookingOrder> $data
 * @var \Expenses\Filter\BookingOrdersFilter $docFilter
 * @var array<string, int> $statusCounts
 * @var int $openCount
 * @var int $closedCount
 * @var array<\App\Model\Entity\User> $users
 * @var array<string, string> $statusList
 */

use Cake\Routing\Router;
use Expenses\Model\Entity\BookingOrder;

// DATE SPAN FILTER DROPDOWN
$spanLabels = [
    'this-month' => __d('expenses', 'This Month'),
    'prev-month' => __d('expenses', 'Previous Month'),
    'last-3-months' => __d('expenses', 'Last 3 Months'),
    'this-year' => __d('expenses', 'This Year'),
];
$currentSpan = $docFilter->get('span');
$spanFilterLabel = is_string($currentSpan) && isset($spanLabels[$currentSpan])
    ? $spanLabels[$currentSpan]
    : __d('expenses', 'Period');
$popupSpan = ['items' => [[
    'title' => __d('expenses', 'All Time'),
    'active' => $currentSpan === null,
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
        'q' => $docFilter->buildQuery('span', null),
    ])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($spanLabels as $spanKey => $spanTitle) {
    $popupSpan['items'][] = [
        'title' => h($spanTitle),
        'active' => $docFilter->check('span', $spanKey),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery(
                'span',
                $docFilter->check('span', $spanKey) ? null : $spanKey,
            ),
        ])],
        'params' => ['class' => 'nowrap'],
    ];
}
$popupSpan = $this->Lil->popup('span', $popupSpan, true);

// OPENER FILTER DROPDOWN
$popupOpeners = '';
if ($this->getCurrentUser()->hasRole('admin')) {
    $openerPopupItems = ['items' => [[
        'title' => __d('expenses', 'All Users'),
        'active' => $docFilter->get('opener') === null,
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('opener', null),
        ])],
        'params' => ['class' => 'nowrap'],
    ]]];
    foreach ($users as $user) {
        $openerPopupItems['items'][] = [
            'title' => h($user->name),
            'active' => $docFilter->check('opener', $user->name),
            'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
                'q' => $docFilter->buildQuery(
                    'opener',
                    $docFilter->check('opener', $user->name) ? null : $user->name,
                ),
            ])],
            'params' => ['class' => 'nowrap'],
        ];
    }
    $popupOpeners = $this->Lil->popup('openers', $openerPopupItems, true);
}

// STATUS FILTER DROPDOWN
$statusLabels = BookingOrder::statusLabels();
$currentStatus = $docFilter->get('status');
$statusFilterLabel = is_string($currentStatus) && isset($statusLabels[$currentStatus])
    ? $statusLabels[$currentStatus]
    : __d('expenses', 'Status');
$popupStatuses = ['items' => [[
    'title' => __d('expenses', 'All Statuses'),
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
    'oldest' => __d('expenses', 'Oldest'),
    'date-desc' => __d('expenses', 'Newest by Date'),
    'date-asc' => __d('expenses', 'Oldest by Date'),
    'opener' => __d('expenses', 'By User'),
    default => __d('expenses', 'Newest'),
};
$popupSort = ['items' => [
    [
        'title' => __d('expenses', 'Newest'),
        'active' => $docFilter->get('sort') === null || $docFilter->check('sort', 'newest'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', null),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => __d('expenses', 'Oldest'),
        'active' => $docFilter->check('sort', 'oldest'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'oldest'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    '-',
    [
        'title' => __d('expenses', 'Newest by Date'),
        'active' => $docFilter->check('sort', 'date-desc'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'date-desc'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => __d('expenses', 'Oldest by Date'),
        'active' => $docFilter->check('sort', 'date-asc'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'date-asc'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    '-',
    [
        'title' => __d('expenses', 'By User'),
        'active' => $docFilter->check('sort', 'opener'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'opener'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
]];
$popupSort = $this->Lil->popup('sort', $popupSort, true);

$tableIndex = [
    'title_for_layout' => __d('expenses', 'Booking Orders'),
    'menu' => [
        'add' => [
            'title' => __d('expenses', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'plugin' => 'Expenses',
                'controller' => 'BookingOrders',
                'action' => 'edit',
            ],
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
            ($this->getCurrentUser()->hasRole('editor') ?
                sprintf(
                    '<a href="%2$s" class="btn-small filled" id="btn-add"><i class="material-icons">add</i>%1$s</a>',
                    __d('expenses', 'New'),
                    $this->Url->build([
                        'plugin' => 'Expenses',
                        'controller' => 'BookingOrders',
                        'action' => 'edit',
                    ]),
                ) : '') .
            '</div>',
        'filter' => '<div id="panel-filter">' .
            '<div class="checkbox"><input type="checkbox" id="select-all-orders" /></div>' .
            '<div id="panel-counters">' .
                $this->Html->link(
                    h(__d('expenses', 'Open')) .
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
                    h(__d('expenses', 'Closed')) .
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
            ($this->getCurrentUser()->hasRole('admin') ? sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum"'
                . ' data-target="dropdown-openers">%s &#128899;</a></li>',
                __d('expenses', 'User'),
            ) : '') .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum%s"'
                . ' data-target="dropdown-statuses">%s &#128899;</a></li>',
                is_string($currentStatus) && !in_array($currentStatus, ['open', 'closed', null], true) ? ' active' : '',
                h($statusFilterLabel),
            ) .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum%s"'
                . ' data-target="dropdown-span">%s &#128899;</a></li>',
                $currentSpan !== null ? ' active' : '',
                h($spanFilterLabel),
            ) .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-sort">'
                . '<i class="material-icons">sort</i>%s &#128899;</a></li>',
                h($sortLabel),
            ) .
            '</ul>' .
            $popupOpeners .
            $popupStatuses .
            $popupSpan .
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
        '<h4>' . __d('expenses', 'No booking orders found.') . '</h4>' .
        '<p>' . __d('expenses', 'Try adjusting your search filters.') . '</p>' .
        '</div>';
} else {
    foreach ($data as $bookingOrder) {
        $tableIndex['panels']['rows']['lines'][] = $this->element('Expenses.booking_order_row', [
            'bookingOrder' => $bookingOrder,
            'docFilter' => $docFilter,
        ]);
    }
}

echo $this->Lil->panels($tableIndex, 'Expenses.BookingOrders.index');
?>
<script type="text/javascript">
    var allOrdersChecked = false;

    document.addEventListener("DOMContentLoaded", function() {
        const elems = document.querySelectorAll(".dropdown-trigger-costum");
        elems.forEach((dropdown) => {
            M.Dropdown.init(dropdown, {
                constrainWidth: false,
                coverTrigger: false,
            });
        });

        <?php if ($this->getCurrentUser()->hasRole('editor')) : ?>
        $("#btn-add").modalPopup({
            title: "<?= __d('expenses', 'New Booking Order') ?>",
            onOpen: function(popup) {
                $("#title", popup).focus();
            },
        });
        <?php endif; ?>

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
    });
</script>

