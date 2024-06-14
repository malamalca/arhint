<?php
    $ret = [];
foreach ($items as $item) {
    $ret[] = [
        'id' => $item->id,
        'text' => $item->descript,
        'label' => $item->descript,
        'value' => $item->descript,

        'vat_id' => $item->vat_id,
        'descript' => $item->descript,
        'qty' => $item->qty,
        'unit' => $item->unit,
        'price' => $item->price,
        'discount' => $item->discount,

        'vat_title' => $item->vat->descript,
        'vat_percent' => $item->vat->percent,
    ];
}

    echo json_encode($ret);
