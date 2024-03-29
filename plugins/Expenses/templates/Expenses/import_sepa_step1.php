<?php
$selectFileForm = [
    'title_for_layout' => __d('expenses', 'IMPORT: SepaXML'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [null, ['type' => 'file']],
            ],
            'fs_file_start' => '<fieldset>',
            'sepafile' => [
                'method' => 'file',
                'parameters' => ['sepafile', [
                    'type' => 'file',
                    'label' => __d('expenses', 'Upload a XML') . ':',
                    'accept' => '.xml,.zip',
                ]],
            ],

            'fs_file_end' => '</fieldset>',

            'submit' => [
                'method' => 'submit',
                'parameters' => ['label' => __d('expenses', 'Import')],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($selectFileForm, 'Expenses.Expenses.import_sepa');
