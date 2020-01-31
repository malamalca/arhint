<?php
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Inflector;

$this->set('head_for_layout', false);

if (empty($id)) {
    $action = ['action' => 'export.pdf', 'download' => 0, 'filter' => $this->getRequest()->getQuery('filter')];
} else {
    if (empty($name)) {
        $name = $id;
    }
    $action = ['action' => 'export', $id, $name . '.pdf'];
}

$invoice_preview = [
    'title_for_layout' => sprintf(__d('lil_invoices', 'Invoice #%1$s'), h($name)),
    'menu' => [
        'edit' => [
            'title' => __d('lil_invoices', 'Back'),
            'visible' => true,
            'url' => [
                'action' => 'view',
                $id,
            ],
        ],
    ],
    //'entity' => $invoice,
    'panels' => [
       sprintf('<iframe id="invoice-view" src="%s"></iframe>', Router::url($action)),
    ],
];

echo $this->Lil->panels($invoice_preview, 'LilInvoices.Invoices.preview');

$this->Lil->jsReady('$("#invoice-view").height(window.innerHeight - $("#invoice-view").offset().top - 30);');
