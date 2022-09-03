<?php
use Cake\Core\Plugin;
use Cake\Utility\Text;

$document->client = $document->documents_counter->direction == 'issued' ? $document->receiver : $document->issuer;

$invoiceView = [
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
                        'Invoice',
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
                        'Invoice',
                        $document->id,
                        'scan',
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
                        'controller' => 'DocumentsLinks',
                        'action' => 'link',
                        'Invoice',
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
                'sepaxml' => [
                    'title' => __d('documents', 'Sepa XML'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $document->id,
                        Text::slug($document->title) . '.sepa.xml',
                        '?' => ['download' => 1],
                    ],
                ],
                'eslog' => [
                    'title' => __d('documents', 'eSlog'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $document->id,
                        Text::slug($document->title) . '.eslog.xml',
                        '?' => ['download' => 1],
                    ],
                ],
                'eslog20' => [
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
            'id' => 'invoice-title',
            'lines' => [[
                'label' => __d('documents', 'Title') . ':',
                'text' => h($document->title),
            ]],
        ],
        'client' => [
            'id' => 'invoice-client',
            'lines' => [
                'client.title' => [
                    'label' => ($document->documents_counter->direction == 'issued' ? __d('documents', 'Receiver') : __d('documents', 'Issuer')) . ':',
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
            'id' => 'invoice-details',
            'lines' => [
                0 => [
                    'label' => __d('documents', 'Date of issue') . ':',
                    'text' => (string)$document->dat_issue,
                ],
                1 => [
                    'label' => __d('documents', 'Service date') . ':',
                    'text' => (string)$document->dat_service,
                ],
                2 => [
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
        'total' => [
            'id' => 'invoice-total',
            'lines' => [[
                'label' => __d('documents', 'Total') . ':',
                'text' => $this->Number->currency($document->total),
            ]],
        ],
    ],
];

// duplicate into counter
foreach ($counters as $cntr) {
    $invoiceView['menu']['duplicate']['submenu'][] = [
        'title' => $cntr->title,
        'visible' => true,
        'url' => [
            'controller' => 'Invoices',
            'action' => 'edit',
            '?' => ['duplicate' => $document->id, 'counter' => $cntr->id],
        ],
    ];
}

////////////////////////////////////////////////////////////////////////////////////////////////
// ITEMS
$itemsBody = [];
$itemsTotal = 0;
$grandTotal = 0;
foreach ($document->invoices_items as $itm) {
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
    'parameters' => ['cellspacing' => '0', 'cellpadding' => '0', 'id' => 'invoice-analytics-table'],
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
                        'parameters' => ['class' => 'right-align', 'id' => 'invoice-analytics-grand-total'],
                        'html' => $this->Number->currency($grandTotal),
                    ],
                ],
            ],
        ],
    ],
];

if ($document->documents_counter->direction == 'issued') {
    $invoiceView['panels']['items_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Analytics'));
    $invoiceView['panels']['items']['id'] = 'invoice-view-items-table';
    $invoiceView['panels']['items']['table'] = $items;
}

////////////////////////////////////////////////////////////////////////////////////////////////
// TAXES
$taxesBody = [];
$baseTotal = 0;
$taxTotal = 0;
$grandTotal = 0;
foreach ($document->invoices_taxes as $itm) {
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
    'parameters' => ['cellspacing' => '0', 'cellpadding' => '0', 'id' => 'invoice-taxes-table', 'class' => 'index-static'],
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
                        'parameters' => ['class' => 'right-align', 'id' => 'invoice-analytics-grand-total'],
                        'html' => $this->Number->currency($grandTotal),
                    ],
                ],
            ],
        ],
    ],
];

if ($document->documents_counter->direction == 'received') {
    $invoiceView['panels']['vat_title'] = sprintf('<h3>%s</h3>', __d('documents', 'VAT Analytics'));
    $invoiceView['panels']['vat']['id'] = 'invoice-view-tax-table';
    $invoiceView['panels']['vat']['table'] = $taxes;
}

////////////////////////////////////////////////////////////////////////////////////////////////
// ATTACHMENTS

if (!empty($document->documents_attachments)) {
    $invoiceView['panels']['attachments_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Attachments'));
    $i = 0;
    foreach ($document->documents_attachments as $atch) {
        $invoiceView['panels']['attachments_' . $atch->id] = sprintf(
            '<div>%1$s (%2$s) %3$s</div>',
            $this->Html->link(
                $atch['original'],
                [
                    'controller' => 'DocumentsAttachments',
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
                    'controller' => 'DocumentsAttachments',
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
                    'controller' => 'DocumentsAttachments',
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
    $invoiceView['panels']['links_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Linked Documents'));
    $i = 0;
    foreach ($links as $link) {
        $invoiceView['panels']['links_' . $link->id] = sprintf(
            '<div>%1$s %2$s</div>',
            '#' . $link->invoice->no . ' :: ' . $this->Html->link(
                $link->invoice->title,
                [
                    'action' => 'view',
                    $link->invoice->id,
                ]
            ),
            $this->Html->link(
                $this->Html->image('/documents/img/remove.gif'),
                [
                    'controller' => 'DocumentsLinks',
                    'action' => 'delete',
                    $document->id,
                    $link->id,
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
    $invoiceView['panels']['text_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Description'));
    $invoiceView['panels']['text_data'] = sprintf('<div id="invoice-descript-preview">%s</div>', $document->descript);
}

echo $this->Lil->panels($invoiceView, 'Documents.Invoices.view');
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
                    $("#attachment-view", popup).height($(popup).innerHeight() - 125);
                },
                onResize: function(popup) {
                    $("#attachment-view", popup).height($(popup).innerHeight() - 125);
                }
            });
        });
    });
</script>
