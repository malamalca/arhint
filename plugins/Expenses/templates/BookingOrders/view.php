<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BookingOrder $bookingOrder
 */

$isLocked = $bookingOrder->status === 'locked';
$isDraft = $bookingOrder->status === 'draft';
$isPosted = $bookingOrder->status === 'posted';

// Compute totals
$totalDebit = 0.0;
$totalCredit = 0.0;
foreach ($bookingOrder->booking_order_entries ?? [] as $entry) {
    $totalDebit += (float)$entry->debit;
    $totalCredit += (float)$entry->credit;
}

// Build entries rows for $this->Lil->table()
$addEntryLink = !$isLocked ? $this->Html->link(
    '+ ' . __d('expenses', 'Add entry'),
    [
        'plugin' => 'Expenses',
        'controller' => 'BookingOrderEntries',
        'action' => 'edit',
        '?' => ['booking_order_id' => $bookingOrder->id],
    ],
    ['class' => 'btn btn-small filled', 'id' => 'AddEntryLink'],
) : '';

$bodyRows = [];
foreach ($bookingOrder->booking_order_entries ?? [] as $entry) {
    $partnerName = $entry->partner && $entry->partner->contact
        ? h($entry->partner->contact->title)
        : '—';

    $bodyRows[] = [
        'columns' => [
            ['html' => h((string)$entry->no)],
            [
                'html' => $entry->account ? h((string)$entry->account->code) : '—',
                'params' => [
                    'class' => 'boe-trunc tooltipped',
                    'data-position' => 'top',
                    'data-tooltip' => $entry->account ? h((string)$entry->account) : '—',
                ],
            ],
            [
                'html' => $partnerName,
                'params' => [
                    'class' => 'boe-trunc tooltipped',
                    'data-position' => 'top',
                    'data-tooltip' => $partnerName,
                ],
            ],
            ['html' => h((string)$entry->descript), 'params' => ['class' => 'boe-trunc']],
            ['html' => $this->Number->currency((float)$entry->debit), 'params' => ['class' => 'right-align']],
            ['html' => $this->Number->currency((float)$entry->credit), 'params' => ['class' => 'right-align']],
            [
                'html' => $isLocked ? '&nbsp;' :
                    $this->Lil->editLink([
                        'plugin' => 'Expenses',
                        'controller' => 'BookingOrderEntries',
                        'action' => 'edit',
                        $entry->id,
                    ], ['class' => 'boe-edit-entry']) . ' ' . $this->Lil->deleteLink([
                        'plugin' => 'Expenses',
                        'controller' => 'BookingOrderEntries',
                        'action' => 'delete',
                        $entry->id,
                    ], ['confirm' => __d('expenses', 'Are you sure you want to delete this entry?')]),
                'params' => ['class' => 'right-align nowrap'],
            ],
        ],
    ];
}

$boView = [
    'title_for_layout' => __d('expenses', 'Booking Order #{0}', h($bookingOrder->no)),
    'menu' => [
        'edit' => [
            'title' => __d('expenses', 'Edit'),
            'visible' => !$isLocked,
            'url' => ['action' => 'edit', $bookingOrder->id],
        ],
        'post' => [
            'title' => __d('expenses', 'Post'),
            'visible' => $isDraft,
            'url' => ['action' => 'post', $bookingOrder->id],
            'params' => ['confirm' => __d('expenses', 'Post this booking order?')],
        ],
        'lock' => [
            'title' => __d('expenses', 'Lock'),
            'visible' => $isPosted,
            'url' => ['action' => 'lock', $bookingOrder->id],
            'params' => ['confirm' => __d('expenses', 'Lock this booking order?')],
        ],
        'delete' => [
            'title' => __d('expenses', 'Delete'),
            'visible' => $isDraft,
            'url' => ['action' => 'delete', $bookingOrder->id],
            'params' => ['confirm' => __d('expenses', 'Are you sure you want to delete this booking order?')],
        ],
    ],
    'panels' => [
        'header' => [
            'lines' => [
                ['label' => __d('expenses', 'No') . ':', 'text' => h($bookingOrder->no)],
                ['label' => __d('expenses', 'Title') . ':', 'text' => h($bookingOrder->title)],
                ['label' => __d('expenses', 'Date') . ':', 'text' => h((string)$bookingOrder->date_created)],
                [
                    'label' => __d('expenses', 'Status') . ':',
                    'text' => sprintf(
                        '<span class="bo-status bo-status-%s">%s</span>',
                        h($bookingOrder->status),
                        h($bookingOrder->status),
                    ),
                ],
                [
                    'label' => __d('expenses', 'Opened by') . ':',
                    'text' => $bookingOrder->opener ? h($bookingOrder->opener->name) : '—',
                ],
            ],
        ],
        'entries' => [
            'params' => ['class' => 'no-margin'],
            'lines' => [0 => ['table' => [
                'pre' => sprintf(
                    '<style>.boe-trunc{overflow:hidden;white-space:nowrap;text-overflow:ellipsis}</style>'
                    . '<h3 style="margin-top:14px">%s %s</h3>',
                    __d('expenses', 'Entries'),
                    $addEntryLink,
                ),
                'params' => ['style' => 'table-layout:fixed;max-width:100%'],
                'head' => [
                    'rows' => [
                        [
                            'columns' => [
                                ['html' => '#', 'params' => ['style' => 'width:2.5em']],
                                ['html' => __d('expenses', 'Account'), 'params' => ['style' => 'width:8%']],
                                ['html' => __d('expenses', 'Partner'), 'params' => ['style' => 'width:30%']],
                                ['html' => __d('expenses', 'Description')],
                                ['html' => __d('expenses', 'Debit'), 'params' => [
                                    'class' => 'right-align', 'style' => 'width:8em',
                                ]],
                                ['html' => __d('expenses', 'Credit'), 'params' => [
                                    'class' => 'right-align', 'style' => 'width:8em',
                                ]],
                                ['html' => '', 'params' => ['style' => 'width:5em']],
                            ],
                        ],
                    ],
                ],
                'body' => [
                    'rows' => $bodyRows,
                ],
                'foot' => [
                    'rows' => [
                        [
                            'column' => 'td',
                            'columns' => [
                                [
                                    'html' => __d('expenses', 'Total') . ':',
                                    'params' => ['colspan' => 4, 'style' => 'text-align:right;font-weight:700'],
                                ],
                                [
                                    'html' => $this->Number->currency($totalDebit),
                                    'params' => ['class' => 'right-align', 'style' => 'font-weight:700'],
                                ],
                                [
                                    'html' => $this->Number->currency($totalCredit),
                                    'params' => ['class' => 'right-align', 'style' => 'font-weight:700'],
                                ],
                                ['html' => ''],
                            ],
                        ],
                    ],
                ],
            ]]],
        ],
    ],
];

echo $this->Lil->panels($boView, 'Expenses.BookingOrders.view');
$this->Lil->jsReady('M.Tooltip.init(document.querySelectorAll(".tooltipped"));');
$this->Lil->jsReady(sprintf(
    '$("#AddEntryLink").modalPopup({title:%s,processSubmit:true,onJson:function(){window.location.reload();}});',
    json_encode(__d('expenses', 'Add Entry')),
));
$this->Lil->jsReady(sprintf(
    '$(".boe-edit-entry").each(function(){'
    . '$(this).modalPopup({title:%s,processSubmit:true,onJson:function(){window.location.reload();}});'
    . '});',
    json_encode(__d('expenses', 'Edit Entry')),
));
