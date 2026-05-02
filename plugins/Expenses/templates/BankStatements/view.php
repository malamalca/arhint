<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BankStatement $bankStatement
 * @var array<string, true> $bookedEntryIds
 */

use Cake\Routing\Router;

// Build entries rows
$addEntryLink = $this->Html->link(
    '+ ' . __d('expenses', 'Add entry'),
    [
        'plugin' => 'Expenses',
        'controller' => 'BankStatementEntries',
        'action' => 'edit',
        '?' => ['statement_id' => $bankStatement->id],
    ],
    ['class' => 'btn btn-small filled', 'id' => 'AddEntryLink'],
);

$bodyRows = [];
foreach ($bankStatement->bank_statement_entries ?? [] as $entry) {
    $isBooked = isset($bookedEntryIds[$entry->id]);
    $bookingsLink = $this->Html->link(
        '<i class="material-icons">receipt</i>',
        [
            'plugin' => 'Expenses',
            'controller' => 'BookingOrders',
            'action' => 'links',
            '?' => [
                'model' => 'BankStatementEntry',
                'foreignid' => $entry->id,
                'redirect' => Router::url(null, true),
            ],
        ],
        [
            'class' => 'btn btn-small filled' . ($isBooked ? ' green' : ' red'),
            'data-booked' => $isBooked ? '1' : '0',
            'escape' => false,
        ],
    );
    $bodyRows[] = [
        'columns' => [
            ['html' => h((string)$entry->dat_issue)],
            ['html' => h((string)$entry->no)],
            [
                'html' => h((string)$entry->client),
                'params' => ['class' => 'bse-trunc tooltipped', 'data-position' => 'top',
                    'data-tooltip' => h((string)$entry->client)],
            ],
            [
                'html' => h((string)$entry->descript),
                'params' => ['class' => 'bse-trunc'],
            ],
            ['html' => h((string)$entry->ref), 'params' => ['class' => 'bse-trunc']],
            ['html' => $this->Number->currency((float)$entry->debit), 'params' => ['class' => 'right-align']],
            ['html' => $this->Number->currency((float)$entry->credit), 'params' => ['class' => 'right-align']],
            [
                'html' => $bookingsLink . ' ' . $this->Lil->editLink([
                    'plugin' => 'Expenses',
                    'controller' => 'BankStatementEntries',
                    'action' => 'edit',
                    $entry->id,
                    '?' => ['redirect' => Router::url(null, true)],
                ], ['class' => 'bse-edit-entry']) . ' ' . $this->Lil->deleteLink([
                    'plugin' => 'Expenses',
                    'controller' => 'BankStatementEntries',
                    'action' => 'delete',
                    $entry->id,
                ], ['confirm' => __d('expenses', 'Are you sure you want to delete this entry?')]),
                'params' => ['class' => 'right-align nowrap'],
            ],
        ],
    ];
}

$bsView = [
    'title_for_layout' => __d('expenses', 'Bank Statement #{0}', h($bankStatement->no)),
    'menu' => [
        'edit' => [
            'title' => __d('expenses', 'Edit'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => ['action' => 'edit', $bankStatement->id],
        ],
        'delete' => [
            'title' => __d('expenses', 'Delete'),
            'visible' => $this->getCurrentUser()->hasRole('root'),
            'url' => ['action' => 'delete', $bankStatement->id],
            'params' => ['confirm' => __d('expenses', 'Are you sure you want to delete this bank statement?')],
        ],
    ],
    'panels' => [
        'header' => [
            'lines' => [
                ['label' => __d('expenses', 'Statement No') . ':', 'text' => h($bankStatement->no)],
                ['label' => __d('expenses', 'Seq. No') . ':', 'text' =>
                    $bankStatement->seq_no !== null ? h((string)$bankStatement->seq_no) : '—'],
                ['label' => __d('expenses', 'IBAN') . ':', 'text' => h($bankStatement->iban)],
                ['label' => __d('expenses', 'Opening Balance') . ':', 'text' =>
                    $bankStatement->balance !== null ? $this->Number->currency((float)$bankStatement->balance) : '—'],
                ['label' => __d('expenses', 'Date') . ':', 'text' => h((string)$bankStatement->dat_issue)],
                ['label' => __d('expenses', 'Currency') . ':', 'text' => h($bankStatement->currency)],
                ['label' => __d('expenses', 'Kind') . ':', 'text' => h((string)$bankStatement->kind)],
                ['label' => __d('expenses', 'Imported') . ':', 'text' => h((string)$bankStatement->dat_import)],
                [
                    'label' => __d('expenses', 'Imported by') . ':',
                    'text' => $bankStatement->user ? h($bankStatement->user->name) : '—',
                ],
                [
                    'label' => __d('expenses', 'Total Credit') . ':',
                    'text' => $this->Number->currency((float)$bankStatement->total_credit)
                        . ' (' . $bankStatement->count_credit . ' ' . __d('expenses', 'entries') . ')',
                ],
                [
                    'label' => __d('expenses', 'Total Debit') . ':',
                    'text' => $this->Number->currency((float)$bankStatement->total_debit)
                        . ' (' . $bankStatement->count_debit . ' ' . __d('expenses', 'entries') . ')',
                ],
                [
                    'label' => __d('expenses', 'Saldo') . ':',
                    'text' => $this->Number->currency((float)$bankStatement->saldo),
                ],
            ],
        ],
        'entries' => [
            'params' => ['class' => 'no-margin'],
            'lines' => [0 => ['table' => [
                'pre' => sprintf(
                    '<style>.bse-trunc{overflow:hidden;white-space:nowrap;text-overflow:ellipsis}</style>'
                    . '<h3 style="margin-top:14px">%s %s</h3>',
                    __d('expenses', 'Entries'),
                    $addEntryLink,
                ),
                'params' => ['style' => 'table-layout:fixed;max-width:100%'],
                'head' => [
                    'rows' => [[
                        'columns' => [
                            ['html' => __d('expenses', 'Date'), 'params' => ['style' => 'width:7em']],
                            ['html' => __d('expenses', 'Ref#'), 'params' => ['style' => 'width:8em']],
                            ['html' => __d('expenses', 'Client'), 'params' => ['style' => 'width:22%']],
                            ['html' => __d('expenses', 'Description')],
                            ['html' => __d('expenses', 'Reference'), 'params' => ['style' => 'width:15%']],
                            ['html' => __d('expenses', 'Debit'),
                                'params' => ['class' => 'right-align', 'style' => 'width:8em']],
                            ['html' => __d('expenses', 'Credit'),
                                'params' => ['class' => 'right-align', 'style' => 'width:8em']],
                            ['html' => '', 'params' => ['style' => 'width:12em']],
                        ],
                    ]],
                ],
                'body' => ['rows' => $bodyRows],
                'foot' => [
                    'rows' => [[
                        'column' => 'td',
                        'columns' => [
                            [
                                'html' => __d('expenses', 'Total') . ':',
                                'params' => ['colspan' => 5, 'style' => 'text-align:right;font-weight:700'],
                            ],
                            [
                                'html' => $this->Number->currency((float)$bankStatement->total_debit),
                                'params' => ['class' => 'right-align', 'style' => 'font-weight:700'],
                            ],
                            [
                                'html' => $this->Number->currency((float)$bankStatement->total_credit),
                                'params' => ['class' => 'right-align', 'style' => 'font-weight:700'],
                            ],
                            ['html' => ''],
                        ],
                    ]],
                ],
            ]]],
        ],
    ],
];

echo $this->Lil->panels($bsView, 'Expenses.BankStatements.view');
$this->Lil->jsReady('M.Tooltip.init(document.querySelectorAll(".tooltipped"));');
$this->Lil->jsReady(sprintf(
    '$("#AddEntryLink").modalPopup({title:%s,processSubmit:true,onJson:function(){window.location.reload();}});',
    json_encode(__d('expenses', 'Add Entry')),
));
$this->Lil->jsReady(sprintf(
    '$(".bse-edit-entry").each(function(){'
    . '$(this).modalPopup({title:%s,processSubmit:true,onJson:function(){window.location.reload();}});'
    . '});',
    json_encode(__d('expenses', 'Edit Entry')),
));
