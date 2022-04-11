<?php
use Cake\Core\Configure;

    $title = h($contact->title);

    $job = '';
if (!empty($contact->job)) {
    $job .= ', ' . h($contact->job);
} else {
    if (!empty($contact->company->title)) {
        $job .= ', ' . __d('crm', 'employed');
    }
}
if (!empty($contact->company->title)) {
    $job .=
        ' ' . __d('crm', 'at') . ' ' .
        $this->Html->link($contact->company->title, [
            'controller' => 'Contacts',
            'action' => 'view',
            $contact->company->id,
        ]);
}
if (!empty($job)) {
    $title .= sprintf('<span class="light">%s</span>', $job);
}

    $contact_view = [
        'title_for_layout' => $title,
        'menu' => [
            'edit' => [
                'title' => __d('crm', 'Edit'),
                'visible' => true,
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'contacts',
                    'action' => 'edit',
                    $contact->id,
                    '?' => ['kind' => $contact->kind],
                ],
            ],
            'delete' => [
                'title' => __d('crm', 'Delete'),
                'visible' => true,
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'contacts',
                    'action' => 'delete',
                    $contact->id,
                    '?' => ['kind' => $contact->kind],
                ],
                'params' => [
                    'confirm' => __d('crm', 'Are you sure you want to delete this contact?'),
                ],
            ],

            'add' => [
                'title' => __d('crm', 'Add'),
                'visible' => true,
                'submenu' => [
                    'add_address' => [
                        'title' => __d('crm', 'Add Address'),
                        'visible' => true,
                        'url' => [
                            'plugin' => 'Crm',
                            'controller' => 'ContactsAddresses',
                            'action' => 'edit',
                            '?' => ['contact' => $contact->id],
                        ],
                        'params' => [
                            'class' => 'AddAddressLink',
                        ],
                    ],

                    'add_email' => [
                        'title' => __d('crm', 'Add Email'),
                        'visible' => true,
                        'url' => [
                            'plugin' => 'Crm',
                            'controller' => 'ContactsEmails',
                            'action' => 'edit',
                            '?' => ['contact' => $contact->id],
                        ],
                        'params' => [
                            'class' => 'AddEmailLink',
                        ],
                    ],

                    'add_phone' => [
                        'title' => __d('crm', 'Add Phone'),
                        'visible' => true,
                        'url' => [
                            'plugin' => 'Crm',
                            'controller' => 'ContactsPhones',
                            'action' => 'edit',
                            '?' => ['contact' => $contact->id],
                        ],
                        'params' => [
                            'class' => 'AddPhoneLink',
                        ],
                    ],

                    'add_account' => [
                        'title' => __d('crm', 'Add Account'),
                        'visible' => true,
                        'url' => [
                            'plugin' => 'Crm',
                            'controller' => 'contacts-accounts',
                            'action' => 'edit',
                            '?' => ['contact' => $contact->id],
                        ],
                        'params' => [
                            'class' => 'AddAccountLink',
                        ],
                    ],
                ],
            ],
        ],
        'entity' => $contact,
        'panels' => [
            'logo' => sprintf(
                '<div id="contact-logo">%1$s</div>',
                $this->Html->image('/crm/img/paperclip.png')
            ),
            'descript' => empty($contact->descript) ? null : [
                'params' => ['id' => 'contact-view-descript'],
                'html' => h($contact->descript),
            ],
            'mat_no' => empty($contact->mat_no) ? null : [
                'params' => ['id' => 'contact-view-mat_no'],
                'lines' => [0 => [
                    'label' => __d('crm', 'Mat no.'),
                    'text' => h($contact->mat_no),
                ]],
            ],
            'tax_no' => empty($contact->tax_no) ? null : [
                'params' => ['id' => 'contact-view-tax_no'],
                'lines' => [0 => [
                    'label' => $contact->tax_status ? __d('crm', 'TAX payee no.') : __d('crm', 'TAX no.'),
                    'text' => h($contact->tax_no),
                ]],
            ],
        ],
    ];

    if (!empty($contact->contacts_addresses)) {
        $countries = Configure::read('Crm.countries');
        $addressTypes = Configure::read('Crm.addressTypes');
        foreach ($contact->contacts_addresses as $address) {
            $contact_view['panels']['addresses']['lines'][] = [
                'label' => __d('crm', 'Address') .
                    ' / ' .
                    h(ucfirst($addressTypes[$address->kind] ?? __d('crm', 'other'))) .
                    ':',
                'text' => implode(' ', [
                    implode(', ', array_filter([
                        $address->street,
                        trim(implode(' ', [$address->zip, $address->city])),
                        h($countries[$address->country_code] ?? $address->country_code),
                    ])),
                    $this->Lil->editLink([
                        'controller' => 'ContactsAddresses',
                        'action' => 'edit',
                        $address->id,
                    ], ['class' => 'edit-element edit-address']),
                    $this->Lil->deleteLink([
                        'controller' => 'ContactsAddresses',
                        'action' => 'delete',
                        $address['id'],
                    ], ['class' => 'delete-element']),
                ]),
            ];
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    if (!empty($contact->contacts_emails)) {
        foreach ($contact->contacts_emails as $email) {
            $label = __d('crm', 'Email') .
                ($email->primary ? '*' : '') .
                ' / ' .
                ucfirst(Configure::read('Crm.emailTypes.' . $email->kind)) . ':';
            $contact_view['panels']['emails']['lines'][] = [
                'label' => $label,
                'text' => implode(' ', [
                    $email->email ?? __d('crm', 'N/A'),
                    $this->Lil->editLink([
                        'controller' => 'ContactsEmails',
                        'action' => 'edit',
                        $email->id,
                    ], ['class' => 'edit-element edit-email']),
                    $this->Lil->deleteLink([
                        'controller' => 'ContactsEmails',
                        'action' => 'delete',
                        $email->id,
                    ], ['class' => 'delete-element']),
                ]),
            ];
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    if (!empty($contact->contacts_phones)) {
        $phoneTypes = Configure::read('Crm.phoneTypes');
        foreach ($contact->contacts_phones as $phone) {
            $contact_view['panels']['phones']['lines'][] = [
                'label' => __d('crm', 'Phone') .
                    ' / ' .
                    h(ucfirst($phoneTypes[$phone->kind] ?? __d('crm', 'other'))) .
                    ':',
                'text' => implode(' ', [
                    $phone->no,
                    $this->Lil->editLink([
                        'controller' => 'ContactsPhones',
                        'action' => 'edit',
                        $phone->id,
                    ], ['class' => 'edit-element edit-phone']),
                    $this->Lil->deleteLink([
                        'controller' => 'ContactsPhones',
                        'action' => 'delete',
                        $phone->id,
                    ], ['class' => 'delete-element']),
                ]),
            ];
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    if (!empty($contact->contacts_accounts)) {
        foreach ($contact->contacts_accounts as $account) {
            $acc_title = __d('crm', 'Unknown');
            $acc_types = Configure::read('Crm.accountTypes');
            if (!empty($acc_types[$account->kind])) {
                $acc_title = ucfirst($acc_types[$account->kind]);
            }

            $contact_view['panels']['accounts']['lines'][] = [
                'label' => __d('crm', 'Account') . ' / ' . $acc_title . ':',
                'text' => implode(' ', [
                    implode(' ', str_split($account->iban ?? '', 4)),
                    $this->Lil->editLink([
                        'controller' => 'ContactsAccounts',
                        'action' => 'edit',
                        $account->id,
                    ], ['class' => 'edit-element edit-account']),
                    $this->Lil->deleteLink([
                        'controller' => 'ContactsAccounts',
                        'action' => 'delete',
                        $account->id,
                    ], ['class' => 'delete-element']),
                ]),
            ];
        }
    }

    $contact_view['panels']['modified'] = [
        'params' => ['id' => 'contact-view-modified'],
        'lines' => [0 => [
            'label' => __d('crm', 'Last modified'),
            'text' => $contact->modified->timeAgoInWords(['accuracy' => 'minute']),
        ]],
    ];

    if (count($employees) > 0) {
        $contact_view['panels']['employees']['id'] = 'PanelViewContactEmployees';
        $contact_view['panels']['employees']['lines'][] = sprintf('<h2>%s</h2>', __d('crm', 'Employees'));

        $employees_table = [
            'parameters' => ['cellspacing' => 0, 'cellpadding' => 0, 'width' => '600'],
            'head' => [
                'rows' => [0 => ['columns' => [
                    'title' => __d('crm', 'Name'),
                    'email' => __d('crm', 'Email'),
                    'phone' => __d('crm', 'Phone'),
                ]]],
            ],
            'foot' => [
                'rows' => [0 => ['columns' => [
                    'empty' => [
                        'parameters' => ['class' => 'right', 'colspan' => 3],
                        'html' => '&nbsp;',
                    ],
                ]]],
            ],
        ];

        $total = 0;
        foreach ($employees as $employee) {
            $employees_table['body']['rows'][]['columns'] = [
                'title' => $this->Html->link($employee->title, [
                    'action' => 'view',
                    $employee->id,
                ]),
                'email' => !empty($employee->primary_email) ?
                    $this->Html->link(
                        $employee->primary_email->email,
                        'mailto:' . $employee->primary_email->email
                    ) : '',
                'phone' => !empty($employee->primary_phone) ? h($employee->primary_phone->no) : '',
            ];
        }

        $contact_view['panels']['employees']['lines'][] = $this->Lil->table($employees_table);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    echo $this->Lil->panels($contact_view, 'Crm.Contacts.view');

    ////////////////////////////////////////////////////////////////////////////////////////////////
    //$js_c = '$("%1$s").click(function(){popup(\'%2$s\', $(this).attr("href"), %3$s); return false;});';
    $js_c = '$("%1$s").each(function() { $(this).modalPopup({title:"%2$s"}); });';
    $this->Lil->jsReady(sprintf($js_c, '.edit-address', __d('crm', 'Edit Address'), '"auto"'));
    $this->Lil->jsReady(sprintf($js_c, '.edit-email', __d('crm', 'Edit Email'), '"auto"'));
    $this->Lil->jsReady(sprintf($js_c, '.edit-phone', __d('crm', 'Edit Phone'), '"auto"'));
    $this->Lil->jsReady(sprintf($js_c, '.edit-account', __d('crm', 'Edit Account'), '"auto"'));

    $this->Lil->jsReady('$(".view-panel").mouseover(function(){' .
        '$(".edit-element, .delete-element", this).show();})' .
        '.mouseout(function(){$(".edit-element, .delete-element", this).hide();}' .
        ');');
    $this->Lil->jsReady('$(".edit-element").hide()');
    $this->Lil->jsReady('$(".delete-element").hide()');

    $this->Lil->jsReady(sprintf('$(".AddAddressLink").modalPopup({title:"%s"})', __d('crm', 'Add new Address')));
    $this->Lil->jsReady(sprintf('$(".AddAccountLink").modalPopup({title:"%s"})', __d('crm', 'Add new Account')));
    $this->Lil->jsReady(sprintf('$(".AddEmailLink").modalPopup({title:"%s"})', __d('crm', 'Add new Email')));
    $this->Lil->jsReady(sprintf('$(".AddPhoneLink").modalPopup({title:"%s"})', __d('crm', 'Add new Phone')));
