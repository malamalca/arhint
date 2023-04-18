<?php

use Cake\Routing\Router;

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
$title = __d('expenses', 'GRAPH: {0}', $yearLink);

$graphPanels = [
    'title_for_layout' => $title,
    'actions' => ['lines' => [$popupYears]],
    'panels' => [
        'graph' => [
            'lines' => [
                '<canvas id="expensesChart" height="400" style="width: 100% !important;"></canvas>',
            ],
        ],
    ],
];

echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js');
echo $this->Lil->panels($graphPanels, 'Expenses.Expenses.graph_expenses');
?>

<script type="text/javascript">
$(document).ready(function() {
    var year = <?= $year ?>;
    var expensesUrl = "<?= Router::url([
        'action' => 'index',
        '?' => ['year' => '__year__', 'month' => '__month__'],
    ], true) ?>";
    var ctx = document.getElementById('expensesChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?= '"' . implode('", "', $this->Arhint->getMonthNames()) . '"' ?>],
            datasets: [
                {
                    label: "<?= $year ?>",
                    data: [<?= implode(', ', $data1) ?>],
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    fill: false,
                },
                {
                    label: "<?= $year - 1 ?>",
                    data: [<?= implode(', ', $data2) ?>],
                    borderColor: 'rgba(99, 255, 132, 1)',
                    borderWidth: 1,
                    fill: false,
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
