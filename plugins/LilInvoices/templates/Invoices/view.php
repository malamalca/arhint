<?php
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Text;

$dateFormat = strtr(implode(Configure::read('Lil.dateSeparator'), str_split(Configure::read('Lil.dateFormat'))), ['Y' => 'yyyy', 'M' => 'MM', 'D' => 'dd']);
$invoice->client = $invoice->invoices_counter->kind == 'issued' ? $invoice->receiver : $invoice->issuer;

$invoiceView = [
    'title_for_layout' => __d(
        'lil_invoices',
        '{3} #{0} <span class="light nowrap">({1} :: {2})</span>',
        h($invoice->no),
        h($invoice->invoices_counter->title),
        h($invoice->counter),
        h($invoice->tpl_title)
    ),
    'menu' => [
        'edit' => [
            'title' => __d('lil_invoices', 'Edit'),
            'visible' => $invoice->invoices_counter->active,
            'url' => [
                'action' => 'edit',
                $invoice->id,
            ],
        ],
        'duplicate' => [
            'title' => __d('lil_invoices', 'Duplicate'),
            'visible' => true,
            'url' => [
                'action' => 'edit',
                '?' => ['duplicate' => $invoice->id],
            ],
        ],
        'attach' => [
            'title' => __d('lil_invoices', 'Attach'),
            'visible' => true,
            'submenu' => [
                'attachment' => [
                    'title' => __d('lil_invoices', 'File'),
                    'visible' => $this->Lil->userLevel('admin'),
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'invoicesAttachments',
                        'action' => 'add',
                        $invoice->id,
                    ],
                    'params' => [
                        'id' => 'AttachFile',
                    ],
                ],
                'scan' => [
                    'title' => __d('lil_invoices', 'Scan'),
                    'visible' => $this->Lil->userLevel('admin'),
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'invoicesAttachments',
                        'action' => 'scan',
                        $invoice->id,
                    ],
                    'params' => [
                        'id' => 'AttachScan',
                    ],
                ],
                'link' => [
                    'title' => __d('lil_invoices', 'Linked Invoice'),
                    'visible' => true,
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'invoices-links',
                        'action' => 'link',
                        $invoice->id,
                    ],
                    'params' => [
                        'id' => 'AttachLink',
                    ],
                ],
            ],
        ],
        'delete' => [
            'title' => __d('lil_invoices', 'Delete'),
            'visible' => $invoice->invoices_counter->active,
            'url' => [
                'action' => 'delete',
                $invoice->id,
            ],
            'params' => [
                'confirm' => __d('lil_invoices', 'Are you sure you want to delete this invoice?'),
            ],
        ],
        'print' => [
            'title' => __d('lil_invoices', 'Print'),
            'visible' => true,
            'url' => [
                'action' => 'preview',
                $invoice->id,
                $invoice->no,
            ],
        ],
        'email' => [
            'title' => __d('lil_invoices', 'Send'),
            'visible' => true,
            'url' => [
                'action' => 'email',
                '?' => ['id' => $invoice->id],
            ],
        ],
        'export' => [
            'title' => __d('lil_invoices', 'Export'),
            'visible' => true,
            'submenu' => [
                'pdf' => [
                    'title' => __d('lil_invoices', 'PDF'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $invoice->id,
                        Text::slug($invoice->title) . '.pdf',
                        '?' => ['download' => 1],
                    ],
                ],
                'sepaxml' => !$invoice->isInvoice() ? null : [
                    'title' => __d('lil_invoices', 'Sepa XML'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $invoice->id,
                        Text::slug($invoice->title) . '.sepa.xml',
                        '?' => ['download' => 1],
                    ],
                ],
                'eslog' => !$invoice->isInvoice() ? null : [
                    'title' => __d('lil_invoices', 'eSlog'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $invoice->id,
                        Text::slug($invoice->title) . '.eslog.xml',
                        '?' => ['download' => 1],
                    ],
                ],
            ],
        ],
        'settings' => [
            'title' => __d('lil_invoices', 'Settings'),
            'visible' => true,
            'submenu' => [
                'templates' => [
                    'title' => __d('lil_invoices', 'Templates'),
                    'visible' => true,
                    'url' => [
                        'action' => 'templates',
                        $invoice->id,
                    ],
                    'params' => [
                        'id' => 'EditTemplates',
                    ],
                ],
                'issuer' => empty($invoice->issuer) ? null : [
                    'title' => __d('lil_invoices', 'Edit Issuer'),
                    'visible' => true,
                    'url' => [
                        'controller' => 'InvoicesClients',
                        'action' => 'edit',
                        $invoice->issuer->id ?? '',
                    ],
                    'params' => [
                        'id' => 'EditIssuer',
                    ],
                ],
                'receiver' => empty($invoice->receiver) ? null : [
                    'title' => __d('lil_invoices', 'Edit Receiver'),
                    'visible' => true,
                    'url' => [
                        'controller' => 'InvoicesClients',
                        'action' => 'edit',
                        $invoice->receiver->id ?? '',
                    ],
                    'params' => [
                        'id' => 'EditReceiver',
                    ],
                ],
                'buyer' => empty($invoice->buyer) ? null : [
                    'title' => __d('lil_invoices', 'Edit Buyer'),
                    'visible' => true,
                    'url' => [
                        'controller' => 'InvoicesClients',
                        'action' => 'edit',
                        $invoice->buyer->id ?? '',
                    ],
                    'params' => [
                        'id' => 'EditBuyer',
                    ],
                ],
            ],
        ],
    ],
    'entity' => $invoice,
    'panels' => [
        'title' => [
            'id' => 'invoice-title',
            'lines' => [[
                'label' => __d('lil_invoices', 'Title') . ':',
                'text' => h($invoice->title),
            ]],
        ],
        'client' => [
            'id' => 'invoice-client',
            'lines' => [
                'client.title' => [
                    'label' => ($invoice->invoices_counter->kind == 'issued' ? __d('lil_invoices', 'Receiver') : __d('lil_invoices', 'Issuer')) . ':',
                    'text' =>
                        h($invoice->client->title) . '&nbsp;' . (
                        empty($invoice->client->contact_id) ? '' :
                            $this->Html->link(
                                $this->Html->image('/lil_invoices/img/goto.gif'),
                                [
                                    'plugin' => 'LilCrm',
                                    'controller' => 'contacts',
                                    'action' => 'view',
                                    $invoice->client->contact_id,
                                ],
                                ['escape' => false]
                            )
                        ),
                ],
                'client-address' => empty($invoice->client->primary_address) ? null : [
                    'label' => '&nbsp;',
                    'text' =>
                        implode(', ', array_filter([
                            h($invoice->client->primary_address->street),
                            h(implode(
                                ' ',
                                [
                                    $invoice->client->primary_address->zip,
                                    $invoice->client->primary_address->city,
                                ]
                            )),
                            h($invoice->client->primary_address->country),
                        ])),
                ],
                'client-tax_no' => empty($invoice->client->tax_no) ? null : [
                    'label' => '&nbsp;',
                    'text' => ($invoice->client->tax_status ? __d('lil_invoices', 'TAX payee no.') : __d('lil_invoices', 'TAX no.')) . ' ' .
                        h($invoice->client->tax_no),
                ],
            ],
        ],

        'details' => [
            'id' => 'invoice-details',
            'lines' => [
                0 => [
                    'label' => __d('lil_invoices', 'Date of issue') . ':',
                    'text' => (string)$invoice->dat_issue,
                ],
                1 => !$invoice->isInvoice() ? null : [
                    'label' => __d('lil_invoices', 'Service date') . ':',
                    'text' => (string)$invoice->dat_service,
                ],
                2 => !$invoice->isInvoice() ? null : [
                    'label' => __d('lil_invoices', 'Expiration date') . ':',
                    'text' => (string)$invoice->dat_expire,
                ],
                3 => empty($invoice->dat_approval) ? null : [
                    'label' => __d('lil_invoices', 'Approval date') . ':',
                    'text' => (string)$invoice->dat_approval,
                ],
            ],
        ],
        'project' => !Plugin::isLoaded('LilProjects') ? null : [
            'lines' => [
                [
                    'label' => __d('lil_invoices', 'Project') . ':',
                    'text' => $this->Html->link((string)$invoice->project, [
                        'plugin' => 'LilProjects',
                        'controller' => 'Projects',
                        'action' => 'view',
                        $invoice->project_id,
                    ]) . ' &nbsp;',
                ],
            ],
        ],
        'total' => !$invoice->isInvoice() ? null : [
            'id' => 'invoice-total',
            'lines' => [[
                'label' => __d('lil_invoices', 'Total') . ':',
                'text' => $this->Number->currency($invoice->total),
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
            '?' => ['duplicate' => $invoice->id, 'counter' => $cntr->id],
        ],
    ];
}

if ($invoice->isInvoice()) {
    ////////////////////////////////////////////////////////////////////////////////////////////////
    // ITEMS
    $itemsBody = [];
    $itemsTotal = 0;
    $grandTotal = 0;
    foreach ($invoice->invoices_items as $itm) {
        $itemsPrice = round($itm->price * $itm->qty, 4);
        $discount = round($itemsPrice * $itm->discount / 100, 4);
        $itemTotal = $itemsPrice - $discount;

        $lineTotal = round($itemTotal, 2) + round($itemTotal * $itm->vat_percent / 100, 2);
        $grandTotal += $lineTotal;

        $itemsBody['rows'][] = ['columns' => [
            'descript' => h($itm['descript']),
            'qty' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->precision($itm->qty, 2),
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
                'html' => $this->Number->precision($itm->discount, 1),
            ],
            'item_total' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->currency($itemTotal),
            ],
            'tax' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->precision($itm->vat_percent, 1),
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
                        __d('lil_invoices', 'Description'),
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Quantity')],
                        ['parameters' => ['class' => 'left-align'], 'html' => __d('lil_invoices', 'Unit')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Price per Unit')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Discount [%]')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Unit Total')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Tax [%]')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Total with Tax')],
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
                            'html' => __d('lil_invoices', 'Grand Total') . ':',
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

    if ($invoice->invoices_counter->kind == 'issued') {
        $invoiceView['panels']['items_title'] = sprintf('<h3>%s</h3>', __d('lil_invoices', 'Analytics'));
        $invoiceView['panels']['items']['id'] = 'invoice-view-items-table';
        $invoiceView['panels']['items']['table'] = $items;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // TAXES
    $taxesBody = [];
    $baseTotal = 0;
    $taxTotal = 0;
    $grandTotal = 0;
    foreach ($invoice->invoices_taxes as $itm) {
        $tax = round($itm->base * $itm->vat_percent / 100, 2);
        $lineTotal = round($itm->base + $tax, 2);

        $taxesBody['rows'][] = ['columns' => [
            'descript' => h($itm->vat_title),
            'percent' => [
                'parameters' => ['class' => 'right-align'],
                'html' => $this->Number->precision($itm->vat_percent, 1),
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
                        __d('lil_invoices', 'Description'),
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'VAT [%]')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Base')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Tax')],
                        ['parameters' => ['class' => 'right-align'], 'html' => __d('lil_invoices', 'Total')],
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
                            'html' => __d('lil_invoices', 'Grand Total') . ':',
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

    if ($invoice->invoices_counter->kind == 'received') {
        $invoiceView['panels']['vat_title'] = sprintf('<h3>%s</h3>', __d('lil_invoices', 'VAT Analytics'));
        $invoiceView['panels']['vat']['id'] = 'invoice-view-tax-table';
        $invoiceView['panels']['vat']['table'] = $taxes;
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////
// ATTACHMENTS
$invoiceView['panels']['attachments_title'] = sprintf('<h3>%s</h3>', __d('lil_invoices', 'Attachments'));
if (empty($invoice->invoices_attachments)) {
    $invoiceView['panels']['attachments_empty'] = sprintf('<div class="hint">%s</div>', __d('lil_invoices', 'No attachments found.'));
} else {
    $i = 0;
    foreach ($invoice->invoices_attachments as $atch) {
        $invoiceView['panels']['attachments_' . $atch->id] = sprintf(
            '<div>%1$s (%2$s) %3$s</div>',
            $this->Html->link(
                $atch['original'],
                [
                    'controller' => 'invoices-attachments',
                    'action' => 'view',
                    $atch->id,
                ],
                [
                    'class' => 'AttachmentPreview'
                ]
            ),
            $this->Number->toReadableSize((int)$atch->filesize),
            $this->Html->link(
                '<i class="material-icons">get_app</i>',
                [
                    'controller' => 'invoices-attachments',
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
                    'controller' => 'invoices-attachments',
                    'action' => 'delete',
                    $atch->id,
                ],
                [
                    'escape' => false,
                    'confirm' => __d('lil_invoices', 'Are you sure you want to delete this attachment'),
                    'class' => 'btn btn-small btn-floating waves-effect waves-light waves-circle',
                ]
            )
        );
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////
// LINKS
$invoiceView['panels']['links_title'] = sprintf('<h3>%s</h3>', __d('lil_invoices', 'Linked Invoices'));
if (empty($links)) {
    $invoiceView['panels']['links_empty'] = sprintf('<div class="hint">%s</div>', __d('lil_invoices', 'No linked invoices found.'));
} else {
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
                $this->Html->image('/lil_invoices/img/remove.gif'),
                [
                    'controller' => 'InvoicesLinks',
                    'action' => 'delete',
                    $invoice->id, $link->id,
                ],
                [
                    'escape' => false,
                    'confirm' => __d('lil_invoices', 'Are you sure you want to delete this link?'),
                ]
            )
        );
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////
// LINKS
$invoiceView['panels']['text_title'] = sprintf('<h3>%s</h3>', __d('lil_invoices', 'Description'));
$invoiceView['panels']['text_data'] = sprintf('<div id="invoice-descript-preview">%s</div>', $invoice->descript);

echo $this->Lil->panels($invoiceView, 'LilInvoices.Invoices.view');
?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#EditTemplates").modalPopup({title: "<?= __d('lil_invoices', 'Edit Templates') ?>"});

        $("#AttachFile").modalPopup({title: "<?= __d('lil_invoices', 'Attach File') ?>"});
        $("#AttachLink").modalPopup({title: "<?= __d('lil_invoices', 'Link Invoice') ?>"});
        $("#AttachScan").modalPopup({title: "<?= __d('lil_invoices', 'Scan Invoice') ?>", onClose: function() {
            if (typeof window.ws != "undefined" && window.ws) {
                ws.close();
                window.ws = null;
            }
        }});

        $("#EditIssuer").modalPopup({title: "<?= __d('lil_invoices', 'Edit Issuer') ?>"});
        $("#EditReceiver").modalPopup({title: "<?= __d('lil_invoices', 'Edit Receiver') ?>"});
        $("#EditBuyer").modalPopup({title: "<?= __d('lil_invoices', 'Edit Buyer') ?>"});

        $(".AttachmentPreview").each(function() {
            $(this).modalPopup({
                title: "<?= __d('lil_invoices', 'Attachment Preview') ?>",
                onOpen: function(popup) {
                    $(popup).height(window.innerHeight - 30);
                    //$("#invoice-view", popup).height(window.innerHeight - $("#invoice-view").offset().top - 30);
                    $("#invoice-view", popup).height($(popup).innerHeight() - 120);
                }
            });
        });
    });
</script>
