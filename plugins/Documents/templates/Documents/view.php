<?php
use Cake\Core\Plugin;
use Cake\Routing\Router;
use Cake\Utility\Text;

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
                        'plugin' => false,
                        'controller' => 'Attachments',
                        'action' => 'edit',
                        '?' => [
                            'model' => 'Document',
                            'foreign_id' => $document->id,
                            'redirect' => Router::url(null, true),
                        ],
                    ],
                    'params' => [
                        'id' => 'AttachFile',
                    ],
                ],
                'link' => [
                    'title' => __d('documents', 'Linked Document'),
                    'visible' => true,
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'DocumentsLinks',
                        'action' => 'link',
                        'Document',
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
                base64_encode($document->no),
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
                'xml' => [
                    'title' => __d('documents', 'XML'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $document->id,
                        Text::slug($document->title) . '.xml',
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
            ],
        ],
    ],
    'entity' => $document,
    'panels' => [
        'title' => [
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
    ],
];

// duplicate into counter
foreach ($counters as $cntr) {
    $invoiceView['menu']['duplicate']['submenu'][] = [
        'title' => $cntr->title,
        'visible' => true,
        'url' => [
            'controller' => 'Documents',
            'action' => 'edit',
            '?' => ['duplicate' => $document->id, 'counter' => $cntr->id],
        ],
    ];
}

////////////////////////////////////////////////////////////////////////////////////////////////
// ATTACHMENTS

if (!empty($document->attachments)) {
    $invoiceView['panels']['attachments_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Attachments'));
    $invoiceView['panels']['attachments'] = $this->Arhint->attachmentsTable(
        $document->attachments,
        'Document',
        $document->id,
        ['redirectUrl' => Router::url(null, true), 'showAddButton' => false]
    );
}

////////////////////////////////////////////////////////////////////////////////////////////////
// LINKS
if (!empty($links)) {
    $invoiceView['panels']['links_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Linked Documents'));
    foreach ($links as $link) {
        $invoiceView['panels']['links_' . $link->id] = sprintf(
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
                    $document->id,
                    $link->id,
                ],
                [
                    'escape' => false,
                    'confirm' => __d('documents', 'Are you sure you want to delete this link?'),
                ],
            ),
        );
    }
}

if (!empty($document->descript)) {
    $invoiceView['panels']['text_data'] = sprintf('<div id="invoice-descript-preview">%s</div>', $document->descript);
}

echo $this->Lil->panels($invoiceView, 'Documents.Documents.view');
?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#EditTemplates").modalPopup({title: "<?= __d('documents', 'Edit Templates') ?>"});
        $("#EmailDocument").modalPopup({title: "<?= __d('documents', 'Email Document') ?>"});

        $("#AttachFile").modalPopup({
            title: "<?= __d('documents', 'Attach File') ?>",
            onOpen: function(popup) {
                $("#filename", popup).focus().click();
            },
        });
        $("#AttachLink").modalPopup({title: "<?= __d('documents', 'Link Document') ?>"});
        $("#AttachScan").modalPopup({title: "<?= __d('documents', 'Scan Document') ?>", onClose: function() {
            if (typeof window.ws != "undefined" && window.ws) {
                ws.close();
                window.ws = null;
            }
        }});

        $("#EditIssuer").modalPopup({title: "<?= __d('documents', 'Edit Issuer') ?>"});
        $("#EditReceiver").modalPopup({title: "<?= __d('documents', 'Edit Receiver') ?>"});
    });
</script>
