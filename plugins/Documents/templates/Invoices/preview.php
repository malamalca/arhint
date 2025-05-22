<?php
use Cake\Routing\Router;

$this->set('head_for_layout', false);

if (empty($id)) {
    $action = ['action' => 'export.pdf', 'download' => 0, 'filter' => $this->getRequest()->getQuery('filter')];
    $name = __d('documents', 'Invoice Preview');

    $action = Router::url(['action' => 'export', 'invoices.pdf', '?' => $filter], true);
} else {
    if (empty($name)) {
        $name = $id;
    }
    $name = sprintf(__d('documents', 'Invoice #%1$s'), h($name));
    $action = Router::url(['action' => 'export', $id, strtr($name, '/', '-') . '.pdf'], true);
}

$invoicePreview = [
    'title_for_layout' => $name,
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
    //'entity' => $document,
    'panels' => [
       sprintf('<iframe id="invoice-view" src="%s"></iframe>', $action),
    ],
];

echo $this->Lil->panels($invoicePreview, 'Documents.Invoices.preview');

$this->Lil->jsReady('$("#invoice-view").height(window.innerHeight - $("#invoice-view").offset().top - 30);');
