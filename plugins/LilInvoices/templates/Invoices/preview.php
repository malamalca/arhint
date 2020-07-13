<?php
use Cake\Routing\Router;

$this->set('head_for_layout', false);

if (empty($id)) {
    $action = ['action' => 'export.pdf', 'download' => 0, 'filter' => $this->getRequest()->getQuery('filter')];
    $name = __d('lil_invoices', 'Invoices Preview');

    $action = Router::url(['action' => 'export', 'invoices.pdf', '?' => $filter], true);
} else {
    if (empty($name)) {
        $name = $id;
    }
    $name = sprintf(__d('lil_invoices', 'Invoice #%1$s'), h($name));
    $action = Router::url(['action' => 'export', $id, $name . '.pdf'], true);
}

$invoice_preview = [
    'title_for_layout' => $name,
    'menu' => [
        'edit' => empty($id) ? null : [
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
       sprintf('<iframe id="invoice-view" src="%s"></iframe>', $action),
    ],
];

echo $this->Lil->panels($invoice_preview, 'LilInvoices.Invoices.preview');

$this->Lil->jsReady('$("#invoice-view").height(window.innerHeight - $("#invoice-view").offset().top - 30);');
