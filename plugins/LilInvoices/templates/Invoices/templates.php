<?php

$invoice_edit = [
    'title_for_layout' => __d(
        'lil_invoices',
        'Edit Templates for Invoice #{0}',
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
            'tpl_header_id' => [
                'method' => 'control',
                'parameters' => ['tpl_header_id', [
                    'type' => 'select',
                    'label' => [
                        'class' => 'active',
                        'text' => __d('lil_invoices', 'Page Header') . ':',
                    ],
                    'empty' => '-- ' . __d('lil_invoices', 'none') . ' --',
                    'options' => empty($templates['header']) ? [] : $templates['header'],
                    'class' => 'browser-default',
                ]],
            ],

            'tpl_body_id' => [
                'method' => 'control',
                'parameters' => ['tpl_body_id', [
                    'type' => 'select',
                    'label' => [
                        'class' => 'active',
                        'text' => __d('lil_invoices', 'Page Body') . ':',
                    ],
                    'empty' => '-- ' . __d('lil_invoices', 'default') . ' --',
                    'options' => empty($templates['body']) ? [] : $templates['body'],
                    'class' => 'browser-default',
                ]],
            ],
            'tpl_footer_id' => [
                'method' => 'control',
                'parameters' => ['tpl_footer_id', [
                    'type' => 'select',
                    'label' => [
                        'class' => 'active',
                        'text' => __d('lil_invoices', 'Page Footer') . ':',
                    ],
                    'empty' => '-- ' . __d('lil_invoices', 'none') . ' --',
                    'options' => empty($templates['footer']) ? [] : $templates['footer'],
                    'class' => 'browser-default',
                ]],
            ],

            ////////////////////////////////////////////////////////////////////////////////////
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_invoices', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($invoice_edit, 'LilInvoices.Invoices.templates');
