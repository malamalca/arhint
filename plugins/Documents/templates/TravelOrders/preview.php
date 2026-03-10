<?php
/**
 * @var \App\View\AppView $this
 * @var string|null $id
 * @var string|null $name
 */
use Cake\Routing\Router;

$this->set('head_for_layout', false);

if (empty($id)) {
    $name = __d('documents', 'Travel Order Preview');
    $action = Router::url(['action' => 'export', 'travel-orders.pdf', '?' => $filter ?? []], true);
} else {
    if (empty($name)) {
        $name = $id;
    }
    $name = sprintf(__d('documents', 'Travel Order #%1$s'), h($name));
    $action = Router::url(['action' => 'export', $id, strtr($name, '/', '-') . '.pdf'], true);
}

$invoicePreview = [
    'title_for_layout' => $name,
    'menu' => [
        'back' => empty($id) ? null : [
            'title' => __d('documents', 'Back'),
            'visible' => true,
            'url' => [
                'action' => 'view',
                $id,
            ],
        ],
    ],
    'panels' => [
        sprintf('<iframe id="invoice-view" src="%s"></iframe>', $action),
    ],
];

echo $this->Lil->panels($invoicePreview, 'Documents.TravelOrders.preview');

$this->Lil->jsReady('$("#invoice-view").height(window.innerHeight - $("#invoice-view").offset().top - 30);');
