<?php
use Cake\Core\Configure;

if ($email->id) {
    $title = __d('crm', 'Edit Email');
} else {
    $title = __d('crm', 'Add an Email');
}

    $editForm = [
        'title_for_layout' => $title,
        'form' => [
            'pre' => '<div class="form">',
            'post' => '</div>',
            'defaultHelper' => $this->Form,
            'lines' => [
                'form_start' => [
                    'method' => 'create',
                    'parameters' => [$email, [
                        'id' => 'contacts-email-form',
                        'idPrefix' => 'contact-email',
                    ]],
                ],
                'id' => [
                    'method' => 'control',
                    'parameters' => ['id', 'options' => ['type' => 'hidden']],
                ],
                'contact_id' => [
                    'method' => 'control',
                    'parameters' => ['contact_id', 'options' => ['type' => 'hidden']],
                ],
                'referer' => [
                    'method' => 'control',
                    'parameters' => ['referer', 'options' => ['type' => 'hidden']],
                ],

                'kind' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'kind',
                        'options' => [
                            'type' => 'select',
                            'label' => [
                                'text' => __d('crm', 'Kind') . ':',
                                'class' => 'active',
                            ],
                            'options' => Configure::read('Crm.emailTypes'),
                            'error' => [
                                'kindOccupied' => __d('crm', 'Entry of this type already exists.'),
                            ],
                            'class' => 'browser-default',
                        ],
                    ],
                ],

                //'fs_main_start' => sprintf('<fieldset><legend>%s</legend>', __d('crm', 'Email')),
                'bban' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'email',
                        'options' => ['label' => __d('crm', 'Email') . ':'],
                    ],
                ],
                //'fs_main_end' => '</fieldset>',
                'primary' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'primary',
                        'options' => [
                            'type' => 'checkbox',
                            'label' => __d('crm', 'This is a primary email'),
                            'default' => false,
                        ],
                    ],
                ],
                'submit' => [
                    'method' => 'submit',
                    'parameters' => ['label' => __d('crm', 'Save')],
                ],
                'form_end' => [
                    'method' => 'end',
                    'parameters' => [],
                ],
            ],
        ],
    ];

    echo $this->Lil->form($editForm, 'Crm.ContactsEmails.edit');
