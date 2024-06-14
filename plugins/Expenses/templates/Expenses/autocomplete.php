<?php
$this->loadHelper('Expenses.LilExpense');

$ret = [];
foreach ($expenses as $exp) {
    $ret[] = [
        'id' => $exp->id,
        'text' => $this->LilExpense->label($exp),
        'value' => $this->LilExpense->title($exp),
        'icon' => $this->LilExpense->icon($exp),
        'title' => $exp->title,
        'total' => $exp->total,
    ];
}

echo json_encode($ret);
