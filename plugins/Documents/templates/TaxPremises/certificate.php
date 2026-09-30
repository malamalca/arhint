<?php
$certificateEdit = [
    'title_for_layout' => __d('documents', 'Tax Certificate'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form" id="edit-tax-certificate">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $certificate, ['type' => 'file']],
            ],
            'info' => sprintf(
                '<p class="light">%s</p>',
                h(__d(
                    'documents',
                    'Certificate (p12) issued by FURS is used to sign tax confirmation requests. ' .
                    'The certificate and its password are stored on the server.',
                )),
            ),
            'p12_file' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'p12_file',
                    'options' => [
                        'type' => 'file',
                        'accept' => '.p12,.pfx',
                        'label' => [
                            'text' => __d('documents', 'Certificate (p12)') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
            ],
            'password' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'password',
                    'options' => [
                        'type' => 'password',
                        'label' => __d('documents', 'Private Key Password') . ':',
                        'autocomplete' => 'new-password',
                        'value' => '',
                    ],
                ],
            ],
            'tax_no' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'tax_no',
                    'options' => [
                        'type' => 'text',
                        'label' => __d('documents', 'Operator Tax Number') . ':',
                        'maxlength' => 8,
                    ],
                ],
            ],
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('documents', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($certificateEdit, 'Documents.TaxPremises.certificate');
