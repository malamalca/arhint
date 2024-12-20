<?php
    $accountIndex = [
        'title_for_layout' => __d('expenses', 'Account List'),
        'menu' => [
            'add' => [
                'title' => __d('expenses', 'Add'),
                'visible' => true,
                'url' => [
                    'plugin' => 'Expenses',
                    'controller' => 'PaymentsAccounts',
                    'action' => 'edit',
                ],
            ],
        ],
        'table' => [
            'parameters' => [
                'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'PaymentsAccountsIndex',
            ],
            'head' => ['rows' => [['columns' => [
                'title' => __d('expenses', 'Title'),
                'active' => [
                    'parameters' => ['class' => 'center'],
                    'html' => __d('expenses', 'Active'),
                 ],
                 'actions' => '',
            ]]]],
        ],
    ];

    foreach ($accounts as $item) {
        $accountIndex['table']['body']['rows'][]['columns'] = [
            'title' => [
                'html' => h($item->title),
            ],
            'active' => [
                'parameters' => ['class' => 'center'],
                'html' => sprintf(
                    '<input type="checkbox" disabled="disabled" %s/>',
                    $item->active ? 'checked="checked" ' : ''
                ),
            ],
            'actions' => [
                'params' => ['class' => 'right-align'],
                'html' => $this->Lil->editLink($item->id) . ' ' . $this->Lil->deleteLink($item->id),
            ],
        ];
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////
    // call plugin handlers and output data
    echo $this->Lil->index($accountIndex, 'Expenses.PaymentsAccounts.index');
