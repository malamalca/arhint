<?php
use Cake\Core\Configure;

$adremaEdit = [
    'title_for_layout' => __d('crm', 'Edit User Fields'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$adrema, ['type' => 'file']],
            ],
            'id' => [
                'method' => 'control',
                'parameters' => [
                    'id',
                    'options' => ['type' => 'hidden'],
                ],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('crm', 'Save'),
                    ['type' => 'submit'],
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

$additionalFields = Configure::read(implode('.', ['Crm', $adrema->kind, $adrema->kind_type, 'form']));
if ($additionalFields) {
    foreach ($additionalFields as $fieldName => $fieldConfig) {
        if (isset($adrema->user_data[$fieldName])) {
            $additionalFields[$fieldName]['parameters']['options']['default'] = $adrema->user_data[$fieldName];
        }
    }

    if (!empty($additionalFields)) {
        $this->Lil->insertIntoArray($adremaEdit['form']['lines'], $additionalFields, ['after' => 'id']);
    }
}

echo $this->Lil->form($adremaEdit, 'Crm.Adremas.adremaFields');
