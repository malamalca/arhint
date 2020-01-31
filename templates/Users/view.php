<?php
    use Cake\I18n\Date;

    $pageTitle = __('User data for {0}', h($user->name));

    $userView = [
        'title' => $pageTitle,
        'menu' => [
            'edit' => [
                'title' => __('Edit'),
                'visible' => $this->getCurrentUser()->hasRole('admin'),
                'url' => ['action' => 'edit', $user->id],
            ],
            'add-group' => [
                'title' => __('Add Group'),
                'visible' => $this->getCurrentUser()->hasRole('admin'),
                'url' => ['controller' => 'UsersGroups', 'action' => 'add', '?' => ['user' => $user->id]],
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
            'timetrack' => ['lines' => [
                'department' => ['label' => __('Department') . ':', 'html' => $user->department ? h($user->department->title) : __('none')],
            ]],
            'groups' => ['lines' => [
                'title' => '<h3>' . __('Groups') . '</h3>',
                '<div class="row"><div class="col s12 m8 l8">',
                'list' => ['table' => [
                    'params' => ['class' => ''],
                    'head' => ['rows' => [0 => ['columns' => [
                        __('Valid From'),
                        __('Group name'),
                        $this->getCurrentUser()->hasRole('admin') ? '' : null
                    ]]]]
                ]],
                '</div></div>'
            ]]
        ],
    ];

    foreach ($user->groups as $group) {
        $userView['panels']['groups']['lines']['list']['table']['body']['rows'][] = ['columns' => [
            empty($group->_joinData->valid_from) ? '' : $group->_joinData->valid_from->i18nFormat(),
            $this->getCurrentUser()->hasRole('admin') ?
                $this->Html->link($group->title, ['controller' => 'Groups', 'action' => 'edit', $group->id]) :
                $group->title,
            !$this->getCurrentUser()->hasRole('admin') ? null :
                $this->Lil->editLink(['controller' => 'UsersGroups', 'action' => 'edit', $group->_joinData->id]) . ' ' .
                $this->Lil->deleteLink(['controller' => 'UsersGroups', 'action' => 'delete', $group->_joinData->id])
        ]];
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////
    // call plugin handlers and output data
    echo $this->Lil->panels($userView, 'Users.view');
