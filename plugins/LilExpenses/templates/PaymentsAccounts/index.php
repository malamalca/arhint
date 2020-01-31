<?php
    $accountIndex = [
        'title_for_layout' => __d('lil_expenses', 'Account List'),
        'menu' => [
            'add' => [
                'title' => __d('lil_expenses', 'Add'),
                'visible' => true,
                'url' => [
                    'plugin' => 'LilExpenses',
                    'controller' => 'PaymentsAccounts',
                    'action' => 'add',
                ],
            ],
        ],
        'table' => [
            'parameters' => [
                'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0,
                'id' => 'PaymentsAccountsIndex', 'class' => 'index',
            ],
            'head' => ['rows' => [['columns' => [
                'title' => __d('lil_expenses', 'Title'),
                'active' => [
                    'parameters' => ['class' => 'center'],
                    'html' => __d('lil_expenses', 'Active'),
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
                    '<input type="checkbox" disabled="disabled" class="browser-default" %s/>',
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
    echo $this->Lil->index($accountIndex, 'LilExpenses.PaymentsAccounts.index');
