<?php

$this->set('head_for_layout', false);

$invoicePreview = [
    'title_for_layout' => __d('documents', 'Invoice validation'),
    'menu' => [
        'edit' => empty($id) ? null : [
            'title' => __d('documents', 'Back'),
            'visible' => true,
            'url' => [
                'action' => 'view',
                $id,
            ],
        ],
    ],
    'panels' => [
       'errors' => ['lines' => []],
    ],
];

if (empty($errors)) {
    $invoicePreview['panels']['errors']['lines'][] = __d('documents', 'SUCCESS! No errors found!');
} else {
    foreach ($errors as $error) {
        $invoicePreview['panels']['errors']['lines'][] = '<div>Line ' . $error->line . ': ' . $error->message . '</div>';
    }
}

echo $this->Lil->panels($invoicePreview, 'Documents.Invoices.validate');
