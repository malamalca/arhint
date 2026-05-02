<?php
/**
 * @var \App\View\AppView $this
 */

$bsImport = [
    'title_for_layout' => __d('expenses', 'Import Bank Statement'),
    'menu' => [],
    'form' => [
        'defaultHelper' => $this->Form,
        'pre'  => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method'     => 'create',
                'parameters' => [null, ['type' => 'file', 'url' => ['action' => 'import']]],
            ],

            'fs_start' => '<fieldset>',
            'lg'       => sprintf('<legend>%s</legend>', __d('expenses', 'Import ISO 20022 camt.053 XML')),

            'xml_file' => [
                'method'     => 'control',
                'parameters' => ['xml_file', [
                    'type'   => 'file',
                    'label'  => __d('expenses', 'XML File') . ':',
                    'accept' => '.xml,application/xml,text/xml',
                ]],
            ],

            'fs_end' => '</fieldset>',

            'submit' => [
                'method'     => 'submit',
                'parameters' => ['label' => __d('expenses', 'Import')],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];

echo $this->Lil->form($bsImport, 'Expenses.BankStatements.import');
