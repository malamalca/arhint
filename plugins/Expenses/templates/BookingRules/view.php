<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BookingRule $bookingRule
 * @var array<string, string> $modelLabels
 */

use Expenses\Model\Entity\BookingRuleFilter;

// ---- Filters rows ----
$filterBodyRows = [];
foreach ($bookingRule->booking_rule_filters ?? [] as $filter) {
    $brackets = str_repeat('(', (int)$filter->left_bracket_count)
        . str_repeat(')', (int)$filter->right_bracket_count);

    $operatorLabels = BookingRuleFilter::operatorLabels();
    $opLabel = $operatorLabels[$filter->operator] ?? $filter->operator;

    $endOp = $filter->end_operator
        ? '<strong>' . h(strtoupper((string)$filter->end_operator)) . '</strong>'
        : '—';

    $filterBodyRows[] = [
        'columns' => [
            ['html' => h(str_repeat('(', (int)$filter->left_bracket_count))],
            ['html' => h($filter->field)],
            ['html' => h($opLabel)],
            ['html' => h($filter->value)],
            ['html' => h(str_repeat(')', (int)$filter->right_bracket_count))],
            ['html' => $endOp],
            [
                'html' => $this->Lil->editLink([
                    'plugin'     => 'Expenses',
                    'controller' => 'BookingRuleFilters',
                    'action'     => 'edit',
                    $filter->id,
                ], ['class' => 'br-edit-filter']) . ' ' . $this->Lil->deleteLink([
                    'plugin'     => 'Expenses',
                    'controller' => 'BookingRuleFilters',
                    'action'     => 'delete',
                    $filter->id,
                ], ['confirm' => __d('expenses', 'Are you sure you want to delete this filter?')]),
                'params' => ['class' => 'right-align nowrap'],
            ],
        ],
    ];
}

// ---- Account entries rows ----
$entryBodyRows = [];
foreach ($bookingRule->booking_rule_account_entries ?? [] as $entry) {
    $accountLabel = $entry->account
        ? h($entry->account->code . ' – ' . $entry->account->name)
        : h((string)$entry->account_id);

    $entryBodyRows[] = [
        'columns' => [
            ['html' => $accountLabel],
            ['html' => h($entry->value)],
            [
                'html' => $this->Lil->editLink([
                    'plugin'     => 'Expenses',
                    'controller' => 'BookingRuleAccountEntries',
                    'action'     => 'edit',
                    $entry->id,
                ], ['class' => 'br-edit-account-entry']) . ' ' . $this->Lil->deleteLink([
                    'plugin'     => 'Expenses',
                    'controller' => 'BookingRuleAccountEntries',
                    'action'     => 'delete',
                    $entry->id,
                ], ['confirm' => __d('expenses', 'Are you sure you want to delete this account entry?')]),
                'params' => ['class' => 'right-align nowrap'],
            ],
        ],
    ];
}

$addFilterLink = $this->Html->link(
    '+ ' . __d('expenses', 'Add filter'),
    [
        'plugin'     => 'Expenses',
        'controller' => 'BookingRuleFilters',
        'action'     => 'edit',
        '?'          => ['rule_id' => $bookingRule->id],
    ],
    ['class' => 'btn btn-small filled', 'id' => 'AddFilterLink'],
);

$addEntryLink = $this->Html->link(
    '+ ' . __d('expenses', 'Add account entry'),
    [
        'plugin'     => 'Expenses',
        'controller' => 'BookingRuleAccountEntries',
        'action'     => 'edit',
        '?'          => ['rule_id' => $bookingRule->id],
    ],
    ['class' => 'btn btn-small filled', 'id' => 'AddAccountEntryLink'],
);

$brView = [
    'title_for_layout' => __d('expenses', 'Booking Rule: {0}', h($bookingRule->title)),
    'menu' => [
        'edit' => [
            'title' => __d('expenses', 'Edit'),
            'url'   => ['action' => 'edit', $bookingRule->id],
        ],
        'delete' => [
            'title'  => __d('expenses', 'Delete'),
            'url'    => ['action' => 'delete', $bookingRule->id],
            'params' => ['confirm' => __d('expenses', 'Are you sure you want to delete this booking rule?')],
        ],
    ],
    'panels' => [
        'header' => [
            'lines' => [
                ['label' => __d('expenses', 'Model') . ':',
                    'text' => h($modelLabels[$bookingRule->model] ?? $bookingRule->model)],
                ['label' => __d('expenses', 'Title') . ':',
                    'text' => h($bookingRule->title)],
            ],
        ],
        'filters' => [
            'params' => ['class' => 'no-margin'],
            'lines' => [0 => ['table' => [
                'pre' => sprintf(
                    '<h3 style="margin-top:14px">%s %s</h3>',
                    __d('expenses', 'Filters'),
                    $addFilterLink,
                ),
                'head' => [
                    'rows' => [[
                        'columns' => [
                            ['html' => '(', 'params' => ['style' => 'width:2em;text-align:center']],
                            ['html' => __d('expenses', 'Field')],
                            ['html' => __d('expenses', 'Operator')],
                            ['html' => __d('expenses', 'Value')],
                            ['html' => ')', 'params' => ['style' => 'width:2em;text-align:center']],
                            ['html' => __d('expenses', 'Connect'), 'params' => ['style' => 'width:5em']],
                            ['html' => '', 'params' => ['style' => 'width:5em']],
                        ],
                    ]],
                ],
                'body' => ['rows' => $filterBodyRows],
            ]]],
        ],
        'account_entries' => [
            'params' => ['class' => 'no-margin'],
            'lines' => [0 => ['table' => [
                'pre' => sprintf(
                    '<h3 style="margin-top:14px">%s %s</h3>',
                    __d('expenses', 'Account Entries Template'),
                    $addEntryLink,
                ),
                'head' => [
                    'rows' => [[
                        'columns' => [
                            ['html' => __d('expenses', 'Account')],
                            ['html' => __d('expenses', 'Value (field or amount)')],
                            ['html' => '', 'params' => ['style' => 'width:5em']],
                        ],
                    ]],
                ],
                'body' => ['rows' => $entryBodyRows],
            ]]],
        ],
    ],
];

echo $this->Lil->panels($brView, 'Expenses.BookingRules.view');

// Open filter edit in a modal popup
$this->Lil->jsReady(sprintf(
    '$("#AddFilterLink").modalPopup({title:%s,processSubmit:true,onJson:function(){window.location.reload();}});',
    json_encode(__d('expenses', 'Add Filter')),
));
$this->Lil->jsReady(sprintf(
    '$(".br-edit-filter").each(function(){'
    . '$(this).modalPopup({title:%s,processSubmit:true,onJson:function(){window.location.reload();}});'
    . '});',
    json_encode(__d('expenses', 'Edit Filter')),
));

// Open account entry edit in a modal popup
$this->Lil->jsReady(sprintf(
    '$("#AddAccountEntryLink").modalPopup({title:%s,processSubmit:true,onJson:function(){window.location.reload();}});',
    json_encode(__d('expenses', 'Add Account Entry')),
));
$this->Lil->jsReady(sprintf(
    '$(".br-edit-account-entry").each(function(){'
    . '$(this).modalPopup({title:%s,processSubmit:true,onJson:function(){window.location.reload();}});'
    . '});',
    json_encode(__d('expenses', 'Edit Account Entry')),
));
