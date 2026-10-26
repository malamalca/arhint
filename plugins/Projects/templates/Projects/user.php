<?php
/**
 * @var \Projects\Model\Entity\ProjectsUser $projectsUser
 * @var array<string, mixed> $users
 * @var \App\Model\Entity\User $newUser
 * @var array<string, string> $counters
 */

$isNewUser = (bool)$this->getRequest()->getData('new_user') || empty($users);
$canCreate = $this->getCurrentUser()->hasRole('admin') && !$projectsUser->user_id;

$newUserErrors = '';
foreach ($newUser->getErrors() as $messages) {
    foreach ((array)$messages as $message) {
        $newUserErrors .= '<div class="error-message">' . h($message) . '</div>';
    }
}

$this->Html->script('Projects.projects-user.js', ['block' => 'script']);

$lines = [
    'form_start' => [
        'method' => 'create',
        'parameters' => ['model' => $projectsUser],
    ],
    'id' => [
        'method' => 'hidden',
        'parameters' => ['field' => 'id'],
    ],
    'project_id' => [
        'method' => 'hidden',
        'parameters' => ['field' => 'project_id'],
    ],
    'referer' => [
        'method' => 'hidden',
        'parameters' => ['field' => 'referer'],
    ],
];

if ($canCreate) {
    $lines['new_user'] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'new_user',
            'options' => [
                'type' => 'checkbox',
                'label' => __d('projects', 'Create a new user'),
                'checked' => $isNewUser,
                'id' => 'ProjectsUserNew',
            ],
        ],
    ];
}

$lines['existing_start'] = '<div id="ProjectsUserExisting"' . ($isNewUser ? ' style="display: none;"' : '') . '>';
$lines['user'] = [
    'method' => 'control',
    'parameters' => [
        'field' => 'user_id',
        'options' => [
            'type' => 'select',
            'options' => $users,
            'label' => __d('projects', 'User') . ':',
        ],
    ],
];
$lines['existing_end'] = '</div>';

if ($canCreate) {
    $lines['new_start'] = '<div id="ProjectsUserNewFields"' . ($isNewUser ? '' : ' style="display: none;"') . '>';
    $lines['new_errors'] = $newUserErrors;
    $lines['new_name'] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'new_name',
            'options' => [
                'type' => 'text',
                'label' => __d('projects', 'Name') . ':',
                'maxlength' => 50,
            ],
        ],
    ];
    $lines['new_email'] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'new_email',
            'options' => [
                'type' => 'email',
                'label' => __d('projects', 'Email') . ':',
            ],
        ],
    ];
    $lines['new_privileges'] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'new_privileges',
            'options' => [
                'type' => 'select',
                'label' => __d('projects', 'Privileges') . ':',
                'options' => [
                    '7' => __d('projects', 'Group Admin'),
                    '10' => __d('projects', 'Editor'),
                    '15' => __d('projects', 'Reader'),
                ],
                'default' => '10',
            ],
        ],
    ];
    if ($counters) {
        $lines['new_counters'] = [
            'method' => 'control',
            'parameters' => [
                'field' => 'new_counters',
                'options' => [
                    'type' => 'select',
                    'multiple' => true,
                    'options' => $counters,
                    'label' => __d('projects', 'Document counters') . ':',
                ],
            ],
        ];
    }
    $lines['new_info'] = '<p class="small">' . h(__d(
        'projects',
        'The new user gets access to this project and to the Projects and Documents plugins, and receives an email with login instructions.',
    )) . '</p>';
    $lines['new_end'] = '</div>';
}

$lines['submit'] = [
    'method' => 'button',
    'parameters' => [
        __d('projects', 'Save'),
    ],
];
$lines['form_end'] = [
    'method' => 'end',
    'parameters' => [],
];

$editForm = [
    'title_for_layout' =>
        $projectsUser->id ? __d('projects', 'Edit Project User') : __d('projects', 'Add Project User'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'defaultHelper' => $this->Form,
        'lines' => $lines,
    ],
];

echo $this->Lil->form($editForm, 'Projects.ProjectsUsers.edit');
