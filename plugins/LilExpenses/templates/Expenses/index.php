<?php

use Cake\Routing\Router;

$this->loadHelper('LilExpenses.LilExpense');

$fromto = ['income' => __d('lil_expenses', 'Income'), 'expenses' => __d('lil_expenses', 'Expenses')];

$kindLink = $this->Html->link(
    !empty($filter['type']) ? $fromto[$filter['type']] : __d('lil_expenses', 'Income and Expenses'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-kind', 'data-target' => 'dropdown-kind']
);
$popupKind = ['items' => [[
        'title' => __d('lil_expenses', 'Income and Expenses'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['type' => null])],
        'active' => empty($filter['type']),
    ], [
        'title' => __d('lil_expenses', 'Income'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['type' => 'income'])],
        'active' => $this->getRequest()->getQuery('type') == 'income',
    ], [
        'title' => __d('lil_expenses', 'Expenses'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['type' => 'expenses'])],
        'active' => $this->getRequest()->getQuery('type') == 'expenses',
    ],
]];
$popupKind = $this->Lil->popup('kind', $popupKind, true);

$title = __d('lil_expenses', '{0}', [$kindLink]);
$hiddenControls = '';

if (isset($filter['span'])) {
    switch ($filter['span']) {
        case 'fromto':
            $start_link = $this->Html->link(
                $this->Time->format($filter['start'], $dateFormat),
                ['action' => 'filter'],
                ['id' => 'lil-expenses-link-date-start']
            );
            $end_link = $this->Html->link(
                $this->Time->format($filter['end'], $dateFormat),
                ['action' => 'filter'],
                ['id' => 'lil-expenses-link-date-end']
            );
            $hiddenControls = sprintf(
                '<input type="hidden" value="%1$s" id="lil-expenses-input-date-start" />' .
                '<input type="hidden" value="%2$s" id="lil-expenses-input-date-end" />',
                $filter['start'],
                $filter['end']
            );
            $title = __d(
                'lil_expenses',
                'Expenses {0} from {1} to {2}',
                [$fromto_link, $start_link, $end_link]
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

            $monthLink = $this->Html->link(
                isset($filter['month']) ? $months[(int)$filter['month']] : __d('lil_expenses', 'Entire Year'),
                ['action' => 'filter'],
                ['class' => 'dropdown-trigger', 'id' => 'filter-month', 'data-target' => 'dropdown-month']
            );

            $popupMonths = $this->Lil->popup('month', $popupMonths, true);

            $popupYears = ['items' => []];
            $destYear = date('Y');
            for ($i = $destYear; $i >= $minYear; $i--) {
                $popupYears['items'][] = [
                    'title' => (string)$i,
                    'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['year' => (int)$i])],
                    'active' => (int)$this->getRequest()->getQuery('year') == $i,
                ];
            }
            $yearLink = $this->Html->link(
                $filter['year'],
                ['action' => 'filter'],
                ['class' => 'dropdown-trigger', 'id' => 'filter-year', 'data-target' => 'dropdown-year']
            );
            $popupYears = $this->Lil->popup('year', $popupYears, true);

            $title = __d(
                'lil_expenses',
                '{0} for {1} {2}',
                [$kindLink, $monthLink, $yearLink]
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
    'actions' => ['lines' => [$hiddenControls, $popupKind, $popupMonths, $popupYears]],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'ExpensesIndex',
        ],
        'head' => ['rows' => [['columns' => [
            'icon' => [
                'parameters' => ['class' => 'center-align'],
                'html' => __d('lil_expenses', 'Kind'),
            ],
            'descript' => [
                'parameters' => ['class' => 'left-align'],
                'html' => __d('lil_expenses', 'Decription'),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => __d('lil_expenses', 'Date'),
            ],
            'net_total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_expenses', 'Net Total'),
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => __d('lil_expenses', 'Total'),
            ],
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'title' => [
                'parameters' => ['colspan' => '3', 'class' => 'right-align'],
                'html' => __d('lil_expenses', 'Recapitulation') . ':',
            ],
            'net_total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => '',
            ],
            'total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => '',
            ],
         ]]]],
    ],
];

$saldo = 0;
$netSaldo = 0;
$positive = 0;
$negative = 0;
if (!empty($expenses)) {
    foreach ($expenses as $e) {
        $saldo += $e->total;
        $netSaldo += $e->net_total;

        if ($e->total > 0) {
            $positive += $e->net_total;
        } else {
            $negative += $e->net_total;
        }

        $admin_index['table']['body']['rows'][] = [
        'data' => $e,
        'columns' => [
            'icon' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->LilExpense->icon($e),
            ],
            'descript' => $this->LilExpense->link($e, false),
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Arhint->calendarDay($e->dat_happened),
            ],

            'net_total' => [
                'parameters' => ['class' => $e->net_total < 0 ? 'right-align negative' : 'right-align positive'],
                'html' => $this->Number->precision($e->net_total, 2),
            ],
            'total' => [
                'parameters' => ['class' => $e->total < 0 ? 'right-align negative' : 'right-align positive'],
                'html' => $this->Number->precision($e->total, 2),
            ],
        ],
        ];
    }
}

$admin_index['table']['foot']['rows'][0]['columns']['net_total']['html'] = $this->Number->currency($netSaldo) .
    '<br />' .
    $this->Number->precision($positive, 2) .
    '/<span class="negative">' .
    $this->Number->currency($negative) .
    '</span>';
$admin_index['table']['foot']['rows'][0]['columns']['total']['html'] = $this->Number->currency($saldo);

echo $this->Lil->index($admin_index, 'LilExpenses.Expenses.index');

?>

<script type="text/javascript">
    var tmtrUrlStartEnd = "<?php echo Router::url([
        'action' => 'index',
        'filter' => array_replace_recursive($filter, ['start' => '[[start]]', 'end' => '[[end]]']),
    ]); ?>";

    function filterByDate(dateText, startOrEnd) {
        var rx_start = new RegExp("(\\%5B){2}start(\\%5D){2}", "i");
        var rx_end = new RegExp("(\\%5B){2}end(\\%5D){2}", "i");
        if (startOrEnd == 'start') {
            rpl_start = dateText;
            rpl_end = $('#lil-expenses-input-date-end').val();
        } else {
            rpl_start = $('#lil-expenses-input-date-start').val();
            rpl_end = dateText;
        }
        document.location.href = tmtrUrlStartEnd.replace(rx_start, rpl_start).replace(rx_end, rpl_end);
    }

    $(document).ready(function() {
        // dates picker
        $("#lil-expenses-input-date-start").datepicker({
            format: 'yy-mm-dd',
            setDefaultDate: true,
            onSelect: function(dateString, inst) {
                filterByDate(dateString, 'start');
            },
            beforeShow: function(input, inst) {
                var pos_start = $("#lil-expenses-link-date-start").position();
                inst.dpDiv.css({'marginLeft': pos_start.left - 20, 'marginTop': '-15px'});
            }
        });
        $("#lil-expenses-link-date-start").click(function() {
            $("#lil-expenses-input-date-start").datepicker('show');
            return false;
        });

        $("#lil-expenses-input-date-end").datepicker({
            format: 'yy-mm-dd',
            setDefaultDate: true,
            onSelect: function(dateString, inst) {
                filterByDate(dateString, 'end');
            },
            beforeShow: function(input, inst) {
                var pos_end = $("#lil-expenses-link-date-end").position();
                inst.dpDiv.css({'marginLeft': pos_end.left - 20, 'marginTop': '-15px'});
            }
        });
        $("#lil-expenses-link-date-end").click(function() {
            $("#lil-expenses-input-date-end").datepicker('show');
            return false;
        });
    });
</script>
