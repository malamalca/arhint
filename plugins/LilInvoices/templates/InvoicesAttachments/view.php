<?php
use Cake\Routing\Router;

$this->set('head_for_layout', false);

$action = Router::url(['action' => 'download', $a->id, 0, $a->original], true);

$attachmentPreview = [
    'title_for_layout' => __d('lil_invoices', 'Attachment Preview'),
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
    'entity' => $a,
    'panels' => [
       sprintf('<iframe id="invoice-view" src="%s"></iframe>', $action),
    ],
];

echo $this->Lil->panels($attachmentPreview, 'LilInvoices.InvoicesAttachments.view');

$this->Lil->jsReady('$("#invoice-view").height(window.innerHeight - $("#invoice-view").offset().top - 30);');
