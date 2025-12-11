<?php
use Cake\Routing\Router;

$changePassword = [
    'title_for_layout' => __('Set new password for {0}', h($user->name)),
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
            ],
            'reset_key' => [
                'method' => 'hidden',
                'parameters' => ['reset_key1'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', [
                    'default' => ($redirect = $this->getRequest()->getQuery('redirect')) ?
                        Router::url($redirect, true) : null,
                ]],
            ],

            'passwd' => [
                'method' => 'control',
                'parameters' => [
                    'passwd',
                    [
                        'type' => 'password',
                        'label' => __('New Password') . ':',
                        'error' => __('Password is required, format must be valid.'),
                        'value' => '',
                    ],
                ],
            ],
            'repeat_passwd' => [
                'method' => 'control',
                'parameters' => [
                    'repeat_passwd',
                    [
                        'type' => 'password',
                        'label' => __('Repeat Password') . ':',
                        'error' => __('Passwords do not match.'),
                        'value' => '',
                    ],
                ],
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __('Change'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($changePassword, 'Users.change_password');
