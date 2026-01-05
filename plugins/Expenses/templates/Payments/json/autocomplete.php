<?php
$this->loadHelper('Expenses.LilExpense');

$ret = [];
foreach ($payments as $p) {
    $ret[] = [
        'id' => $p->id,
        'text' =>
            '<span class="ac-payment-date">' . (string)$p->dat_happened . '</span>' .
            '<span class="ac-payment-descript">' . $p->descript . '</span>' .
            '<span class="ac-payment-amount">' . $this->Number->currency($p->amount) . '</span>',
        'value' => $p->descript,
        'icon' => '',
        'descript' => $p->descript,
        'amount' => $p->amount,
        'dat_happened' => $p->dat_happened,
    ];
}

echo json_encode($ret);
