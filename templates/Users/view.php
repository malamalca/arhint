<?php
    $pageTitle = __('User data for {0}', h($user->name));

    $userView = [
        'title' => $pageTitle,
        'menu' => [
            'edit' => [
                'title' => __('Edit'),
                'visible' => $this->getCurrentUser()->hasRole('admin'),
                'url' => ['action' => 'edit', $user->id],
            ],
            'delete' => [
                'title' => __('Delete'),
                'visible' => $this->getCurrentUser()->hasRole('admin'),
                'url' => ['action' => 'delete', $user->id],
                'params' => [
                    'confirm' => __('Are you sure you want to delete this user?')
                ]
            ]
        ],
        'actions' => [
            'lines' => [
            ]
        ],
        'panels' => [
            'basics' => ['lines' => [
                'name' => ['label' => __('Name') . ':', 'html' => h($user->name)],
                'username' => ['label' => __('Username') . ':', 'html' => h($user->username)],
                'email' => ['label' => __('Email') . ':', 'html' => h($user->email)]
            ]],
        ],
    ];

    ///////////////////////////////////////////////////////////////////////////////////////////////
    // call plugin handlers and output data
    echo $this->Lil->panels($userView, 'Users.view');
