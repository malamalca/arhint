<?php
$ret = [];
foreach ($items as $item) {
    $value = $item->no . ' – ' . $item->title;
    $ret[] = [
        'id' => $item->id,
        'text' => h($value),
        'value' => $value,
    ];
}

echo json_encode($ret);
