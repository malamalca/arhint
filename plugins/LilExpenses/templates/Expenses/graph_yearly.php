<?php

use Cake\Routing\Router;

// popup for expenses/income
$expenseKinds = ['income' => __d('lil_expenses', 'Income'), 'expenses' => __d('lil_expenses', 'Expenses')];

$kindLink = $this->Html->link(
    !empty($kind) ? $expenseKinds[$kind] : __d('lil_expenses', 'Income and Expenses'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-kind', 'data-target' => 'dropdown-kind']
);
$popupKind = ['items' => [
    [
        'title' => __d('lil_expenses', 'Income'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['kind' => 'income'])],
        'active' => $this->getRequest()->getQuery('kind') == 'income',
    ],
    [
        'title' => __d('lil_expenses', 'Expenses'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['kind' => 'expenses'])],
        'active' => $this->getRequest()->getQuery('kind') == 'expenses',
    ],
]];
$popupKind = $this->Lil->popup('kind', $popupKind, true);

// popup for years
$popupYears = ['items' => []];
$destYear = (int)date('Y');
$minYear = 2015;
for ($i = $destYear; $i >= $minYear; $i--) {
    $popupYears['items'][] = [
        'title' => (string)$i,
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['year' => (int)$i])],
        'active' => (int)$this->getRequest()->getQuery('year') == $i,
    ];
}
$yearLink = $this->Html->link(
    $year,
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-year', 'data-target' => 'dropdown-year']
);
$popupYears = $this->Lil->popup('year', $popupYears, true);

// page title
$title = __d('lil_expenses', 'GRAPH: {0} for {1}', [$kindLink, $yearLink]);

$graphPanels = [
    'title_for_layout' => $title,
    'actions' => ['lines' => [$popupKind, $popupYears]],
    'panels' => [
        'graph' => [
            'lines' => [
                '<canvas id="expensesChart" height="400" style="width: 100% !important;"></canvas>',
            ],
        ],
    ],
];

echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js');
echo $this->Lil->panels($graphPanels, 'LilExpenses.Expenses.graph_yearly');
?>

<script type="text/javascript">
$(document).ready(function() {
    var year = <?= $year ?>;
    var expensesUrl = "<?= Router::url(['action' => 'index', '?' => ['type' => $kind, 'year' => '__year__', 'month' => '__month__']], true) ?>";
    var ctx = document.getElementById('expensesChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?= '"' . implode('", "', $this->Arhint->getMonthNames()) . '"' ?>],
            datasets: [
                {
                    label: "<?= $year ?>",
                    data: [<?= implode(', ', $data1) ?>],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: "<?= $year - 1 ?>",
                    data: [<?= implode(', ', $data2) ?>],
                    borderWidth: 1
                },
                {
                    label: "<?= $year - 2 ?>",
                    data: [<?= implode(', ', $data3) ?>],
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });

    $("#expensesChart").on("click", function(e) {
        let activePoints = myChart.getElementAtEvent(e);

        if (activePoints.length > 0) {
            let clickedDatasetIndex = activePoints[0]._datasetIndex;
            let clickedElementindex = activePoints[0]._index;

            let rx_year = new RegExp("__year__", "i");
            let rx_month = new RegExp("__month__", "i");
            let url = expensesUrl
                .replace(rx_year, year - clickedDatasetIndex)
                .replace(rx_month, clickedElementindex + 1);

            window.location.href = url;
        }
    });

});
</script>
