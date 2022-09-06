<?php
use Cake\Routing\Router;

$start_span = empty($filter['start']) ? $dateSpan['start'] : $filter['start'];
$end_span = empty($filter['end']) ? $dateSpan['end'] : $filter['end'];

$startLink = $this->Html->link(
    (string)$start_span,
    ['action' => 'filter'],
    ['id' => 'lil-invoices-link-date-start', 'class' => 'nowrap'],
);
$endLink = $this->Html->link(
    (string)$end_span,
    ['action' => 'filter'],
    ['id' => 'lil-invoices-link-date-end', 'class' => 'nowrap'],
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
        'url' => [
            'controller' => $cntr->kind,
            '?' => array_merge($this->getRequest()->getQuery(), ['counter' => $cntr->id])],
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

$invoicesIndex = [
    'title_for_layout' => $title,
    'actions' => [
        'pre' => '<div>',
        'post' => '',
        'lines' => [
            sprintf('<input type="hidden" value="%s" id="lil-invoices-input-date-start" />', $start_span->toDateString()),
            sprintf('<input type="hidden" value="%s" id="lil-invoices-input-date-end" />', $end_span->toDateString()),
            $popupCounters,
        ],
    ],
    'menu' => [
        'add' => [
            'title' => __d('documents', 'Add'),
            'visible' => $counter->active && $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'plugin' => 'Documents',
                'controller' => 'Invoices',
                'action' => 'edit',
                '?' => ['counter' => $counter->id],
            ],
        ],
        'print' => [
            'title' => __d('documents', 'Print'),
            'visible' => true,
            'url' => [
                'plugin' => 'Documents',
                'controller' => 'Invoices',
                'action' => 'preview',
                '?' => array_merge($filter, ['search' => '__term__']),
            ],
            'params' => ['id' => 'MenuItemPrint'],
        ],
    ],
    'table' => [
        'pre' => $this->Arhint->searchPanel($this->getRequest()->getQuery('search', '')),
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminInvoicesIndex',
        ],
        'head' => ['rows' => [
            1 => ['columns' => [
                'no' => [
                    'html' => $this->Paginator->sort('no', __d('documents', 'No')),
                ],
                'date' => [
                    'parameters' => ['class' => 'center-align hide-on-small-only'],
                    'html' => $this->Paginator->sort('dat_issue', __d('documents', 'Issued')),
                ],
                'title' => [
                    'parameters' => ['class' => 'left-align hide-on-small-only'],
                    'html' => $this->Paginator->sort('title', __d('documents', 'Title')),
                ],
                'client' => [
                    'parameters' => ['class' => 'left-align hide-on-small-only'],
                    'html' => __d('documents', 'Client'),
                ],
                'net_total' => [
                    'parameters' => ['class' => 'right-align hide-on-small-only'],
                    'html' => $this->Paginator->sort('net_total', __d('documents', 'Net Total')),
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
            'title' => [
                'parameters' => ['class' => 'invoices-title right-align hide-on-small-only'],
                'html' => '',
            ],
            'client' => [
                'parameters' => ['class' => 'invoices-client right-align hide-on-small-only'],
                'html' => __d('documents', 'Total Sum') . ': ',
            ],
            'net_total' => [
                'parameters' => ['class' => 'right-align nowrap hide-on-small-only'],
                'html' => '&nbsp;',
            ],
            'total' => [
                'parameters' => ['class' => 'right-align nowrap'],
                'html' => '&nbsp;',
            ],
        ]]]],
    ],
];

$total = 0;
$net_total = 0;
$link_template = '<a href="' . Router::url(['action' => 'view', '__id__']) . '">__title__</a>';
foreach ($data as $invoice) {
    $project = $projects[$invoice->project_id] ?? null;

    $invoicesIndex['table']['body']['rows'][]['columns'] = [
        'no' => [
            'parameters' => ['class' => 'nowrap'],
            'html' => '<div class="invoices-no">' .
                strtr(
                    $link_template,
                    [
                        '__title__' => empty($invoice->no) ? __d('documents', 'n/a') : $invoice->no,
                        '__id__' => $invoice->id,
                    ]
                ) .
                sprintf(
                    '<div class="hide-on-med-and-up">%1$s<br />%2$s</div>',
                    (string)$invoice->dat_issue,
                    h($invoice->Client['title'] ?? '')
                ) . '</div>',
        ],
        'date' => [
            'parameters' => ['class' => 'center-align nowrap hide-on-small-only'],
            'html' => $this->Arhint->calendarDay($invoice->dat_issue),
        ],
        'title' => [
            'parameters' => ['class' => 'invoices-title left-align hide-on-small-only'],
            'html' => h($invoice->title) .
                // attachment
                ($invoice->attachments_count == 0 ? '' :
                    ' ' . $this->Html->image('/documents/img/attachment.png')),
        ],
        'client' => [
            'parameters' => ['class' => 'invoices-client left-align hide-on-small-only'],
            'html' => '<div class="truncate">' . h($invoice->Client['title'] ?? '') . '</div>',
        ],
        'net_total' => [
            'parameters' => ['class' => 'invoices-net_total right-align nowrap hide-on-small-only'],
            'html' => $this->Number->currency($invoice->net_total),
        ],
        'total' => [
            'parameters' => ['class' => 'invoices-total right-align nowrap'],
            'html' => $this->Number->currency($invoice->total),
        ],
    ];
    $total += $invoice->total;
    $net_total += $invoice->net_total;
}

$invoicesIndex['table']['foot']['rows'][0]['columns']['total']['html'] = $this->Number->currency($invoicesTotals['sumTotal']);
$invoicesIndex['table']['foot']['rows'][0]['columns']['net_total']['html'] = $this->Number->currency($invoicesTotals['sumNetTotal']);

echo $this->Lil->index($invoicesIndex, 'Documents.Invoices.index');
?>
<script type="text/javascript">
    var startEndUrl = "<?php echo Router::url([
        'plugin' => 'Documents',
        'controller' => 'Invoices',
        'action' => 'index',
        '?' => array_merge($this->request->getQuery(), ['start' => '__start__', 'end' => '__end__']),
    ]); ?>";

    var searchUrl = "<?php echo Router::url([
        'plugin' => 'Documents',
        'controller' => 'Invoices',
        'action' => 'index',
        '?' => array_merge($this->request->getQuery(), ['search' => '__term__']),
    ]); ?>";

    var searchTimer = null;

    function filterByDate(dateText, startOrEnd) {
        var rx_start = new RegExp("__start__", "i");
        var rx_end = new RegExp("__end__", "i");
        if (startOrEnd == 'start') {
            rpl_start = dateText;
            rpl_end = $('#lil-invoices-input-date-end').val();
        } else {
            rpl_start = $('#lil-invoices-input-date-start').val();
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
            $("#AdminInvoicesIndex").html(tBody);
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
        $("#lil-invoices-input-date-start").datepicker({
            format: "yyyy-mm-dd",
            setDefaultDate: true,
            onSelect: function(date, inst) {
                let dateString = new Date(date.getTime() - (date.getTimezoneOffset() * 60000 ))
                    .toISOString()
                    .substring(0,10);
                filterByDate(dateString, "start");
            }
        });
        $("#lil-invoices-link-date-start").click(function() {
            $("#lil-invoices-input-date-start").datepicker("open");
            return false;
        });

        $("#lil-invoices-input-date-end").datepicker({
            format: "yyyy-mm-dd",
            setDefaultDate: true,
            onSelect: function(date) {
                let dateString = new Date(date.getTime() - (date.getTimezoneOffset() * 60000 ))
                    .toISOString()
                    .substring(0,10);
                filterByDate(dateString, "end");
            }
        });
        $("#lil-invoices-link-date-end").click(function() {
            $("#lil-invoices-input-date-end").datepicker("open");
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
