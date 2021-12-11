<?php

$activeCaption = __('All');
if ($this->getRequest()->getQuery('active') === "0") {
    $activeCaption = __('Inactive');
}
if ($this->getRequest()->getQuery('active') === "1") {
    $activeCaption = __('Active');
}
$activeLink = $this->Html->link($activeCaption, '#', ['id' => 'filter-active', 'class' => 'dropdown-trigger', 'data-target' => 'dropdown-active']);

$dropdownActive = ['items' => [
    ['title' => __('All'), 'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['active' => null])]],
    ['title' => __('Active'), 'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['active' => 1])]],
    ['title' => __('Inactive'), 'url' => ['?' => array_merge($this->getRequest()->getQuery(), ['active' => 0])]]
]];
$dropdownActive = $this->Lil->popup('active', $dropdownActive, true);

$usersTitle = __('{0} Users', $activeLink);

$usersIndex = [
    'title' => $usersTitle,
    'menu' => [
        'add' => [
            'title' => __('Add'),
            'visible' => $this->getCurrentUser()->hasRole('admin'),
            'url' => [
                'action' => 'edit',
            ]
        ]
    ],
    'actions' => [
        'lines' => [
            $dropdownActive
        ]
    ],
    'table' => [
        'head' => [
            'rows' => [
                ['columns' =>
                    [
                        'icon' => [
                            'params' => ['class' => 'center-align'],
                            'html' => '&nbsp;'
                        ],
                        'title' => __('Name'),
                        ''
                    ],
                ]]],
    ]
];

foreach ($users as $user) {
    $usersIndex['table']['body']['rows'][]['columns'] = [
        'icon' => [
            'params' => ['class' => 'center-align'],
            'html' => ''
        ],
        'title' => [
            'html' => $this->Html->link(
                $user->name,
                ['action' => 'view', $user->id],
                ['class' => [$user->active == 0 ? 'strikethrough' : null]]
            )
        ],
        'actions' => [
            'params' => ['class' => 'actions right'],
            'html' => $this->Lil->editLink($user->id) .
                $this->Lil->deleteLink($user->id)
        ]
    ];
}

///////////////////////////////////////////////////////////////////////////////////////////////
// call plugin handlers and output data
echo $this->Lil->index($usersIndex, 'Users.index');
