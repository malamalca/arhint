<?php
use Cake\Routing\Router;

$start_span = empty($filter['start']) ? $dateSpan['start'] : $filter['start'];
$end_span = empty($filter['end']) ? $dateSpan['end'] : $filter['end'];

$startLink = $this->Html->link(
    (string)$start_span,
    ['action' => 'filter'],
    ['id' => 'lil-invoices-link-date-start']
);
$endLink = $this->Html->link(
    (string)$end_span,
    ['action' => 'filter'],
    ['id' => 'lil-invoices-link-date-end'],
    ['class' => 'dropdown-trigger no-autoinit', 'data-target' => 'lil-invoices-input-date-end']
);

$title = __d(
    'lil_invoices',
    '"{2}" from {0} to {1}',
    $startLink,
    $endLink,
    h($counter->title)
);

$invoices_index = [
    'title_for_layout' => $title,
    'actions' => [
        'pre' => '<div>',
        'post' => '',
        'lines' => [
            sprintf('<input type="hidden" value="%s" id="lil-invoices-input-date-start" />', $start_span->toDateString()),
            sprintf('<input type="hidden" value="%s" id="lil-invoices-input-date-end" />', $end_span->toDateString()),
        ],
    ],
    'menu' => [
        'add' => [
            'title' => __d('lil_invoices', 'Add'),
            'visible' => $counter->active,
            'url' => [
                'plugin' => 'LilInvoices',
                'controller' => 'invoices',
                'action' => 'add',
                '?' => ['counter' => $counter->id],
            ],
        ],
        'print' => [
            'title' => __d('lil_invoices', 'Print'),
            'visible' => true,
            'url' => [
                'plugin' => 'LilInvoices',
                'controller' => 'invoices',
                'action' => 'preview',
                '?' => array_merge($filter, ['search' => '__term__']),
            ],
            'params' => ['id' => 'MenuItemPrint'],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminInvoicesIndex'
        ],
        'head' => ['rows' => [
            0 => [
                'columns' => [
                    'search' => [
                        'params' => ['colspan' => 3, 'class' => 'input-field'],
                        'html' => sprintf('<input placeholder="%s" id="SearchBox" />', __d('lil_invoices', 'Search')),
                    ],
                    'pagination' => [
                        'params' => ['colspan' => 4, 'class' => 'right-align'],
                        'html' => '<ul class="paginator">' . $this->Paginator->numbers([
                            'first' => '<<',
                            'last' => '>>',
                            'modulus' => 3]) . '</ul>'],
                ],
            ],
            1 => ['columns' => [
                'cnt' => [
                    'parameters' => ['class' => 'center-align'],
                    'html' => $this->Paginator->sort('counter', __d('lil_invoices', 'Cnt')),
                ],
                'no' => [
                    'html' => $this->Paginator->sort('no', __d('lil_invoices', 'No')),
                ],
                'date' => [
                    'parameters' => ['class' => 'center-align'],
                    'html' => $this->Paginator->sort('dat_issue', __d('lil_invoices', 'Issued')),
                ],
                'title' => [
                    'html' => $this->Paginator->sort('title', __d('lil_invoices', 'Title')),
                ],
                'client' => [
                    'html' => __d('lil_invoices', 'Client'),
                ],
                'net_total' => [
                    'parameters' => ['class' => 'right-align nowrap'],
                    'html' => $this->Paginator->sort('net_total', __d('lil_invoices', 'Net Total')),
                ],
                'total' => [
                    'parameters' => ['class' => 'right-align nowrap'],
                    'html' => $this->Paginator->sort('total', __d('lil_invoices', 'Total')),
                ],
            ]],
        ]],
        'foot' => ['rows' => [0 => ['columns' => [
            0 => [
                'parameters' => ['class' => 'right-align', 'colspan' => 5],
                'html' => __d('lil_invoices', 'Total Sum') . ': ',
            ],
            'net_total' => [
                'parameters' => ['class' => 'right-align nowrap'],
                'html' => '&nbsp;',
            ],
            'total' => [
                'parameters' => ['class' => 'right nowrap'],
                'html' => '&nbsp;',
            ],
        ]]]],
    ],
];

$total = 0;
$net_total = 0;
$link_template = '<a href="' . Router::url(['action' => 'view', '__id__']) . '">__title__</a>';
foreach ($data as $invoice) {
    $client = $counter->kind == 'issued' ? $invoice->receiver : $invoice->issuer;

    $invoices_index['table']['body']['rows'][]['columns'] = [
        'cnt' => [
            'parameters' => ['class' => 'center-align'],
            'html' => h($invoice->counter),
        ],
        'no' => [
            'parameters' => ['class' => 'nowrap'],
            'html' => strtr(
                $link_template,
                [
                    '__title__' => empty($invoice->no) ? __d('lil_invoices', 'n/a') : $invoice->no,
                    '__id__' => $invoice->id,
                ]
            ),
        ],
        'date' => [
            'parameters' => ['class' => 'center-align nowrap'],
            'html' => (string)$invoice->dat_issue,
        ],
        'title' => [
            'parameters' => ['class' => 'left-align nowrap'],
            'html' => h($invoice->title) .
                // attachment
                ($invoice->invoices_attachment_count == 0 ? '' :
                    ' ' . $this->Html->image('/lil_invoices/img/attachment.png')),
        ],
        'client' => [
            'parameters' => ['class' => 'left-align'],
            'html' => '<div style="height: 20px; min-width: 250px; overflow: hidden; scroll: none;">' . h($client->title) . '</div>',
        ],
        'net_total' => [
            'parameters' => ['class' => 'right-align nowrap'],
            'html' => $this->Number->currency($invoice->net_total),
        ],
        'total' => [
            'parameters' => ['class' => 'right nowrap'],
            'html' => $this->Number->currency($invoice->total),
        ],
    ];
    $total += $invoice->total;
    $net_total += $invoice->net_total;
}
$invoices_index['table']['foot']['rows'][0]['columns']['total']['html'] = $this->Number->currency($total);
$invoices_index['table']['foot']['rows'][0]['columns']['net_total']['html'] = $this->Number->currency($net_total);

echo $this->Lil->index($invoices_index, 'LilInvoices.Invoices.index');
?>
<script type="text/javascript">
    var startEndUrl = "<?php echo Router::url([
        'plugin' => 'LilInvoices',
        'controller' => 'invoices',
        'action' => 'index',
        '?' => array_merge($this->request->getQuery(), ['start' => '__start__', 'end' => '__end__']),
    ]); ?>";

    var searchUrl = "<?php echo Router::url([
        'plugin' => 'LilInvoices',
        'controller' => 'Invoices',
        'action' => 'index',
        '?' => array_merge($this->request->getQuery(), ['search' => '__term__']),
    ]); ?>";

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

    $(document).ready(function() {
        ////////////////////////////////////////////////////////////////////////////////////////////
        // Filter for invoices
        $("#SearchBox").on("input", function(e) {
            var rx_term = new RegExp("__term__", "i");
            $.get(searchUrl.replace(rx_term, encodeURIComponent($(this).val())), function(response) {
                let tBody = response.substring(response.indexOf("<tbody>")+7, response.indexOf("</tbody>"));
                $("#AdminInvoicesIndex tbody").html(tBody);

                let tFoot = response.substring(response.indexOf("<tfoot>")+7, response.indexOf("</tfoot>"));
                $("#AdminInvoicesIndex tfoot").html(tFoot);

                let paginator = response.substring(
                    response.indexOf("<ul class=\"paginator\">")+22,
                    response.indexOf("</ul>", response.indexOf("<ul class=\"paginator\">"))
                );
                $("#AdminInvoicesIndex ul.paginator").html(paginator);
            });
        });


        ////////////////////////////////////////////////////////////////////////////////////////////
        // Start-End date in title
        $("#lil-invoices-input-date-start").datepicker({
            dateFormat: 'yyyy-mm-dd',
            setDefaultDate: true,
            onSelect: function(date, inst) {
                let dateString = new Date(date.getTime() - (date.getTimezoneOffset() * 60000 ))
                    .toISOString()
                    .substring(0,10);
                filterByDate(dateString, 'start');
            }
        });
        $("#lil-invoices-link-date-start").click(function() {
            $("#lil-invoices-input-date-start").datepicker('open');
            return false;
        });

        $("#lil-invoices-input-date-end").datepicker({
            dateFormat: 'yyyy-mm-dd',
            setDefaultDate: true,
            onSelect: function(date) {
                let dateString = new Date(date.getTime() - (date.getTimezoneOffset() * 60000 ))
                    .toISOString()
                    .substring(0,10);
                filterByDate(dateString, 'end');
            }
        });
        $("#lil-invoices-link-date-end").click(function() {
            $("#lil-invoices-input-date-end").datepicker('open');
            return false;
        });

        ////////////////////////////////////////////////////////////////////////////////////////////
        $('#MenuItemExportSepaXml, #MenuItemExportPdf, #MenuItemEmail, #MenuItemPrint').click(function(e) {
            let rx_term = new RegExp("__term__", "i");
            let searchTerm = $("#SearchBox").val();
            let url = $(this).prop("href").replace(rx_term, encodeURIComponent(searchTerm));

            document.location.href = url;

            e.preventDefault();
            return false;
        });

    });
</script>
