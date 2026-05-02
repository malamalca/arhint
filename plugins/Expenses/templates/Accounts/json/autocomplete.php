<?php
$ret = [];
foreach ($accounts as $account) {
    $ret[] = [
        'id'    => $account->id,
        'text'  => h((string)$account),
        'value' => (string)$account,
        'code'  => $account->code,
        'name'  => $account->name,
    ];
}

echo json_encode($ret);
