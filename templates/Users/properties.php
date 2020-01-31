<?php
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\Routing\Router;

$userForm = [
    'title_for_layout' => __('My Properties'),
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
                    'readonly' => true
                ]]
            ],
            'email' => [
                'method' => 'control',
                'parameters' => ['email', [
                    'type' => 'text',
                    'label' => __('Email') . ':',
                ]]
            ],
            'fs_basics_end' => '</fieldset>',

            'fs_login_start' => '<fieldset>',
            'lg_login' => sprintf('<legend>%s</legend>', __('Change Password')),
            'old-passwd' => [
                'method' => 'control',
                'parameters' => ['old_passwd', [
                    'type' => 'password',
                    'label' => __('Current Password') . ':',
                    'value' => ''
                ]]
            ],
            'passwd' => [
                'method' => 'control',
                'parameters' => ['passwd', [
                    'type' => 'password',
                    'label' => __('Password') . ':',
                    'value' => ''
                ]]
            ],
            'repeat-passwd' => [
                'method' => 'control',
                'parameters' => ['repeat_passwd', [
                    'type' => 'password',
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

echo $this->Lil->form($userForm, 'User.properties');
