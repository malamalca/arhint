<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Expenses\Model\Entity\BankStatement> $data
 * @var \Expenses\Filter\BankStatementsFilter $docFilter
 * @var array<string> $ibanList
 */

use Cake\Routing\Router;

// DATE SPAN FILTER DROPDOWN
$spanLabels = [
    'this-month' => __d('expenses', 'This Month'),
    'prev-month' => __d('expenses', 'Previous Month'),
    'last-3-months' => __d('expenses', 'Last 3 Months'),
    'this-year' => __d('expenses', 'This Year'),
];
$currentSpan = $docFilter->get('span');
$spanFilterLabel = is_string($currentSpan) && isset($spanLabels[$currentSpan])
    ? $spanLabels[$currentSpan]
    : __d('expenses', 'Period');
$popupSpan = ['items' => [[
    'title' => __d('expenses', 'All Time'),
    'active' => $currentSpan === null,
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
        'q' => $docFilter->buildQuery('span', null),
    ])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($spanLabels as $spanKey => $spanTitle) {
    $popupSpan['items'][] = [
        'title' => h($spanTitle),
        'active' => $docFilter->check('span', $spanKey),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery(
                'span',
                $docFilter->check('span', $spanKey) ? null : $spanKey,
            ),
        ])],
        'params' => ['class' => 'nowrap'],
    ];
}
$popupSpan = $this->Lil->popup('span', $popupSpan, true);

// IBAN FILTER DROPDOWN
$currentIban = $docFilter->get('iban');
$ibanFilterLabel = is_string($currentIban) && $currentIban !== '' ? $currentIban : __d('expenses', 'IBAN');
$popupIban = ['items' => [[
    'title' => __d('expenses', 'All Accounts'),
    'active' => $currentIban === null || $currentIban === '',
    'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
        'q' => $docFilter->buildQuery('iban', null),
    ])],
    'params' => ['class' => 'nowrap'],
]]];
foreach ($ibanList as $iban) {
    $popupIban['items'][] = [
        'title' => h($iban),
        'active' => $docFilter->check('iban', $iban),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery(
                'iban',
                $docFilter->check('iban', $iban) ? null : $iban,
            ),
        ])],
        'params' => ['class' => 'nowrap'],
    ];
}
$popupIban = $this->Lil->popup('iban', $popupIban, true);

// SORT DROPDOWN
$sortLabel = match ($docFilter->get('sort')) {
    'date-asc' => __d('expenses', 'Oldest First'),
    'date-desc' => __d('expenses', 'Newest First'),
    default => __d('expenses', 'Newest First'),
};
$popupSort = ['items' => [
    [
        'title' => __d('expenses', 'Newest First'),
        'active' => $docFilter->get('sort') === null || $docFilter->check('sort', 'date-desc'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', null),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
    [
        'title' => __d('expenses', 'Oldest First'),
        'active' => $docFilter->check('sort', 'date-asc'),
        'url' => ['?' => array_merge($this->getRequest()->getQuery(), [
            'q' => $docFilter->buildQuery('sort', 'date-asc'),
        ])],
        'params' => ['class' => 'nowrap'],
    ],
]];
$popupSort = $this->Lil->popup('sort', $popupSort, true);

$tableIndex = [
    'title_for_layout' => __d('expenses', 'Bank Statements'),
    'menu' => [
        'import' => [
            'title' => __d('expenses', 'Import XML'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'plugin' => 'Expenses',
                'controller' => 'BankStatements',
                'action' => 'import',
            ],
        ],
    ],
    'pre' => '<div id="panel-index">',
    'post' => '</div>',
    'panels' => [
        'search' => '<div id="panel-search">' .
            sprintf('<form method="get" action="%s">', Router::url()) .
            sprintf(
                '<input name="q" id="query" value="%s" />',
                htmlspecialchars((string)$this->getRequest()->getQuery('q', '')),
            ) .
            '<button type="submit" class="btn-small tonal" id="btn-search">' .
            '<i class="material-icons">search</i></button>' .
            '</form>' .
            ($this->getCurrentUser()->hasRole('editor') ?
                sprintf(
                    '<a href="%2$s" class="btn-small filled" id="btn-import"><i class="material-icons">file_upload</i>%1$s</a>',
                    __d('expenses', 'Import XML'),
                    $this->Url->build([
                        'plugin' => 'Expenses',
                        'controller' => 'BankStatements',
                        'action' => 'import',
                    ]),
                ) : '') .
            '</div>',
        'filter' => '<div id="panel-filter">' .
            '<ul>' .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum%s"'
                . ' data-target="dropdown-iban">%s &#128899;</a></li>',
                is_string($currentIban) && $currentIban !== '' ? ' active' : '',
                h($ibanFilterLabel),
            ) .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum%s"'
                . ' data-target="dropdown-span">%s &#128899;</a></li>',
                $currentSpan !== null ? ' active' : '',
                h($spanFilterLabel),
            ) .
            sprintf(
                '<li><a href="#" class="btn text dropdown-trigger-costum" data-target="dropdown-sort">'
                . '<i class="material-icons">sort</i>%s &#128899;</a></li>',
                h($sortLabel),
            ) .
            '</ul>' .
            $popupIban .
            $popupSpan .
            $popupSort .
            '</div>',
        'rows' => [
            'params' => ['id' => 'panel-list'],
            'lines' => [],
        ],
        'footer' => [
            'params' => ['id' => 'panel-footer'],
            'lines' => [
                '<ul class="paginator">' .
                $this->Paginator->numbers(['first' => 1, 'last' => 1, 'modulus' => 3]) .
                '</ul>',
            ],
        ],
    ],
];

if ($data->items()->isEmpty()) {
    $tableIndex['panels']['rows']['lines'][] =
        '<div id="no-rows-found">' .
        '<h4>' . __d('expenses', 'No bank statements found.') . '</h4>' .
        '<p>' . __d('expenses', 'Import an XML bank statement to get started.') . '</p>' .
        '</div>';
} else {
    foreach ($data as $bankStatement) {
        $tableIndex['panels']['rows']['lines'][] = $this->element('Expenses.bank_statement_row', [
            'bankStatement' => $bankStatement,
            'docFilter' => $docFilter,
        ]);
    }
}

echo $this->Lil->panels($tableIndex, 'Expenses.BankStatements.index');
?>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        const elems = document.querySelectorAll(".dropdown-trigger-costum");
        elems.forEach((dropdown) => {
            M.Dropdown.init(dropdown, {
                constrainWidth: false,
                coverTrigger: false,
            });
        });
    });
</script>
