<?php
$attachment_add = [
    'title_for_layout' => __d('lil_invoices', 'Add Attachment'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'class' => $this->Form,
                'method' => 'create',
                'parameters' => [$attachment, ['type' => 'file']],
            ],
            'referer' => [
                'class' => $this->Form,
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'id' => [
                'class' => $this->Form,
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'invoice_id' => [
                'class' => $this->Form,
                'method' => 'hidden',
                'parameters' => ['field' => 'invoice_id', ['default' => $attachment->id]],
            ],
            'filename' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'filename',
                    'options' => [
                        'type' => 'file',
                        'multiple' => 'multiple',
                        'label' => false,
                        'error' => [
                            'empty' => __d('lil_invoices', 'A file must be selected for upload.'),
                        ],
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'submit' => [
                'class' => $this->Form,
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_invoices', 'Save'),
                ],
            ],
            'form_end' => [
                'class' => $this->Form,
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($attachment_add, 'LilInvoices.InvoicesAttachments.edit');
