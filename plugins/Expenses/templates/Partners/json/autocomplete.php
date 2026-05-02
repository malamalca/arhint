<?php
$ret = [];
foreach ($partners as $partner) {
    $title = $partner->contact->title ?? '';
    $ret[] = [
        'id'    => $partner->id,
        'text'  => h($title),
        'value' => $title,
    ];
}

echo json_encode($ret);
