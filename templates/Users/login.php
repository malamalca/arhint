<?php
    use Cake\Core\Configure;

    $userLogin = [
        'title_for_layout' => __('Login'),
        'form' => [
            'pre' => '<div class="form row" id="users-login"><div class="col s12 m6 l6">',
            'post' => '</div></div>',
            'defaultHelper' => $this->Form,
            'lines' => [
                'form_start' => [
                    'method' => 'create',
                ],
                'username' => [
                    'method' => 'control',
                    'parameters' => [
                        'username',
                        [
                            'label' => __('Username') . ':',
                            'type' => 'text'
                        ]
                    ]
                ],
                'password' => [
                    'method' => 'control',
                    'parameters' => [
                        'passwd',
                        [
                            'label' => __('Password') . ':',
                            'type' => 'password'
                        ]
                    ]
                ],
                'remember_me' => [
                    'method' => 'control',
                    'parameters' => [
                        'remember_me',
                        [
                            'type' => 'checkbox',
                            'label' => __('Remember me on this computer'),
                        ]
                    ]
                ],
                'submit' => [
                    'method' => 'submit',
                    'parameters' => [__('OK')]
                ],
                'form_end' => [
                    'method'  => 'end',
                ],
                'passwd_reset' => sprintf(
                    '<div id="UserLoginPasswordReset">%s</div>',
                    $this->Html->link(__('Forgot your password?'), [
                        'plugin' => false,
                        'controller' => 'Users',
                        'action' => 'reset',
                    ])
                ),
            ]
        ]
    ];

    echo $this->Lil->form($userLogin, 'Users.login');
