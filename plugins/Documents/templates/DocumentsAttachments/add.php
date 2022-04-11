<?php

use Cake\Routing\Router;

$attachment_add = [
    'title_for_layout' => __d('documents', 'Add Attachment'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$attachment, ['type' => 'file']],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['default' => Router::url($this->getRequest()->referer(), true)]],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'document_id' => [
                'method' => 'hidden',
                'parameters' => ['document_id', ['default' => $attachment->id]],
            ],
            'filename' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'filename',
                    'options' => [
                        'type' => 'file',
                        'multiple' => 'multiple',
                        'label' => false,
                        'error' => [
                            'empty' => __d('documents', 'A file must be selected for upload.'),
                        ],
                        'class' => 'browser-default',
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

echo $this->Lil->form($attachment_add, 'Documents.DocumentsAttachments.edit');
