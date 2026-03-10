<?php
use Cake\Core\Plugin;
use Cake\Routing\Router;
use Cake\Utility\Text;
use Documents\Model\Entity\TravelOrder;

$statusLabels = TravelOrder::statusLabels();
$statusLabel = $statusLabels[$document->status ?? ''] ?? h($document->status ?? '');

$invoiceView = [
    'title_for_layout' => __d(
        'documents',
        '{3} #{0} <span class="light nowrap">({1} :: {2})</span>',
        h($document->no),
        h($document->documents_counter->title),
        h($document->counter),
        h($document->tpl_title),
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
                    'visible' => $this->getCurrentUser()->hasRole('admin'),
                    'url' => [
                        'plugin' => false,
                        'controller' => 'Attachments',
                        'action' => 'edit',
                        '?' => [
                            'model' => 'TravelOrder',
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
                        Text::slug($document->title ?? $document->no ?? 'travel-order') . '.pdf',
                        '?' => ['download' => 1],
                    ],
                ],
                'xml' => [
                    'title' => __d('documents', 'XML'),
                    'visible' => true,
                    'url' => [
                        'action' => 'export',
                        $document->id,
                        Text::slug($document->title ?? $document->no ?? 'travel-order') . '.xml',
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
                    'label' => __d('documents', 'Issued') . ':',
                    'text' => h($document->location) . ', ' . (string)$document->dat_issue,
                ],
            ],
        ],
        'vehicle' => [
            'id' => 'invoice-vehicle',
            'lines' => [
                0 => empty($document->vehicle_title) && empty($document->vehicle_registration) && empty($document->vehicle_owner) ? null : [
                    'label' => __d('documents', 'Vehicle') . ':',
                    'text' => implode(' &nbsp;&bull;&nbsp; ', array_filter([
                        h($document->vehicle_title),
                        h($document->vehicle_registration),
                        h($document->vehicle_owner),
                    ])),
                ],
            ],
        ],
        'status' => [
            'id' => 'invoice-status',
            'lines' => [
                0 => [
                    'label' => __d('documents', 'Status') . ':',
                    'text' => '<strong>' . h($statusLabel) . '</strong>',
                ],
                1 => [
                    'label' => __d('documents', 'Entered by') . ':',
                    'text' => h($document->entered_by->name ?? '')
                        . (!empty($document->entered_at) ? ' &ndash; ' . h((string)$document->entered_at) : ''),
                ],
                2 => empty($document->approved_by_id) ? null : [
                    'label' => __d('documents', 'Approved by') . ':',
                    'text' => h($document->approved_by->name ?? '')
                        . (!empty($document->approved_at) ? ' &ndash; ' . h((string)$document->approved_at) : ''),
                ],
                3 => empty($document->processed_by_id) ? null : [
                    'label' => __d('documents', 'Processed by') . ':',
                    'text' => h($document->processed_by->name ?? '')
                        . (!empty($document->processed_at) ? ' &ndash; ' . h((string)$document->processed_at) : ''),
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
            'lines' => [
                [
                    'label' => __d('documents', 'Total') . ':',
                    'text' => $this->Number->currency((float)$document->total),
                ],
                !empty($document->advance) ? [
                    'label' => __d('documents', 'Advance') . ':',
                    'text' => $this->Number->currency((float)$document->advance),
                ] : null,
                !empty($document->advance) ? [
                    'label' => __d('documents', 'Total Payout') . ':',
                    'text' => '<strong>' . $this->Number->currency((float)$document->total - (float)$document->advance) . '</strong>',
                ] : null,
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
            'controller' => 'TravelOrders',
            'action' => 'edit',
            '?' => ['duplicate' => $document->id, 'counter' => $cntr->id],
        ],
    ];
}

if (!empty($document->descript)) {
    $invoiceView['panels']['text_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Description'));
    $invoiceView['panels']['text_data'] = sprintf('<div id="travelorder-descript-preview">%s</div>', $document->descript);
}

////////////////////////////////////////////////////////////////////////////////////////////////
// WORKFLOW ACTION BUTTONS
$workflowButtons = '';
if (($document->status === TravelOrder::STATUS_DRAFT) && $this->getCurrentUser()->hasRole('editor')) {
    $workflowButtons .= $this->Html->link(
        __d('documents', 'Sign'),
        ['action' => 'sign', $document->id],
        ['class' => 'btn filled', 'id' => 'SignTravelOrder'],
    );
}
if (($document->status === TravelOrder::STATUS_WAITING_APPROVAL) && $this->getCurrentUser()->hasRole('admin')) {
    $workflowButtons .= $this->Html->link(
        __d('documents', 'Approve'),
        ['action' => 'approve', $document->id],
        ['class' => 'btn filled', 'id' => 'ApproveTravelOrder'],
    );
    $workflowButtons .= $this->Form->postLink(
        __d('documents', 'Decline'),
        ['action' => 'decline', $document->id],
        [
            'class' => 'btn',
            'confirm' => __d('documents', 'Decline this travel order?'),
        ],
    );
}
if (($document->status === TravelOrder::STATUS_APPROVED) && $this->getCurrentUser()->hasRole('editor')) {
    $workflowButtons .= $this->Html->link(
        __d('documents', 'Submit for Processing'),
        ['action' => 'submit', $document->id],
        ['class' => 'btn filled', 'confirm' => __d('documents', 'Submit this travel order for processing?')],
    );
}
if (($document->status === TravelOrder::STATUS_WAITING_PROCESSING) && $this->getCurrentUser()->hasRole('admin')) {
    $workflowButtons .= $this->Html->link(
        __d('documents', 'Finalize (Process)'),
        ['action' => 'process', $document->id],
        ['class' => 'btn filled', 'id' => 'ProcessTravelOrder'],
    );
}
if (!empty($workflowButtons)) {
    $invoiceView['panels']['workflow_actions'] = '<div class="workflow-actions">' . $workflowButtons . '</div>';
}

////////////////////////////////////////////////////////////////////////////////////////////////
// ATTACHMENTS

if (!empty($document->attachments)) {
    $invoiceView['panels']['attachments_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Attachments'));
    $invoiceView['panels']['attachments'] = $this->Arhint->attachmentsTable(
        $document->attachments,
        'TravelOrder',
        $document->id,
        ['redirectUrl' => Router::url(null, true), 'showAddButton' => false],
    );
}

////////////////////////////////////////////////////////////////////////////////////////////////
// MILEAGES
$canEditRows = ($document->status === TravelOrder::STATUS_WAITING_PROCESSING) && $this->getCurrentUser()->hasRole('admin');

if (!empty($document->travel_orders_mileages) || $canEditRows) {
    $mileagesBody = [];
    $mileageTotal = 0;
    foreach ($document->travel_orders_mileages as $m) {
        $mileageTotal += (float)($m->total ?? 0);
        $row = ['columns' => [
            'details' => ['html' =>
                '<small>' . h((string)$m->start_time) . ' &ndash; ' . h((string)$m->end_time) . '</small><br>'
                . h($m->road_description),
            ],
            'distance_km' => ['parameters' => ['class' => 'right-align'], 'html' => $this->Number->format($m->distance_km ?? 0, ['places' => 1])],
            'price_per_km' => ['parameters' => ['class' => 'right-align'], 'html' => $this->Number->format($m->price_per_km ?? 0, ['places' => 2])],
            'total' => ['parameters' => ['class' => 'right-align'], 'html' => $this->Number->currency($m->total ?? 0)],
        ]];
        if ($canEditRows) {
            $row['columns']['actions'] = [
                'parameters' => ['class' => 'nowrap'],
                'html' => $this->Lil->editLink(
                    ['plugin' => 'Documents', 'controller' => 'TravelOrdersMileages', 'action' => 'edit', $m->id, '?' => ['redirect' => Router::url(null, true)]],
                    ['class' => 'EditMileageRow'],
                )
                    . ' '
                    . $this->Lil->deleteLink(
                        ['plugin' => 'Documents', 'controller' => 'TravelOrdersMileages', 'action' => 'delete', $m->id, '?' => ['redirect' => Router::url(null, true)]],
                        ['confirm' => __d('documents', 'Delete this mileage entry?')],
                    ),
            ];
        }
        $mileagesBody['rows'][] = $row;
    }

    $mileagesHeadColumns = [
        __d('documents', 'Route'),
        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Distance (km)')],
        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Price / km')],
        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Total')],
    ];
    if ($canEditRows) {
        $mileagesHeadColumns[] = '';
    }

    $mileagesFootColspan = $canEditRows ? 3 : 3;
    $mileagesFootColumns = [
        ['parameters' => ['class' => 'right-align', 'colspan' => $mileagesFootColspan], 'html' => __d('documents', 'Total') . ':'],
        ['parameters' => ['class' => 'right-align'], 'html' => $this->Number->currency($mileageTotal)],
    ];
    if ($canEditRows) {
        $mileagesFootColumns[] = ['html' => ''];
    }

    $mileagesTable = [
        'parameters' => ['cellspacing' => '0', 'cellpadding' => '0', 'id' => 'travel-order-mileages-table', 'class' => 'index-static'],
        'head' => ['rows' => [0 => ['columns' => $mileagesHeadColumns]]],
        'body' => $mileagesBody,
        'foot' => ['rows' => [0 => ['columns' => $mileagesFootColumns]]],
    ];

    $invoiceView['panels']['mileages_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Mileage'));
    $invoiceView['panels']['mileages']['id'] = 'travel-order-view-mileages-table';
    $invoiceView['panels']['mileages']['table'] = $mileagesTable;
    if ($canEditRows) {
        $invoiceView['panels']['mileages_add'] = $this->Html->link(
            __d('documents', '+ Add Mileage'),
            ['plugin' => 'Documents', 'controller' => 'TravelOrdersMileages', 'action' => 'edit', '?' => ['travel_order_id' => $document->id, 'redirect' => Router::url(null, true)]],
            ['id' => 'AddMileageBtn', 'class' => 'btn btn-small filled'],
        );
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////
// EXPENSES
if (!empty($document->travel_orders_expenses) || $canEditRows) {
    $expensesBody = [];
    $expenseTotal = 0;
    foreach ($document->travel_orders_expenses as $e) {
        $effectiveTotal = !empty($e->approved_total) ? (float)$e->approved_total : (float)($e->total ?? 0);
        $expenseTotal += $effectiveTotal;
        $totalHtml = !empty($e->approved_total)
            ? '<s>' . $this->Number->currency((float)$e->total) . '</s><br>' . $this->Number->currency((float)$e->approved_total)
            : $this->Number->currency((float)($e->total ?? 0));
        $row = ['columns' => [
            'details' => ['html' =>
                '<small>' . h((string)$e->start_time) . ' &ndash; ' . h((string)$e->end_time) . '</small><br>'
                . h($e->description),
            ],
            'type' => h($e->type),
            'quantity' => ['parameters' => ['class' => 'right-align'], 'html' => $this->Number->format($e->quantity ?? 0, ['places' => 1])],
            'price' => ['parameters' => ['class' => 'right-align'], 'html' => $this->Number->format($e->price ?? 0, ['places' => 2])],
            'currency' => h($e->currency),
            'total' => ['parameters' => ['class' => 'right-align'], 'html' => $totalHtml],
        ]];
        if ($canEditRows) {
            $row['columns']['actions'] = [
                'parameters' => ['class' => 'nowrap'],
                'html' => $this->Lil->editLink(
                    ['plugin' => 'Documents', 'controller' => 'TravelOrdersExpenses', 'action' => 'edit', $e->id, '?' => ['redirect' => Router::url(null, true)]],
                    ['class' => 'EditExpenseRow'],
                )
                    . ' '
                    . $this->Lil->deleteLink(
                        ['plugin' => 'Documents', 'controller' => 'TravelOrdersExpenses', 'action' => 'delete', $e->id, '?' => ['redirect' => Router::url(null, true)]],
                        ['confirm' => __d('documents', 'Delete this expense entry?')],
                    ),
            ];
        }
        $expensesBody['rows'][] = $row;
    }

    $expensesHeadColumns = [
        __d('documents', 'Details'),
        __d('documents', 'Type'),
        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Qty')],
        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Price')],
        __d('documents', 'Currency'),
        ['parameters' => ['class' => 'right-align'], 'html' => __d('documents', 'Total')],
    ];
    if ($canEditRows) {
        $expensesHeadColumns[] = '';
    }

    $expensesFootColumns = [
        ['parameters' => ['class' => 'right-align', 'colspan' => 5], 'html' => __d('documents', 'Total') . ':'],
        ['parameters' => ['class' => 'right-align'], 'html' => $this->Number->currency($expenseTotal)],
    ];
    if ($canEditRows) {
        $expensesFootColumns[] = ['html' => ''];
    }

    $expensesTable = [
        'parameters' => ['cellspacing' => '0', 'cellpadding' => '0', 'id' => 'travel-order-expenses-table', 'class' => 'index-static'],
        'head' => ['rows' => [0 => ['columns' => $expensesHeadColumns]]],
        'body' => $expensesBody,
        'foot' => ['rows' => [0 => ['columns' => $expensesFootColumns]]],
    ];

    $invoiceView['panels']['expenses_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Additional Costs'));
    $invoiceView['panels']['expenses']['id'] = 'travel-order-view-expenses-table';
    $invoiceView['panels']['expenses']['table'] = $expensesTable;
    if ($canEditRows) {
        $invoiceView['panels']['expenses_add'] = $this->Html->link(
            __d('documents', '+ Add Expense'),
            ['plugin' => 'Documents', 'controller' => 'TravelOrdersExpenses', 'action' => 'edit', '?' => ['travel_order_id' => $document->id, 'redirect' => Router::url(null, true)]],
            ['id' => 'AddExpenseBtn', 'class' => 'btn btn-small filled'],
        );
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////
// LINKS
if (!empty($links)) {
    $invoiceView['panels']['links_title'] = sprintf('<h3>%s</h3>', __d('documents', 'Linked Documents'));
    foreach ($links as $link) {
        $invoiceView['panels']['links_' . $link->id] = sprintf(
            '<div>%1$s %2$s</div>',
            '#' . $link->invoice->no . ' :: ' . $this->Html->link(
                $link->invoice->title,
                [
                    'action' => 'view',
                    $link->invoice->id,
                ],
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

echo $this->Lil->panels($invoiceView, 'Documents.TravelOrders.view');
?>

<script type="text/javascript">
    $(document).ready(function() {
        var travelOrderPopupOpen = function(popup) {
            $(popup).css({width: "80vw", maxWidth: "none", height: "85vh"});
            $("#travel-order-view", popup).css({width: "100%", height: "calc(85vh - 160px)"});
        };
        $("#SignTravelOrder").modalPopup({
            title: "<?= __d('documents', 'Sign Travel Order') ?>",
            onOpen: travelOrderPopupOpen,
        });
        $("#ApproveTravelOrder").modalPopup({
            title: "<?= __d('documents', 'Approve Travel Order') ?>",
            onOpen: travelOrderPopupOpen,
        });
        $("#ProcessTravelOrder").modalPopup({
            title: "<?= __d('documents', 'Process Travel Order') ?>",
            onOpen: travelOrderPopupOpen,
        });

        $("#EditTemplates").modalPopup({title: "<?= __d('documents', 'Edit Templates') ?>"});
        $("#EmailDocument").modalPopup({title: "<?= __d('documents', 'Email Document') ?>"});

        $("#AttachFile").modalPopup({title: "<?= __d('documents', 'Attach File') ?>"});
        $("#AttachLink").modalPopup({title: "<?= __d('documents', 'Link Document') ?>"});

        $("#AddMileage").modalPopup({title: "<?= __d('documents', 'Add Mileage') ?>"});
        $("#AddExpense").modalPopup({title: "<?= __d('documents', 'Add Expense') ?>"});
        $("#AddMileageBtn").modalPopup({title: "<?= __d('documents', 'Add Mileage') ?>"});
        $("#AddExpenseBtn").modalPopup({title: "<?= __d('documents', 'Add Expense') ?>"});

        $(".EditMileageRow").each(function() {
            $(this).modalPopup({title: "<?= __d('documents', 'Edit Mileage') ?>"});
        });
        $(".EditExpenseRow").each(function() {
            $(this).modalPopup({title: "<?= __d('documents', 'Edit Expense') ?>"});
        });

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
