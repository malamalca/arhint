<?php
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Text;

$document->client = $document->documents_counter->kind == 'issued' ? $document->receiver : $document->issuer;

$documentView = [
    'title_for_layout' => __d(
        'documents',
        '{3} #{0} <span class="light nowrap">({1} :: {2})</span>',
        h($document->no),
        h($document->documents_counter->title),
        h($document->counter),
        h($document->tpl_title)
    ),
    'menu' => [
        'edit' => [
            'title' => __d('documents', 'Edit'),
            'visible' => $document->documents_counter->active && $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'action' => 'edit',
                $document->id,
            ],
        ],
        'duplicate' => [
            'title' => __d('documents', 'Duplicate'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'action' => 'edit',
                '?' => ['duplicate' => $document->id],
            ],
        ],
        'attach' => [
            'title' => __d('documents', 'Attach'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'submenu' => [
                'attachment' => [
                    'title' => __d('documents', 'File'),
                    'visible' => $this->Lil->userLevel('admin'),
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'DocumentsAttachments',
                        'action' => 'add',
                        $document->id,
                    ],
                    'params' => [
                        'id' => 'AttachFile',
                    ],
                ],
                'scan' => [
                    'title' => __d('documents', 'Scan'),
                    'visible' => $this->Lil->userLevel('admin'),
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'DocumentsAttachments',
                        'action' => 'add',
                        $document->id,
                        'scan'
                    ],
                    'params' => [
                        'id' => 'AttachScan',
                    ],
                ],
                'link' => [
                    'title' => __d('documents', 'Linked Document'),
                    'visible' => true,
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'documents-links',
                        'action' => 'link',
                        $document->id,
                    ],
                    'params' => [
                        'id' => 'AttachLink',
                    ],
                ],
            ],
        ],
        'delete' => [
            'title' => __d('documents', 'Delete'),
            'visible' => $document->documents_counter->active && $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'action' => 'delete',
                $document->id,
            ],
            'params' => [
                'confirm' => __d('documents', 'Are you sure you want to delete this document?'),
            ],
        ],
        'print' => [
            'title' => __d('documents', 'Print'),
            'visible' => true,
            'url' => [
                'action' => 'preview',
                $document->id,
                $document->no,
            ],
        ],
        'email' => [
            'title' => __d('documents', 'Send'),
            'visible' => true,
            'url' => [
                'action' => 'email',
                '?' => ['id' => $document->id],
            ],
            'params' => [
                'id' => 'EmailDocument',
            ],
        ],
        'export' => [
            'title' => __d('documents', 'Export'),
            'visible' => true,
            'submenu' => [
                'pdf' => [
                    'title' => __d('documents', 'PDF'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $document->id,
                        Text::slug($document->title) . '.pdf',
                        '?' => ['download' => 1],
                    ],
                ],
                'sepaxml' => !$document->isInvoice() ? null : [
                    'title' => __d('documents', 'Sepa XML'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $document->id,
                        Text::slug($document->title) . '.sepa.xml',
                        '?' => ['download' => 1],
                    ],
                ],
                'eslog' => !$document->isInvoice() ? null : [
                    'title' => __d('documents', 'eSlog'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $document->id,
                        Text::slug($document->title) . '.eslog.xml',
                        '?' => ['download' => 1],
                    ],
                ],
                'eslog20' => !$document->isInvoice() ? null : [
                    'title' => __d('documents', 'eSlog 2.0'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $document->id,
                        Text::slug($document->title) . '.eslog20.xml',
                        '?' => ['download' => 1],
                    ],
                ],
            ],
        ],
        'settings' => [
            'title' => __d('documents', 'Settings'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'submenu' => [
                'templates' => [
                    'title' => __d('documents', 'Templates'),
                    'visible' => true,
                    'url' => [
                        'action' => 'templates',
                        $document->id,
                    ],
                    'params' => [
                        'id' => 'EditTemplates',
                    ],
                ],
                'issuer' => empty($document->issuer) ? null : [
                    'title' => __d('documents', 'Edit Issuer'),
                    'visible' => true,
                    'url' => [
                        'controller' => 'DocumentsClients',
                        'action' => 'edit',
                        $document->issuer->id ?? '',
                    ],
                    'params' => [
                        'id' => 'EditIssuer',
                    ],
                ],
                'receiver' => empty($document->receiver) ? null : [
                    'title' => __d('documents', 'Edit Receiver'),
                    'visible' => true,
                    'url' => [
                        'controller' => 'DocumentsClients',
                        'action' => 'edit',
                        $document->receiver->id ?? '',
                    ],
                    'params' => [
                        'id' => 'EditReceiver',
                    ],
                ],
                'buyer' => empty($document->buyer) ? null : [
                    'title' => __d('documents', 'Edit Buyer'),
                    'visible' => true,
                    'url' => [
                        'controller' => 'DocumentsClients',
                        'action' => 'edit',
                        $document->buyer->id ?? '',
                    ],
                    'params' => [
                        'id' => 'EditBuyer',
                    ],
                ],
            ],
        ],
    ],
    'entity' => $document,
    'panels' => [
        'title' => [
            'id' => 'document-title',
            'lines' => [[
                'label' => __d('documents', 'Title') . ':',
                'text' => h($document->title),
            ]],
        ],
        'client' => [
            'id' => 'document-client',
            'lines' => [
                'client.title' => [
                    'label' => ($document->documents_counter->kind == 'issued' ? __d('documents', 'Receiver') : __d('documents', 'Issuer')) . ':',
                    'text' =>
                        h($document->client->title) . '&nbsp;' . (
                        empty($document->client->contact_id) ? '' :
                            $this->Html->link(
                                $this->Html->image('/documents/img/goto.gif'),
                                [
                                    'plugin' => 'Crm',
                                    'controller' => 'contacts',
                                    'action' => 'view',
                                    $document->client->contact_id,
                                ],
                                ['escape' => false]
                            )
                        ),
                ],
                'client-address' => empty($document->client->primary_address) ? null : [
                    'label' => '&nbsp;',
                    'text' =>
                        implode(', ', array_filter([
                            h($document->client->primary_address->street),
                            h(implode(
                                ' ',
                                [
                                    $document->client->primary_address->zip,
                                    $document->client->primary_address->city,
                                ]
                            )),
                            h($document->client->primary_address->country),
                        ])),
                ],
                'client-tax_no' => empty($document->client->tax_no) ? null : [
                    'label' => '&nbsp;',
                    'text' => ($document->client->tax_status ? __d('documents', 'TAX payee no.') : __d('documents', 'TAX no.')) . ' ' .
                        h($document->client->tax_no),
                ],
            ],
        ],

        'details' => [
            'id' => 'document-details',
            'lines' => [
                0 => [
                    'label' => __d('documents', 'Date of issue') . ':',
                    'text' => (string)$document->dat_issue,
                ],
                1 => !$document->isInvoice() ? null : [
                    'label' => __d('documents', 'Service date') . ':',
                    'text' => (string)$document->dat_service,
                ],
                2 => !$document->isInvoice() ? null : [
                    'label' => __d('documents', 'Expiration date') . ':',
                    'text' => (string)$document->dat_expire,
                ],
                3 => empty($document->dat_approval) ? null : [
                    'label' => __d('documents', 'Approval date') . ':',
                    'text' => (string)$document->dat_approval,
                ],
                4 => empty($document->dat_sign) ? null : [
                    'label' => __d('documents', 'Sign date') . ':',
                    'text' => (string)$document->dat_sign,
                ],
            ],
        ],
        'project' => !Plugin::isLoaded('Projects') || empty($document->project_id) ? null : [
            'lines' => [
                [
                    'label' => __d('documents', 'Project') . ':',
                    'text' => $this->Html->link((string)$document->project, [
                        'plugin' => 'Projects',
                        'controller' => 'Projects',
                        'action' => 'view',
                        $document->project_id,
                    ]) . ' &nbsp;',
                ],
            ],
        ],
        'total' => !$document->isInvoice() ? null : [
            'id' => 'document-total',
            'lines' => [[
                'label' => __d('documents', 'Total') . ':',
                'text' => $this->Number->currency($document->total),
            ]],
        ],
    ],
];

// duplicate into counter
foreach ($counters as $cntr) {
    $documentView['menu']['duplicate']['submenu'][] = [
        'title' => $cntr->title,
        'visible' => true,
        'url' => [
            'controller' => 'Documents',
            'action' => 'edit',
            '?' => ['duplicate' => $document->id, 'counter' => $cntr->id],
        ],
    ];
}

if ($document->isInvoice()) {
    ////////////////////////////////////////////////////////////////////////////////////////////////
    // ITEMS
    $itemsBody = [];
    $itemsTotal = 0;
    $grandTotal = 0;
    foreach ($document->documents_items as $itm) {
        $itemsPrice = round($itm->price * $itm->qty, 4);
        $discount = round($itemsPrice * $itm->discount / 100, 4);
        $itemTotal = $itemsPrice - $discount;

        $lineTotal = round($itemTotal, 2) + round($itemTotal * $itm->vat_percent / 100, 2);
        $grandTotal += $lineTotal;

        $itemsBody['rows'][] = ['columns' => [
            'descript' => h($itm['descript']),
            'qty' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->precision((float)$itm->qty, 2),
            ],
            'unit' => [
                'html' => h($itm['unit']),
            ],
            'price' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($itm->price),
            ],
            'discount' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->precision((float)$itm->discount, 1),
            ],
            'item_total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($itemTotal),
            ],
            'tax' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->precision((float)$itm->vat_percent, 1),
            ],
            'line_total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($lineTotal),
            ],
        ]];
        $itemsTotal += $itemTotal;
    }

    $items = [
        'parameters' => ['cellspacing' => '0', 'cellpadding' => '0', 'id' => 'document-analytics-table'],
        'head' => [
            'rows' => [
                0 => [
                    'columns' => [
                        __d('documents', 'Description'),
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Quantity')],
                        ['parameters' => ['class' => 'left-align'], 'html' => __d('documents', 'Unit')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Price per Unit')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Discount [%]')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Unit Total')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Tax [%]')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Total with Tax')],
                    ],
                ],
            ],
        ],
        'body' => $itemsBody,
        'foot' => [
            'rows' => [
                0 => [
                    'columns' => [
                        [
                            'parameters' => ['class' => 'right-align', 'colspan' => 5],
                            'html' => __d('documents', 'Grand Total') . ':',
                        ],
                        [
                            'parameters' => ['class' => 'right-align'],
                            'html' => $this->Number->currency($itemsTotal),
                        ],
                        [
                            'html' => '&nbsp;',
                        ],
                        [
                            'parameters' => ['class' => 'right-align', 'id' => 'document-analytics-grand-total'],
                            'html' => $this->Number->currency($grandTotal),
                        ],
                    ],
                ],
            ],
        ],
    ];

    if ($document->documents_counter->kind == 'issued') {
        $documentView['panels']['items_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Analytics'));
        $documentView['panels']['items']['id'] = 'document-view-items-table';
        $documentView['panels']['items']['table'] = $items;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // TAXES
    $taxesBody = [];
    $baseTotal = 0;
    $taxTotal = 0;
    $grandTotal = 0;
    foreach ($document->documents_taxes as $itm) {
        $tax = round($itm->base * $itm->vat_percent / 100, 2);
        $lineTotal = round($itm->base + $tax, 2);

        $taxesBody['rows'][] = ['columns' => [
            'descript' => h($itm->vat_title),
            'percent' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->precision((float)$itm->vat_percent, 1),
            ],
            'base' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($itm->base),
            ],
            'tax' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($tax),
            ],
            'line_total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($lineTotal),
            ],

        ]];
        $baseTotal += round($itm->base, 2);
        $taxTotal += $tax;
        $grandTotal += $lineTotal;
    }
    $taxes = [
        'parameters' => ['cellspacing' => '0', 'cellpadding' => '0', 'id' => 'document-taxes-table', 'class' => 'index-static'],
        'head' => [
            'rows' => [
                0 => [
                    'columns' => [
                        __d('documents', 'Description'),
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'VAT [%]')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Base')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Tax')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Total')],
                    ],
                ],
            ],
        ],
        'body' => $taxesBody,
        'foot' => [
            'rows' => [
                0 => [
                    'columns' => [
                        [
                            'parameters' => ['class' => 'right-align', 'colspan' => 2],
                            'html' => __d('documents', 'Grand Total') . ':',
                        ],
                        [
                            'parameters' => ['class' => 'right-align'],
                            'html' => $this->Number->currency($baseTotal),
                        ],
                        [
                            'parameters' => ['class' => 'right-align'],
                            'html' => $this->Number->currency($taxTotal),
                        ],
                        [
                            'parameters' => ['class' => 'right-align', 'id' => 'document-analytics-grand-total'],
                            'html' => $this->Number->currency($grandTotal),
                        ],
                    ],
                ],
            ],
        ],
    ];

    if ($document->documents_counter->kind == 'received') {
        $documentView['panels']['vat_title'] = sprintf('<h3>%s</h3>', __d('documents', 'VAT Analytics'));
        $documentView['panels']['vat']['id'] = 'document-view-tax-table';
        $documentView['panels']['vat']['table'] = $taxes;
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////
// ATTACHMENTS

if (!empty($document->documents_attachments)) {
    $documentView['panels']['attachments_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Attachments'));
    $i = 0;
    foreach ($document->documents_attachments as $atch) {
        $documentView['panels']['attachments_' . $atch->id] = sprintf(
            '<div>%1$s (%2$s) %3$s</div>',
            $this->Html->link(
                $atch['original'],
                [
                    'controller' => 'documents-attachments',
                    'action' => 'view',
                    $atch->id,
                ],
                [
                    'class' => 'AttachmentPreview',
                ]
            ),
            $this->Number->toReadableSize((int)$atch->filesize),
            $this->Html->link(
                '<i class="material-icons">get_app</i>',
                [
                    'controller' => 'documents-attachments',
                    'action' => 'download',
                    $atch->id,
                    1,
                    $atch->original,
                ],
                [
                    'escape' => false,
                    'class' => 'btn btn-small btn-floating waves-effect waves-light waves-circle',
                ]
            ) . ' ' .
            $this->Html->link(
                '<i class="material-icons">delete</i>',
                [
                    'controller' => 'documents-attachments',
                    'action' => 'delete',
                    $atch->id,
                ],
                [
                    'escape' => false,
                    'confirm' => __d('documents', 'Are you sure you want to delete this attachment'),
                    'class' => 'btn btn-small btn-floating waves-effect waves-light waves-circle',
                ]
            )
        );
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////
// LINKS
if (!empty($links)) {
    $documentView['panels']['links_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Linked Documents'));
    $i = 0;
    foreach ($links as $link) {
        $documentView['panels']['links_' . $link->id] = sprintf(
            '<div>%1$s %2$s</div>',
            '#' . $link->document->no . ' :: ' . $this->Html->link(
                $link->document->title,
                [
                    'action' => 'view',
                    $link->document->id,
                ]
            ),
            $this->Html->link(
                $this->Html->image('/documents/img/remove.gif'),
                [
                    'controller' => 'DocumentsLinks',
                    'action' => 'delete',
                    $document->id, $link->id,
                ],
                [
                    'escape' => false,
                    'confirm' => __d('documents', 'Are you sure you want to delete this link?'),
                ]
            )
        );
    }
}

if (!empty($document->descript)) {
    $documentView['panels']['text_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Description'));
    $documentView['panels']['text_data'] = sprintf('<div id="document-descript-preview">%s</div>', $document->descript);
}

echo $this->Lil->panels($documentView, 'Documents.Documents.view');
?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#EditTemplates").modalPopup({title: "<?= __d('documents', 'Edit Templates') ?>"});
        $("#EmailDocument").modalPopup({title: "<?= __d('documents', 'Email Document') ?>"});

        $("#AttachFile").modalPopup({title: "<?= __d('documents', 'Attach File') ?>"});
        $("#AttachLink").modalPopup({title: "<?= __d('documents', 'Link Document') ?>"});
        $("#AttachScan").modalPopup({title: "<?= __d('documents', 'Scan Document') ?>", onClose: function() {
            if (typeof window.ws != "undefined" && window.ws) {
                ws.close();
                window.ws = null;
            }
        }});

        $("#EditIssuer").modalPopup({title: "<?= __d('documents', 'Edit Issuer') ?>"});
        $("#EditReceiver").modalPopup({title: "<?= __d('documents', 'Edit Receiver') ?>"});
        $("#EditBuyer").modalPopup({title: "<?= __d('documents', 'Edit Buyer') ?>"});

        $(".AttachmentPreview").each(function() {
            $(this).modalPopup({
                title: "<?= __d('documents', 'Attachment Preview') ?>",
                onOpen: function(popup) {
                    $(popup).height(window.innerHeight);
                    $("#document-view", popup).height($(popup).innerHeight() - 125);
                },
                onResize: function(popup) {
                    $("#document-view", popup).height($(popup).innerHeight() - 125);
                }
            });
        });
    });
</script>
