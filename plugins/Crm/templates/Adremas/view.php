<?php

use Cake\Collection\Collection;
use Cake\Core\Configure;
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
        'values' => [
            'title' => __d('crm', 'Additional'),
            'visible' => true,
            'url' => [
                'action' => 'adremaFields',
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
        'email' => $adrema->kind != 'email' ? null : [
            'title' => __d('crm', 'Email'),
            'visible' => true,
            'url' => [
                'controller' => 'Adremas',
                'action' => 'email',
                $adrema->id,
            ],
            'params' => [
                'confirm' => __d('crm', 'Are you sure you want to send this adrema via email?'),
            ],
        ],
        'labels' => $adrema->kind != 'labels' ? null : [
            'title' => __d('crm', 'Labels'),
            'visible' => true,
            'url' => [
                'controller' => 'Adremas',
                'action' => 'labels',
                $adrema->id,
            ],
            'params' => [
                'confirm' => __d('crm', 'Are you sure you want to print labels?'),
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
                ],
        ],
        'add_address' => ['lines' => [
            $this->Html->link(
                __d('crm', 'Add new address'),
                [
                    'controller' => 'AdremasContacts',
                    'action' => 'edit',
                    '?' => ['adrema' => $adrema->id],
                ],
                [
                    'class' => 'btn-small filled',
                ],
            ),
        ]],
        'title_attachments' => ['lines' => [sprintf('<h3>%s</h3>', __d('crm', 'Attachments'))]],
        'attachments' => '',
    ],
];

/** DISPLAY ADDITIONAL FIELDS */

$additionalFields = Configure::read(implode('.', ['Crm', $adrema->kind, $adrema->kind_type, 'form']));
if ($additionalFields && count($additionalFields) != 0) {
    $additionalFieldsPanel = ['lines' => []];
    foreach ($additionalFields as $fieldName => $fieldConfig) {
        $additionalFieldsPanel['lines'][$fieldName] = [
            'label' => h($fieldConfig['parameters']['options']['label'] ?? $fieldName),
            'html' => h($adrema->user_data[$fieldName] ?? ''),
        ];
        if ($fieldConfig['parameters']['options']['type'] === 'file' && !empty($adrema->user_data[$fieldName])) {
            $attachment = (new Collection($adrema->form_attachments))->firstMatch(['id' => $adrema->user_data[$fieldName]]);
            if ($attachment) {
                $additionalFieldsPanel['lines'][$fieldName]['html'] = $this->Html->link(
                    (string)$attachment->filename,
                    [
                        'prefix' => false,
                        'plugin' => false,
                        'controller' => 'Attachments',
                        'action' => 'preview',
                        $attachment->id,
                        '?' => ['redirect' => Router::url(null, true)],
                    ],
                    ['class' => 'AttacmhmentPreviewLink'],
                );
            }
        }
    }
    if (!empty($additionalFieldsPanel['lines'])) {
        $this->Lil->insertIntoArray(
            $viewAdremaPanels['panels'],
            [
                'title_additional_fields' => ['lines' => [sprintf('<h3>%s</h3>', __d('crm', 'Additional Fields'))]],
                'additional_fields' => $additionalFieldsPanel],
            ['before' => 'title_addresses'],
        );
    }
}

foreach ($addresses as $address) {
    $viewAdremaPanels['panels']['addresses']['table']['body']['rows'][] = ['columns' => [
        'checked' => '&nbsp;',
        'title' => h($address->contact->title ?? 'N/A'),
        'address' => h($address->contacts_address ?? ''),
        'email' => h($address->contacts_email->email ?? ''),
        'actions' => ['params' => ['class' => 'nowrap'], 'html' =>
            $this->Lil->editLink(['controller' => 'AdremasContacts', 'action' => 'edit', $address->id]) . ' ' .
            $this->Lil->deleteLink(['controller' => 'AdremasContacts', 'action' => 'delete', $address->id]),
        ],
    ]];
}

$viewAdremaPanels['panels']['attachments'] = $this->Arhint->attachmentsTable(
    $adrema->attachments ?? [],
    'Adrema',
    $adrema->id,
    ['redirectUrl' => Router::url(null, true)],
);

////////////////////////////////////////////////////////////////////////////////////////////////
echo $this->Lil->panels($viewAdremaPanels, 'Crm.Adremas.view');
