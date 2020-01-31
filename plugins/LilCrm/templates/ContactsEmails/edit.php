<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

if ($email->id) {
    $title = __d('lil_crm', 'Edit Email');
} else {
    $title = __d('lil_crm', 'Add an Email');
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
                                'text' => __d('lil_crm', 'Kind') . ':',
                                'class' => 'active',
                            ],
                            'options' => Configure::read('LilCrm.emailTypes'),
                            'error' => [
                                'kindOccupied' => __d('lil_crm', 'Entry of this type already exists.'),
                            ],
                            'class' => 'browser-default',
                        ],
                    ],
                ],

                //'fs_main_start' => sprintf('<fieldset><legend>%s</legend>', __d('lil_crm', 'Email')),
                'bban' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'email',
                        'options' => ['label' => __d('lil_crm', 'Email') . ':'],
                    ],
                ],
                //'fs_main_end' => '</fieldset>',
                'primary' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'primary',
                        'options' => [
                            'type' => 'checkbox',
                            'label' => __d('lil_crm', 'This is a primary email'),
                            'default' => false,
                        ],
                    ],
                ],
                'submit' => [
                    'method' => 'submit',
                    'parameters' => ['label' => __d('lil_crm', 'Save')],
                ],
                'form_end' => [
                    'method' => 'end',
                    'parameters' => [],
                ],
            ],
        ],
    ];

    echo $this->Lil->form($editForm, 'LilCrm.ContactsEmails.edit');
