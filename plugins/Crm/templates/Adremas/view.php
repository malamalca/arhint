<?php

use Cake\Routing\Router;

$viewAdremaPanels = [
    'title_for_layout' => h($adrema->title),
    'menu' => [
        'edit' => [
            'title' => __d('crm', 'Edit'),
            'visible' => true,
            'url' => [
                'action' => 'edit',
                $adrema->id,
            ],
        ],
        'delete' => [
            'title' => __d('crm', 'Delete'),
            'visible' => true,
            'url' => [
                'action' => 'delete',
                $adrema->id,
            ],
            'params' => [
                'confirm' => __d('crm', 'Are you sure you want to delete the adrema?'),
            ],
        ],
    ],
    'panels' => [
        'title_addresses' => ['lines' => [sprintf('<h3>%s</h3>', __d('crm', 'Addresses'))]],
        'addresses' => [
            'table' => [
                'parameters' => ['id' => 'AddressesList'],
                'head' => ['rows' => [0 => ['columns' => [
                    'checked' => '<input type="checkbox" />',
                    'title' => __d('crm', 'Title'),
                    'address' => __d('crm', 'Address'),
                    'email' => __d('crm', 'Email'),
                    'actions' => '&nbsp;',
                ]]]],
            ]
        ],
        'title_attachments' => ['lines' => [sprintf('<h3>%s</h3>', __d('crm', 'Attachments'))]],
        'attachments' => '',
    ],
];

foreach ($addresses as $k => $address) {
    $viewAdremaPanels['panels']['addresses']['table']['body']['rows'][] = ['columns' => [
        'checked' => '&nbsp;',
        'title' => h($address->contact->title ?? 'N/A'),
        'address' => h($address->contacts_address ?? ''),
        'email' => h($address->contacts_email->email ?? ''),
        'actions' => 
            $this->Lil->editLink(['controller' => 'AdremasContacts', 'action' => 'edit', $address->id]) . ' ' .
            $this->Lil->deleteLink(['controller' => 'AdremasContacts', 'action' => 'delete', $address->id]),
    ]];
}

$viewAdremaPanels['panels']['attachments'] = $this->Arhint->attachmentsTable(
    $attachments, 'Adrema', $adrema->id, ['redirectUrl' => Router::url(null, true)]
);

////////////////////////////////////////////////////////////////////////////////////////////////
echo $this->Lil->panels($viewAdremaPanels, 'Crm.Adremas.view');