<?php
use Cake\Core\Plugin;

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
                        'TravelOrder',
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
                        'TravelOrder',
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
                        'TravelOrder',
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
                'payer' => empty($document->payer) ? null : [
                    'title' => __d('documents', 'Edit Payer'),
                    'visible' => true,
                    'url' => [
                        'controller' => 'DocumentsClients',
                        'action' => 'edit',
                        $document->payer->id ?? '',
                    ],
                    'params' => [
                        'id' => 'EditIssuerPayer',
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
        'details' => [
            'id' => 'invoice-details',
            'lines' => [
                0 => [
                    'label' => __d('documents', 'Date of issue') . ':',
                    'text' => (string)$document->dat_issue,
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
            'controller' => 'TravelOrders',
            'action' => 'edit',
            '?' => ['duplicate' => $document->id, 'counter' => $cntr->id],
        ],
    ];
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

echo $this->Lil->panels($invoiceView, 'Documents.TravelOrders.view');
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

        $("#EditPayer").modalPopup({title: "<?= __d('documents', 'Edit Payer') ?>"});

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
