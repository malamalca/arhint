<?php
$passwordReset = [
    'title_for_layout' => __('Password Reset'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer'],
            ],

            'email' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'email',
                    'options' => [
                        'label' => __('Email') . ':',
                        'error' => __('Email is required, format must be valid.'),
                    ],
                ],
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __('Request new Password'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($passwordReset, 'Users.reset');
