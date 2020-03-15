<?php

use Cake\I18n\Time;
use Cake\Routing\Router;

/** POPUP Accounts */
$accountsLink = $this->Html->link(
    !empty($filter['account']) ? $accounts[$filter['account']] : __d('lil_expenses', 'all accounts'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-accounts', 'data-target' => 'dropdown-accounts']
);
$popupAccounts = ['items' => [0 => [
    'title' => __d('lil_expenses', 'All Accounts'),
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['account' => null])],
    'active' => empty($this->getRequest()->getQuery('account')),
]]];
foreach ($accounts as $accId => $accTitle) {
    $menuItem = [
        'title' => h($accTitle),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['account' => $accId])],
        'active' => $this->getRequest()->getQuery('account') == $accId,
    ];
    $popupAccounts['items'][] = $menuItem;
}
$popupAccounts = $this->Lil->popup('accounts', $popupAccounts, true);

/** POPUP From-To */
$fromto = ['from' => __d('lil_expenses', 'From'), 'to' => __d('lil_expenses', 'To')];
$fromtoLink = $this->Html->link(
    !empty($filter['type']) ? $fromto[$filter['type']] : __d('lil_expenses', 'from+to'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-fromto', 'data-target' => 'dropdown-fromto']
);
$popupFromto = ['items' => [[
        'title' => __d('lil_expenses', 'From + To'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['type' => null])],
        'active' => empty($filter['type']),
    ], [
        'title' => __d('lil_expenses', 'From'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['type' => 'from'])],
        'active' => $this->getRequest()->getQuery('type') == 'from',
    ], [
        'title' => __d('lil_expenses', 'To'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['type' => 'to'])],
        'active' => $this->getRequest()->getQuery('type') == 'to',
    ],
]];
$popupFromto = $this->Lil->popup('fromto', $popupFromto, true);

$popupMonths = '';
$popupYears = '';

$title = __d('lil_expenses', 'Payments {0} {1}', [$fromtoLink, $accountsLink]);
$hiddenControls = '';

if (isset($filter['span'])) {
    switch ($filter['span']) {
        case 'fromto':
            $start_link = $this->Html->link(
                $this->Time->format($filter['start'], $dateFormat),
                ['action' => 'filter'],
                ['id' => 'lil-payments-link-date-start']
            );
            $end_link = $this->Html->link(
                $this->Time->format($filter['end'], $dateFormat),
                ['action' => 'filter'],
                ['id' => 'lil-payments-link-date-end']
            );
            $hiddenControls = sprintf(
                '<input type="hidden" value="%1$s" id="lil-payments-input-date-start" />' .
                '<input type="hidden" value="%2$s" id="lil-payments-input-date-end" />',
                $filter['start'],
                $filter['end']
            );
            $title = __d(
                'lil_expenses',
                'Payments {0} {1} from {2} to {3}',
                [$fromtoLink, $accountsLink, $start_link, $end_link]
            );
            break;
        case 'month':
            $popupMonths = ['items' => [0 => [
                'title' => __d('lil_expenses', 'Entire Year'),
                'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['month' => null])],
                'active' => empty($this->getRequest()->getQuery('month')),
            ]]];

            $months = $this->Arhint->getMonthNames();
            foreach ($months as $monthNo => $monthName) {
                $popupMonths['items'][] = [
                    'title' => $monthName,
                    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['month' => (int)$monthNo])],
                    'active' => (int)$this->getRequest()->getQuery('month') == $monthNo,
                ];
            }

            $month_link = $this->Html->link(
                isset($filter['month']) ? $months[(int)$filter['month']] : __d('lil_expenses', 'Entire Year'),
                ['action' => 'filter'],
                ['class' => 'dropdown-trigger', 'id' => 'filter-month', 'data-target' => 'dropdown-month']
            );

            $popupMonths = $this->Lil->popup('month', $popupMonths, true);

            $popupYears = ['items' => []];
            $destYear = date('Y');
            for ($i = $minYear; $i <= $destYear; $i++) {
                $popupYears['items'][] = [
                    'title' => (string)$i,
                    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['year' => (int)$i])],
                    'active' => (int)$this->getRequest()->getQuery('year') == $i,
                ];
            }
            $year_link = $this->Html->link(
                $filter['year'],
                ['action' => 'filter'],
                ['class' => 'dropdown-trigger', 'id' => 'filter-year', 'data-target' => 'dropdown-year']
            );
            $popupYears = $this->Lil->popup('year', $popupYears, true);

            $title = __d(
                'lil_expenses',
                'Payments {0} {1} for {2} {3}',
                [$fromtoLink, $accountsLink, $month_link, $year_link]
            );
            break;
    }
}

$admin_index = [
    'title_for_layout' => $title,
    'menu' => [
        'add' => [
            'title' => __d('lil_expenses', 'Add'),
            'visible' => true,
            'url' => ['action' => 'add'],
        ],
    ],
    'actions' => ['lines' => [$hiddenControls, $popupAccounts, $popupFromto, $popupYears, $popupMonths]],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'PaymentsIndex',
        ],
        'head' => ['rows' => [
            0 => [
                'columns' => [
                    'search' => [
                        'params' => ['colspan' => 1, 'class' => 'input-field'],
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
            1 => [
                'columns' => [
                    'account' => [
                        'parameters' => ['class' => 'left-align'],
                        'html' => __d('lil_expenses', 'Account'),
                    ],
                    'date' => [
                        'parameters' => ['class' => 'center-align'],
                        'html' => $this->Paginator->sort('dat_happened', __d('lil_expenses', 'Date')),
                    ],
                    'descript' => [
                        'html' => __d('lil_expenses', 'Description'),
                    ],
                    'payment' => [
                        'parameters' => ['class' => 'right-align'],
                        'html' => $this->Paginator->sort('amount', __d('lil_expenses', 'Payment')),
                    ],
                ]
            ]
        ]],
        'foot' => ['rows' => [['columns' => [
            'title' => [
                'parameters' => ['colspan' => '3', 'class' => 'right-align'],
                'html' => __d('lil_expenses', 'Recapitulation') . ':',
            ],
            'recap' => [
                'parameters' => ['class' => 'right-align'],
                'html' => '',
            ],
         ]]]],
    ],
];

$total_positive = 0;
$total_negative = 0;
if (!empty($payments)) {
    foreach ($payments as $p) {
        if ($p->amount < 0) {
            $total_negative += $p->amount;
        } else {
            $total_positive += $p->amount;
        }

        $admin_index['table']['body']['rows'][] = [
        'data' => $p,
        'columns' => [
            'account' => h($p->payments_account->title ?? __d('lil_expenses', 'N/A')),
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Arhint->calendarDay($p->dat_happened),
            ],
            'descript' => $this->Html->link($p->descript ?: __d('lil_expenses', 'N/A'), [
                'action' => 'edit',
                $p->id,
            ]),
            'payment' => [
                'parameters' => ['class' => $p->amount < 0 ? 'right-align negative' : 'right-align positive'],
                'html' => $this->Number->precision($p->amount, 2),
            ],
        ],
        ];
    }
}
$admin_index['table']['foot']['rows'][0]['columns']['recap']['html'] =
    '<span>' . $this->Number->precision($total_positive, 2) . '</span>' .
    '<span> / </span>' .
    '<span class="negative">' . $this->Number->precision($total_negative, 2) . '</span>';

echo $this->Lil->index($admin_index, 'LilExpenses.Payments.index');

?>

<script type="text/javascript">
    var startEndUrl = "<?php echo Router::url([
        'action' => 'index',
        'filter' => array_replace_recursive($filter, ['start' => '__start__', 'end' => '__end__']),
    ]); ?>";

    var searchUrl = "<?php echo Router::url([
        'plugin' => 'LilExpenses',
        'controller' => 'payments',
        'action' => 'index',
        '?' => array_merge($this->request->getQuery(), ['search' => '__term__']),
    ]); ?>";

    function filterByDate(dateText, startOrEnd) {
        var rx_start = new RegExp("__start__", "i");
        var rx_end = new RegExp("__end__", "i");
        if (startOrEnd == 'start') {
            rpl_start = dateText;
            rpl_end = $('#lil-payments-input-date-end').val();
        } else {
            rpl_start = $('#lil-payments-input-date-start').val();
            rpl_end = dateText;
        }
        document.location.href = startEndUrl.replace(rx_start, rpl_start).replace(rx_end, rpl_end);
    }

    $(document).ready(function() {
        ////////////////////////////////////////////////////////////////////////////////////////////
        // Filter for invoices
        $("#SearchBox").on("input", function(e) {
            var rx_term = new RegExp("__term__", "i");
            $.get(searchUrl.replace(rx_term, encodeURIComponent($(this).val())), function(response) {
                let tBody = response.substring(response.indexOf("<tbody>")+7, response.indexOf("</tbody>"));
                $("#PaymentsIndex tbody").html(tBody);

                let tFoot = response.substring(response.indexOf("<tfoot>")+7, response.indexOf("</tfoot>"));
                $("#PaymentsIndex tfoot").html(tFoot);

                let paginator = response.substring(
                    response.indexOf("<ul class=\"paginator\">")+22,
                    response.indexOf("</ul>", response.indexOf("<ul class=\"paginator\">"))
                );
                $("#PaymentsIndex ul.paginator").html(paginator);
            });
        });

        // dates picker
        $("#lil-payments-input-date-start").datepicker({
            dateFormat: 'yy-mm-dd',
            onSelect: function(dateString, inst) {
                filterByDate(dateString, 'start');
            },
            beforeShow: function(input, inst) {
                var pos_start = $("#lil-payments-link-date-start").position();
                inst.dpDiv.css({'marginLeft': pos_start.left - 20, 'marginTop': '-15px'});
            }
        });
        $("#lil-payments-link-date-start").click(function() {
            $("#lil-payments-input-date-start").datepicker('show');
            return false;
        });

        $("#lil-payments-input-date-end").datepicker({
            dateFormat: 'yy-mm-dd',
            onSelect: function(dateString, inst) {
                filterByDate(dateString, 'end');
            },
            beforeShow: function(input, inst) {
                var pos_end = $("#lil-payments-link-date-end").position();
                inst.dpDiv.css({'marginLeft': pos_end.left - 20, 'marginTop': '-15px'});
            }
        });
        $("#lil-payments-link-date-end").click(function() {
            $("#lil-payments-input-date-end").datepicker('show');
            return false;
        });
    });
</script>
