<?php
$graphPanels = [
    'title_for_layout' => __d('lil_expenses', 'GRAPH: Yearly Income/Expenses'),
    'panels' => [
        'graph' => [
            'lines' => [
                '<canvas id="myChart" height="400" style="width: 100% !important;"></canvas>'
            ]
        ]
    ]
];

echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js');
echo $this->Lil->panels($graphPanels, 'LilExpenses.Expenses.graph_yearly');
?>

<script type="text/javascript">
var ctx = document.getElementById('myChart').getContext('2d');
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
</script>
