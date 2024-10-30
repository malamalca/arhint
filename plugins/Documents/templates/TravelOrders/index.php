<?php
use Cake\Routing\Router;

$start_span = empty($filter['start']) ? $dateSpan['start'] : $filter['start'];
$end_span = empty($filter['end']) ? $dateSpan['end'] : $filter['end'];

$startLink = $this->Html->link(
    (string)$start_span,
    ['action' => 'filter'],
    ['id' => 'lil-documents-link-date-start', 'class' => 'nowrap']
);
$endLink = $this->Html->link(
    (string)$end_span,
    ['action' => 'filter'],
    ['id' => 'lil-documents-link-date-end'],
    ['class' => 'dropdown-trigger no-autoinit nowrap', 'data-target' => 'lil-documents-input-date-end']
);

$counterLink = $this->Html->link(
    $counter->title,
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-counters', 'data-target' => 'dropdown-counters']
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

$title = __d(
    'documents',
    '"{2}" from {0} to {1}',
    $startLink,
    $endLink,
    $counterLink
);

$documents_index = [
    'title_for_layout' => $title,
    'actions' => [
        'pre' => '<div>',
        'post' => '',
        'lines' => [
            sprintf('<input type="hidden" value="%s" id="lil-documents-input-date-start" />', $start_span->toDateString()),
            sprintf('<input type="hidden" value="%s" id="lil-documents-input-date-end" />', $end_span->toDateString()),
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
    'table' => [
        'pre' => $this->Arhint->searchPanel($this->getRequest()->getQuery('search', '')),
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminDocumentsIndex',
        ],
        'head' => ['rows' => [
            1 => ['columns' => [
                'no' => [
                    'html' => $this->Paginator->sort('no', __d('documents', 'No')),
                ],
                'date' => [
                    'parameters' => ['class' => 'center-align hide-on-small-only'],
                    'html' => $this->Paginator->sort('dat_task', __d('documents', 'Date')),
                ],
                'task' => [
                    'parameters' => ['class' => 'left-align hide-on-small-only'],
                    'html' => $this->Paginator->sort('title', __d('documents', 'Task')),
                ],
                'employee' => [
                    'parameters' => ['class' => 'left-align hide-on-small-only'],
                    'html' => __d('documents', 'Employee'),
                ],
                'total' => [
                    'parameters' => ['class' => 'right-align'],
                    'html' => $this->Paginator->sort('total', __d('documents', 'Total')),
                ],
            ]],
        ]],
        'foot' => ['rows' => [0 => ['columns' => [
            'no' => [
                'parameters' => ['class' => 'left-align '],
                'html' => '<ul class="paginator">' . $this->Paginator->numbers([
                    'first' => '<<',
                    'last' => '>>',
                    'modulus' => 3]) . '</ul>',
            ],
            'date' => [
                'parameters' => ['class' => 'right-align hide-on-small-only'],
                'html' => '',
            ],
            'descript' => [
                'parameters' => ['class' => 'documents-title right-align hide-on-small-only'],
                'html' => '',
            ],
            'client' => [
                'parameters' => ['class' => 'documents-client right-align hide-on-small-only'],
                'html' => __d('documents', 'Total Sum') . ': ',
            ],
            'total' => [
                'parameters' => ['class' => 'right-align nowrap'],
                'html' => '&nbsp;',
            ],
        ]]]],
    ],
];

$total = 0;
$link_template = '<a href="' . Router::url(['action' => 'view', '__id__']) . '">__title__</a>';
foreach ($data as $travelOrder) {
    $documents_index['table']['body']['rows'][]['columns'] = [
        'no' => [
            'parameters' => ['class' => 'nowrap'],
            'html' => '<div class="documents-no">' .
                strtr(
                    $link_template,
                    [
                        '__title__' => empty($travelOrder->no) ? __d('documents', 'n/a') : $travelOrder->no,
                        '__id__' => $travelOrder->id,
                    ]
                ) .
                sprintf(
                    '<div class="hide-on-med-and-up">%1$s<br />%2$s</div>',
                    (string)$travelOrder->dat_task,
                    h($document->client['title'] ?? '')
                ) . '</div>',
        ],
        'date' => [
            'parameters' => ['class' => 'center-align nowrap hide-on-small-only'],
            'html' => $this->Arhint->calendarDay($travelOrder->dat_task),
        ],
        'task' => [
            'parameters' => ['class' => 'documents-title left-align hide-on-small-only'],
            'html' => h($travelOrder->title) . '<div class="small">' . h($travelOrder->descript) . '</div>' .
                '<div class="small">' . h($travelOrder->departure) . '</div>' .
                // attachment
                ($travelOrder->attachments_count == 0 ? '' :
                    ' ' . $this->Html->image('/documents/img/attachment.png')),
        ],
        'client' => [
            'parameters' => ['class' => 'documents-client left-align hide-on-small-only'],
            'html' => '<div class="truncate">' . h($travelOrder->employee->name ?? '') . '</div>',
        ],
        'project' => empty($project) ? null : [
            'parameters' => ['class' => 'documents-project left-align'],
            'html' => '<div style="height: 20px; overflow: hidden; scroll: none;">' .
                ($project ? $this->Html->link(
                    $project,
                    [
                        'plugin' => 'Projects',
                        'controller' => 'projects',
                        'action' => 'view',
                        $project->id,
                    ]
                ) : '') .
                '</div>',
        ],
        'net_total' => [
            'parameters' => ['class' => 'documents-net_total right-align nowrap hide-on-small-only'],
            'html' => $this->Number->currency($travelOrder->net_total ?? 0),
        ],
        'total' => [
            'parameters' => ['class' => 'documents-total right-align nowrap'],
            'html' => $this->Number->currency($travelOrder->total ?? 0),
        ],
    ];
    $total += $travelOrder->total;
}

//$documents_index['table']['foot']['rows'][0]['columns']['total']['html'] = $this->Number->currency($documentsTotals['sumTotal']);

echo $this->Lil->index($documents_index, 'Documents.TravelOrders.index');
?>
<script type="text/javascript">
    var startEndUrl = "<?php echo Router::url([
        'plugin' => 'Documents',
        'controller' => 'TravelOrders',
        'action' => 'index',
        '?' => array_merge($this->request->getQuery(), ['start' => '__start__', 'end' => '__end__']),
    ]); ?>";

    var searchUrl = "<?php echo Router::url([
        'plugin' => 'Documents',
        'controller' => 'TravelOrders',
        'action' => 'index',
        '?' => array_merge($this->request->getQuery(), ['search' => '__term__']),
    ]); ?>";

    var searchTimer = null;

    function filterByDate(dateText, startOrEnd) {
        var rx_start = new RegExp("__start__", "i");
        var rx_end = new RegExp("__end__", "i");
        if (startOrEnd == 'start') {
            rpl_start = dateText;
            rpl_end = $('#lil-documents-input-date-end').val();
        } else {
            rpl_start = $('#lil-documents-input-date-start').val();
            rpl_end = dateText;
        }

        var targetUrl = startEndUrl.replace(rx_start, rpl_start).replace(rx_end, rpl_end)
        document.location.href = targetUrl;
    }

    function searchDocuments()
    {
        var rx_term = new RegExp("__term__", "i");
        $.get(searchUrl.replace(rx_term, encodeURIComponent($(".search-panel input").val())), function(response) {
            let tBody = response
                .substring(response.indexOf("<table class=\"index"), response.indexOf("</table>")+8);
            $("#AdminDocumentsIndex").html(tBody);
        });
    }

    $(document).ready(function() {
        ////////////////////////////////////////////////////////////////////////////////////////////
        // Filter for documents
        $(".search-panel input").on("input", function(e) {
            if ($(this).val().length > 1) {
                if (searchTimer) {
                    window.clearTimeout(searchTimer);
                    searchTimer = null;
                }
                searchTimer = window.setTimeout(searchDocuments, 500);
            }
        }).focus();


        ////////////////////////////////////////////////////////////////////////////////////////////
        // Start-End date in title
        M.Datepicker.init($("#lil-documents-input-date-start").get(0), {
            format: "yyyy-mm-dd",
            setDefaultDate: true,
            onSelect: function(date, inst) {
                let dateString = new Date(date.getTime() - (date.getTimezoneOffset() * 60000 ))
                    .toISOString()
                    .substring(0,10);
                filterByDate(dateString, "start");
            }
        });
        $("#lil-documents-link-date-start").click(function() {
            let datePicker = M.Datepicker.getInstance($("#lil-documents-input-date-start").get(0));
            datePicker.open();
            return false;
        });

        M.Datepicker.init($("#lil-documents-input-date-end").get(0), {
            format: "yyyy-mm-dd",
            setDefaultDate: true,
            onSelect: function(date) {
                let dateString = new Date(date.getTime() - (date.getTimezoneOffset() * 60000 ))
                    .toISOString()
                    .substring(0,10);
                filterByDate(dateString, "end");
            }
        });
        $("#lil-documents-link-date-end").click(function() {
            let datePicker = M.Datepicker.getInstance($("#lil-documents-input-date-end").get(0));
            datePicker.open();
            return false;
        });

        ////////////////////////////////////////////////////////////////////////////////////////////
        $('#MenuItemExportSepaXml, #MenuItemExportPdf, #MenuItemEmail, #MenuItemPrint').click(function(e) {
            let rx_term = new RegExp("__term__", "i");
            let searchTerm = $(".search-panel input").val();
            let url = $(this).prop("href").replace(rx_term, encodeURIComponent(searchTerm));

            document.location.href = url;

            e.preventDefault();
            return false;
        });

    });
</script>
