<?php
if (!$certificate->isNew() && !empty($certificate->p12)) {
    $validTo = $certificate->valid_to;
    $expired = $validTo !== null && $validTo->isPast();
    $status = sprintf(
        '<p class="%s"><strong>%s</strong>%s%s</p>',
        $expired ? 'error' : 'success',
        h($expired ? __d('documents', 'Certificate is uploaded but expired.') : __d('documents', 'Certificate is uploaded.')),
        $validTo ? ' ' . h(__d('documents', 'Valid to') . ': ' . $validTo->format('d.m.Y')) : '',
        $certificate->tax_no ? ' ' . h(__d('documents', 'Operator Tax Number') . ': ' . $certificate->tax_no) : '',
    );
} else {
    $status = sprintf('<p class="error"><strong>%s</strong></p>', h(__d('documents', 'Certificate is not uploaded.')));
}

if (!empty($checkResult)) {
    $status .= sprintf(
        '<p class="%s"><strong>%s</strong></p><textarea readonly rows="6" style="width:100%%;font-family:monospace">%s</textarea>',
        $checkResult['ok'] ? 'success' : 'error',
        h($checkResult['ok'] ? __d('documents', 'Check passed.') : __d('documents', 'Check failed.')),
        h($checkResult['text']),
    );
}

$certificateEdit = [
    'title_for_layout' => __d('documents', 'Tax Certificate'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form" id="edit-tax-certificate">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $certificate, ['type' => 'file', 'url' => ['action' => 'certificate']]],
            ],
            'status' => $status,
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

// outside of the upload form, since postLink creates its own form

echo $this->Form->postLink(
    '<i class="material-icons left">wifi_tethering</i>' . h(__d('documents', 'Ping FURS')),
    ['action' => 'pingFurs'],
    [
        'class' => 'btn filled',
        'escape' => false,
        'title' => __d('documents', 'Check network connection to FURS servers (no certificate needed)'),
    ],
);
if (!$certificate->isNew() && !empty($certificate->p12)) {
    echo $this->Form->postLink(
        '<i class="material-icons left">network_check</i>' . h(__d('documents', 'Check certificate')),
        ['action' => 'testCertificate'],
        [
            'class' => 'btn filled',
            'escape' => false,
            'title' => __d('documents', 'Send echo request to the tax authority to verify the certificate'),
        ],
    );
}
