<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Expenses\Model\Entity\BankStatementEntry> $bankStatementEntries
 * @var string|null $statementId
 */

// Standalone entry listing (embedded listing is in BankStatements/view.php)
$tableIndex = [
    'title_for_layout' => __d('expenses', 'Bank Statement Entries'),
    'menu' => [],
    'panels' => [
        'rows' => [
            'params' => ['id' => 'panel-list'],
            'lines'  => [],
        ],
    ],
];

foreach ($bankStatementEntries as $entry) {
    $tableIndex['panels']['rows']['lines'][] = sprintf(
        '<div class="panel-row"><div class="title">%s – %s</div><div class="total">%s / %s</div></div>',
        h((string)$entry->dat_issue),
        h((string)$entry->client),
        $this->Number->currency((float)$entry->debit),
        $this->Number->currency((float)$entry->credit),
    );
}

echo $this->Lil->panels($tableIndex, 'Expenses.BankStatementEntries.index');
