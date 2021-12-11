<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$userForm = [
    'title_for_layout' => __('Edit User'),
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$user]
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id']
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', [
                    'default' => base64_encode($this->getRequest()->referer())
                ]]
            ],

            'fs_basics_start' => '<fieldset>',
            'lg_basics' => sprintf('<legend>%s</legend>', __('Basics')),

            'name' => [
                'method' => 'control',
                'parameters' => ['name', [
                    'type' => 'text',
                    'label' => __('Name') . ':',
                ]]
            ],
            'email' => [
                'method' => 'control',
                'parameters' => ['email', [
                    'type' => 'text',
                    'label' => __('Email') . ':',
                ]]
            ],
            'active' => [
                'method' => 'control',
                'parameters' => ['active', [
                    'type' => 'checkbox',
                    'label' => __('Active User')
                ]]
            ],
            'hidden' => [
                'method' => 'control',
                'parameters' => ['hidden', [
                    'type' => 'checkbox',
                    'label' => __('Hidden User')
                ]]
            ],
            'fs_basics_end' => '</fieldset>',

            'fs_login_start' => '<fieldset>',
            'lg_login' => sprintf('<legend>%s</legend>', __('Login')),

            'privileges' => [
                'method' => 'control',
                'parameters' => ['privileges', [
                    'type' => 'select',
                    'label' => __('Privileges') . ':',
                    'enabled' => $this->getCurrentUser()->hasRole('admin'),
                    'options' => [
                        '5' => __('Admin'),
                        '7' => __('Group Admin'),
                        '10' => __('Editor'),
                        '15' => __('Reader')
                    ]
                ]]
            ],
            'username' => [
                'method' => 'control',
                'parameters' => ['username', [
                    'type' => 'text',
                    'label' => __('Username') . ':',
                    'enabled' => $this->getCurrentUser()->hasRole('admin')
                ]]
            ],
            'passwd' => [
                'method' => 'control',
                'parameters' => ['passwd', [
                    'type' => 'text',
                    'label' => __('Password') . ':',
                    'value' => ''
                ]]
            ],
            'repeat-passwd' => [
                'method' => 'control',
                'parameters' => ['repeat_passwd', [
                    'type' => 'text',
                    'label' => __('Repeat Password') . ':',
                    'value' => ''
                ]]
            ],
            'fs_login_end' => '</fieldset>',

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __('Save')
                ]
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => []
            ],
        ]
    ]
];

echo $this->Lil->form($userForm, 'User.edit');
