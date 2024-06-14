<?php

$templateEdit = [
    'title_for_layout' => __d(
        'documents',
        'Edit Templates for Document #{0}',
        $invoice->counter
    ),
    'form' => [
        'defaultHelper' => $this->Form,

        'pre' => '<div class="form">',
        'post' => '</div>',

        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [
                    $invoice, [
                        'type' => 'file',
                        'id' => 'invoice-edit-form',
                        'idPrefix' => 'invoice',
                        'url' => [
                            'action' => 'edit',
                            $invoice->id,
                        ],
                    ],
                ],
            ],
            'referer' => [
                'method' => 'control',
                'parameters' => ['referer', ['type' => 'hidden']],
            ],
            'id' => [
                'method' => 'control',
                'parameters' => ['id', ['type' => 'hidden']],
            ],

            'tpl_header_id_label' => [
                'method' => 'label',
                'parameters' => ['tpl_header_id', __d('documents', 'Page Header') . ':'],
            ],
            'tpl_header_id' => [
                'method' => 'control',
                'parameters' => ['tpl_header_id', [
                    'type' => 'select',
                    'label' => false,
                    'empty' => '-- ' . __d('documents', 'none') . ' --',
                    'options' => empty($templates['header']) ? [] : $templates['header'],
                    'class' => 'browser-default',
                ]],
            ],

            'tpl_body_id_label' => [
                'method' => 'label',
                'parameters' => ['tpl_body_id', __d('documents', 'Page Body') . ':'],
            ],
            'tpl_body_id' => [
                'method' => 'control',
                'parameters' => ['tpl_body_id', [
                    'type' => 'select',
                    'label' => false,
                    'empty' => '-- ' . __d('documents', 'default') . ' --',
                    'options' => empty($templates['body']) ? [] : $templates['body'],
                    'class' => 'browser-default',
                ]],
            ],

            'tpl_footer_id_label' => [
                'method' => 'label',
                'parameters' => ['tpl_footer_id', __d('documents', 'Page Footer') . ':'],
            ],
            'tpl_footer_id' => [
                'method' => 'control',
                'parameters' => ['tpl_footer_id', [
                    'type' => 'select',
                    'label' => false,
                    'empty' => '-- ' . __d('documents', 'none') . ' --',
                    'options' => empty($templates['footer']) ? [] : $templates['footer'],
                    'class' => 'browser-default',
                ]],
            ],

            ////////////////////////////////////////////////////////////////////////////////////
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

echo $this->Lil->form($templateEdit, 'Documents.Invoices.templates');
