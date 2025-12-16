<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

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
    $title .= sprintf('<span class="small">%s</span>', $job);
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
                $this->Html->image('/crm/img/paperclip.png'),
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
        $addresses_table = [
            'parameters' => ['class' => 'contact-details-table'],
        ];
        foreach ($contact->contacts_addresses as $address) {
            $addresses_table['body']['rows'][]['columns'] = [
                'primary' => [
                    'parameters' => ['class' => 'starred'],
                    'html' => $address->primary ? '<i class="material-icons tiny">star</i>' : '',
                ],
                'address' => implode(' ', [
                    implode(', ', array_filter([
                        $address->street,
                        trim(implode(' ', [$address->zip, $address->city])),
                        h($countries[$address->country_code] ?? $address->country_code),
                    ])),
                ]),
                'actions' => [
                    'parameters' => ['class' => 'actions'],
                    'html' =>
                        implode(' ', [
                        $this->Lil->editLink([
                            'controller' => 'ContactsAddresses',
                            'action' => 'edit',
                            $address->id,
                        ], ['class' => 'edit-element edit-email tiny']),
                        $this->Lil->deleteLink([
                            'controller' => 'ContactsAddresses',
                            'action' => 'delete',
                            $address->id,
                        ], ['class' => 'delete-element tiny']),
                    ]),
                ],
            ];
        }
        $contact_view['panels']['addresses']['lines'][0] = [
            'label' => __d('crm', 'Addresses') . ':',
            'text' => $this->Lil->table($addresses_table),
        ];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    if (!empty($contact->contacts_emails)) {
        $emails_table = [
            'parameters' => ['class' => 'contact-details-table'],
        ];
        foreach ($contact->contacts_emails as $email) {
            $emails_table['body']['rows'][]['columns'] = [
                'primary' => [
                    'parameters' => ['class' => 'starred'],
                    'html' => $email->primary ? '<i class="material-icons tiny">star</i>' : '',
                ],
                'email' => h($email->email ?? __d('crm', 'N/A')),
                'actions' => [
                    'parameters' => ['class' => 'actions'],
                    'html' =>
                        implode(' ', [
                        $this->Lil->editLink([
                            'controller' => 'ContactsEmails',
                            'action' => 'edit',
                            $email->id,
                        ], ['class' => 'edit-element edit-email tiny']),
                        $this->Lil->deleteLink([
                            'controller' => 'ContactsEmails',
                            'action' => 'delete',
                            $email->id,
                        ], ['class' => 'delete-element tiny']),
                    ]),
                ],
            ];
        }
        $contact_view['panels']['emails']['lines'][0] = [
            'label' => __d('crm', 'Emails') . ':',
            'text' => $this->Lil->table($emails_table),
        ];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    if (!empty($contact->contacts_phones)) {
        $phoneTypes = Configure::read('Crm.phoneTypes');
        $phones_table = [
            'parameters' => ['class' => 'contact-details-table'],
        ];
        foreach ($contact->contacts_phones as $phone) {
            $phones_table['body']['rows'][]['columns'] = [
                'primary' => [
                    'parameters' => ['class' => 'starred'],
                    'html' => $phone->primary ? '<i class="material-icons tiny">star</i>' : '',
                ],
                'phone' => h($phone->no ?? __d('crm', 'N/A')),
                'actions' => [
                    'parameters' => ['class' => 'actions'],
                    'html' =>
                        implode(' ', [
                        $this->Lil->editLink([
                            'controller' => 'ContactsPhones',
                            'action' => 'edit',
                            $phone->id,
                        ], ['class' => 'edit-element edit-phone tiny']),
                        $this->Lil->deleteLink([
                            'controller' => 'ContactsPhones',
                            'action' => 'delete',
                            $phone->id,
                        ], ['class' => 'delete-element tiny']),
                    ]),
                ],
            ];
        }
        $contact_view['panels']['phones']['lines'][0] = [
            'label' => __d('crm', 'Phones') . ':',
            'text' => $this->Lil->table($phones_table),
        ];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    if (!empty($contact->contacts_accounts)) {
        $accounts_table = [
            'parameters' => ['class' => 'contact-details-table'],
        ];
        foreach ($contact->contacts_accounts as $account) {
            $accounts_table['body']['rows'][]['columns'] = [
                'primary' => [
                    'parameters' => ['class' => 'starred'],
                    'html' => $account->primary ? '<i class="material-icons tiny">star</i>' : '',
                ],
                'account' => h($account->iban ?? __d('crm', 'N/A')),
                'actions' => [
                    'parameters' => ['class' => 'actions'],
                    'html' =>
                        implode(' ', [
                        $this->Lil->editLink([
                            'controller' => 'ContactsAccounts',
                            'action' => 'edit',
                            $account->id,
                        ], ['class' => 'edit-element edit-account tiny']),
                        $this->Lil->deleteLink([
                            'controller' => 'ContactsAccounts',
                            'action' => 'delete',
                            $account->id,
                        ], ['class' => 'delete-element tiny']),
                    ]),
                ],
            ];
        }
        $contact_view['panels']['accounts']['lines'][0] = [
            'label' => __d('crm', 'Accounts') . ':',
            'text' => $this->Lil->table($accounts_table),
        ];
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
                        'parameters' => ['colspan' => 3],
                        'html' => '',
                    ],
                ]]],
            ],
        ];

        foreach ($employees as $employee) {
            $employees_table['body']['rows'][]['columns'] = [
                'title' => $this->Html->link($employee->title ?? __d('crm', 'Unknown'), [
                    'action' => 'view',
                    $employee->id,
                ]),
                'email' => !empty($employee->primary_email) ?
                    $this->Html->link(
                        $employee->primary_email->email,
                        'mailto:' . $employee->primary_email->email,
                    ) : '',
                'phone' => !empty($employee->primary_phone) ? h($employee->primary_phone->no) : '',
            ];
        }

        $contact_view['panels']['employees']['lines'][] = $this->Lil->table($employees_table);
    }

    $contact_view['panels']['tabs'] = ['lines' => [
        'pre' => '<div class="row view-panel"><div class="col s12"><ul id="ContactTabs" class="tabs">',

        'logs' => sprintf(
            '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
            $this->Url->build([$this->getRequest()->getParam('pass.0'), '?' => ['tab' => 'logs']]),
            __d('crm', 'Logs'),
            ($this->getRequest()->getQuery('tab') == 'logs' || !$this->getRequest()->getQuery('tab'))  ? ' class="active"' : ''
        ),

        'post' => '</ul></div>',
    ]];

    $activeTab = $this->getRequest()->getQuery('tab', 'logs');
    $this->set('tab', $activeTab);

    if ($activeTab == 'logs') {
        $contact_view['panels']['logs'] = '<div id="tab-content-logs"></div>';

        $sourceRequest = Router::reverseToArray($this->getRequest());
        unset($sourceRequest['?']['page']);
        unset($sourceRequest['?']['sort']);
        unset($sourceRequest['?']['direction']);

        $url = Router::normalize($sourceRequest);
        $params = [
            'source' => $url,
            'page' => $this->getRequest()->getQuery('page'),
            'sort' => $this->getRequest()->getQuery('sort'),
            'direction' => $this->getRequest()->getQuery('direction'),
        ];

        $url = Router::url([
            'controller' => 'ContactsLogs',
            'action' => 'index',
            '_ext' => 'aht',
            '?' => $params,
        ]);
        $this->Lil->jsReady('$.get("' . $url . '", function(data) { $("#tab-content-logs").html(data); });');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    echo $this->Lil->panels($contact_view, 'Crm.Contacts.view');

    ////////////////////////////////////////////////////////////////////////////////////////////////
    $js_c = '$("%1$s").each(function() { $(this).modalPopup({title:"%2$s", processSubmit: true, onJson: function(data, dialog) { window.location.reload(); }}); });';
    $this->Lil->jsReady(sprintf($js_c, '.edit-address', __d('crm', 'Edit Address'), '"auto"'));
    $this->Lil->jsReady(sprintf($js_c, '.edit-email', __d('crm', 'Edit Email'), '"auto"'));
    $this->Lil->jsReady(sprintf($js_c, '.edit-phone', __d('crm', 'Edit Phone'), '"auto"'));
    $this->Lil->jsReady(sprintf($js_c, '.edit-account', __d('crm', 'Edit Account'), '"auto"'));

    $this->Lil->jsReady('$(".view-panel").mouseover(function(){' .
        '$(".edit-element, .delete-element", this).show();})' .
        '.mouseout(function(){$(".edit-element, .delete-element", this).hide();}' .
        ');');
    $this->Lil->jsReady('$(".edit-element").hide();');
    $this->Lil->jsReady('$(".delete-element").hide();');

    $this->Lil->jsReady(sprintf('$(".AddAddressLink").modalPopup({title:"%s"});', __d('crm', 'Add new Address')));
    $this->Lil->jsReady(sprintf('$(".AddAccountLink").modalPopup({title:"%s"});', __d('crm', 'Add new Account')));
    $this->Lil->jsReady(sprintf('$(".AddEmailLink").modalPopup({title:"%s"});', __d('crm', 'Add new Email')));
    $this->Lil->jsReady(sprintf('$(".AddPhoneLink").modalPopup({title:"%s"});', __d('crm', 'Add new Phone')));
