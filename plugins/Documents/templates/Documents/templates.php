<?php

$document_edit = [
    'title_for_layout' => __d(
        'documents',
        'Edit Templates for Document #{0}',
        $document->counter
    ),
    'form' => [
        'defaultHelper' => $this->Form,

        'pre' => '<div class="form">',
        'post' => '</div>',

        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [
                    $document, [
                        'type' => 'file',
                        'id' => 'document-edit-form',
                        'idPrefix' => 'document',
                        'url' => [
                            'action' => 'edit',
                            $document->id,
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
                        'text' => __d('documents', 'Page Header') . ':',
                    ],
                    'empty' => '-- ' . __d('documents', 'none') . ' --',
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
                        'text' => __d('documents', 'Page Body') . ':',
                    ],
                    'empty' => '-- ' . __d('documents', 'default') . ' --',
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
                        'text' => __d('documents', 'Page Footer') . ':',
                    ],
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

echo $this->Lil->form($document_edit, 'Documents.Documents.templates');
