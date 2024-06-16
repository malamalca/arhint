<?php

use Cake\I18n\Date;
use Cake\Routing\Router;

$linkUnfinished = $this->Html->link(
    !empty($filter['onlyunfinished']) ? __d('expenses', 'Unfinished') : __d('expenses', 'All Payments'),
    ['action' => 'filter'],
    ['class' => 'dropdown-trigger', 'id' => 'filter-unfinished', 'data-target' => 'dropdown-unfinished']
);

$popupUnfinished = ['items' => [[
        'title' => __d('expenses', 'All Payments'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['onlyunfinished' => null])],
        'active' => empty($filter['onlyunfinished']),
    ], [
        'title' => __d('expenses', 'Unfinished Payments'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['onlyunfinished' => 1])],
        'active' => $this->getRequest()->getQuery('onlyunfinished') == '1',
    ],
]];
$popupUnfinished = $this->Lil->popup('unfinished', $popupUnfinished, true);

$title = __d('expenses', 'IMPORT: SepaXML - Step2 - {0}', [$linkUnfinished]);

$report = [
    'title_for_layout' => $title,
    'menu' => [
        'clear' => [
            'title' => __d('expenses', 'Upload file'),
            'visible' => true,
            'url' => ['?' => ['clearcache' => 1]],
        ],
    ],
    'actions' => ['lines' => [$popupUnfinished]],
    'panels' => [
        'payments' => ['table' => [
            'parameters' => ['class' => 'striped', 'id' => 'import-sepa-step2'],
            'head' => ['rows' => [['columns' => [
                'date' => [
                    'parameters' => ['class' => 'center-align'],
                    'html' => __d('expenses', 'Date'),
                ],
                'amount' => [
                    'parameters' => ['class' => 'right-align'],
                    'html' => __d('expenses', 'Amount'),
                ],

                'descript' => [
                    'parameters' => ['class' => 'left-align'],
                    'html' => __d('expenses', 'Descript'),
                ],

                'client' => [
                    'parameters' => ['class' => 'left-align'],
                    'html' => __d('expenses', 'Client'),
                ],

                'net_total' => [
                    'parameters' => ['class' => 'left-align'],
                    'html' => __d('expenses', 'Ref'),
                ],

                'action' => [
                    'parameters' => ['class' => 'left-align'],
                    'html' => __d('expenses', 'Action'),
                ],
            ]]]],
            'body' => ['rows' => []],
            'foot' => ['rows' => [['columns' => [
                'first' => ['parameters' => [], 'html' => ''],
                'recap' => ['parameters' => ['colspan' => 5], 'html' => ''],

            ]]]],
        ]],
    ],
];

$popupAction = [
    'items' => [
        [
            'title' => __d('expenses', 'Link with Existing Payment'),
            'params' => ['id' => 'popupItem-LinkPayment', 'class' => 'nowrap'],
        ], [
            'title' => __d('expenses', 'Pay an Existing Expense'),
            'params' => ['id' => 'popupItem-PayExpense', 'class' => 'nowrap'],
        ], [
            'title' => __d('expenses', 'Create a New Expense'),
            'params' => ['id' => 'popupItem-AddExpense', 'class' => 'nowrap'],
        ],
    ],
];
echo $this->Lil->popup('action', $popupAction, true);

$jsPaymentData = '';
$total_positive = 0;
$total_negative = 0;
foreach ($importedPayments as $p) {
    if (empty($p['payment_id']) || empty($filter['onlyunfinished'])) {
        if ($p['amount'] < 0) {
            $total_negative += $p['amount'];
        } else {
            $total_positive += $p['amount'];
        }

        $pAction = '<button class="sepa-import-action btn-small dropdown-trigger" type="button" ' .
            'data-target="dropdown-action" data-payment="p%s">▼</button>';

        if (!empty($p['payment_id'])) {
            $pAction = '';
        }

        $report['panels']['payments']['table']['body']['rows'][] = [
            'columns' => [
                'date' => [
                    'parameters' => ['class' => 'center-align'],
                    'html' => (string)Date::parseDate($p['date'], 'yyyy-MM-dd'),
                ],
                'amount' => [
                    'parameters' => ['class' => 'right-align' . ($p['kind'] == 'DBIT' ? ' negative' : '')],
                    'html' => $this->Number->currency($p['amount']),
                ],
                'descript' => $p['descr'],
                'client' => $p['client'],
                'ref2' => $p['ref'],
                'action' => sprintf($pAction, $p['id']),
            ],
        ];

        $jsPaymentData .= sprintf(
            'paymentData["p%1$s"] = {id: "%1$s", date: "%2$s", amount:%3$s, descript:"%4$s"};' . PHP_EOL,
            $p['id'],
            $p['date'],
            $p['amount'],
            h($p['descr'])
        );
    }
}

$report['panels']['payments']['table']['foot']['rows'][0]['columns']['recap']['html'] =
    '<span>' . $this->Number->currency($total_positive) . '</span>' .
    '<span> / </span>' .
    '<span class="negative">' . $this->Number->currency($total_negative) . '</span>';

echo $this->Lil->panels($report, 'Expenses.Expenses.import_sepa_step2');
?>

<script type="text/javascript">
    function modifyUrl(url, data) {
        var rx_id = new RegExp("__id__", "i");
        var rx_date = new RegExp("__date__", "i");
        var rx_amount = new RegExp("__amount__", "i");
        var rx_descript = new RegExp("__descript__", "i");

        return url.replace(rx_id, data.id)
            .replace(rx_date, data.date)
            .replace(rx_amount, data.amount)
            .replace(rx_descript, encodeURIComponent(data.descript));
    }

    $(document).ready(function() {
        var popupTriggerElement = null;

        var paymentData = {};
        <?=$jsPaymentData?>

        var urlLinkPayment = "<?= Router::url([
            'controller' => 'Expenses',
            'action' => 'importSepaLink',
            '?' => ['id' => '__id__', 'date' => '__date__', 'amount' => '__amount__'],
        ]) ?>";

        var urlPayExpense = "<?= Router::url([
            'controller' => 'Payments',
            'action' => 'edit',
            '?' => [
                'sepa_id' => '__id__',
                'date' => '__date__',
                'amount' => '__amount__',
                'descript' => '__descript__',
            ]]) ?>";

        var urlAddExpense = "<?= Router::url([
            'controller' => 'Expenses',
            'action' => 'edit',
            '?' => ['sepa_id' => '__id__', 'date' => '__date__', 'total' => '__amount__', 'title' => '__descript__'],
            ]) ?>";

        // onClick :: dropdown arrow
        $(".sepa-import-action").click(function() {
            popupTriggerElement = $(this);
        });

        // onClick :: popup items
        $("#popupItem-LinkPayment").modalPopup({
            title: "<?=__d('expenses', 'Link with existing Payment');?>",
            processSubmit: true,
            onBeforeRequest: function() {
                let popUpinstance = M.Dropdown.getInstance(popupTriggerElement.get(0));
                popUpinstance.close();
                return modifyUrl(urlLinkPayment, paymentData[$(popupTriggerElement).data("payment")]);
            }
        })

        $("#popupItem-PayExpense").modalPopup({
            title: "<?=__d('expenses', 'Add Payment to Existing Expense');?>",
            processSubmit: true,
            onBeforeRequest: function() {
                let popUpinstance = M.Dropdown.getInstance(popupTriggerElement.get(0));
                popUpinstance.close();
                return modifyUrl(urlPayExpense, paymentData[$(popupTriggerElement).data("payment")]);
            }
        })

        $("#popupItem-AddExpense").modalPopup({
            title: "<?=__d('expenses', 'Add new Expense');?>",
            processSubmit: true,
            onBeforeRequest: function() {
                let popUpinstance = M.Dropdown.getInstance(popupTriggerElement.get(0));
                popUpinstance.close();
                return modifyUrl(urlAddExpense, paymentData[$(popupTriggerElement).data("payment")]);
            }
        })
    });
</script>
