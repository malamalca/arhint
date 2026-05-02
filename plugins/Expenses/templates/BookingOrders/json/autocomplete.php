<?php
$ret = [];
foreach ($bookingOrders as $bo) {
    $ret[] = [
        'id' => $bo->id,
        'text' => h((string)$bo),
        'value' => $bo,
    ];
}

echo json_encode($ret);
