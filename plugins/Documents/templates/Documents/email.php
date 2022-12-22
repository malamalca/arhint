<?php

use Cake\Routing\Router;

$send_document = [
    'title_for_layout' => __d('documents', 'Email Document'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$email],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['id' => 'referer', 'default' => Router::url($this->getRequest()->referer(), true)]],
            ],
            'to' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'to',
                    'options' => [
                        'type' => 'text',
                        'label' => __d('documents', 'To') . ':',
                        'default' => $this->getRequest()->getQuery('to'),
                        'autocomplete' => 'off',
                    ],
                ],
            ],
            'cc' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'cc',
                    'options' => [
                        'type' => 'text',
                        'label' => __d('documents', 'CC') . ':',
                        'default' => $this->getRequest()->getQuery('cc'),
                    ],
                ],
            ],
            'cc_me' => !$this->getCurrentUser()->get('email') ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'cc_me',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('documents', 'Send CC to me ({0})', $this->getCurrentUser()->get('email')),
                    ],
                ],
            ],
            'subject' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'subject',
                    'options' => [
                        'type' => 'text',
                        'label' => __d('documents', 'Subject') . ':',
                        'default' => $this->getRequest()->getQuery('subject'),
                    ],
                ],
            ],
            'body' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'body',
                    'options' => [
                        'type' => 'textarea',
                        'label' => __d('documents', 'Body') . ':',
                        'default' => $this->getRequest()->getQuery('body'),
                    ],
                ],
            ],
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('documents', 'Send'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

// add documents to be send
$documents_display = [];
$documents_display[] = '<div>';
$documents_display[] = sprintf('<label>%1$s:</label>', __d('documents', 'Attachments'));

foreach ($attachments as $document_id => $document_title) {
    $documents_display[] = sprintf(
        '<div class="email-attachment" id="email-attachment-%3$s">%2$s %1$s</div>',
        $document_title,
        $this->Html->image('/documents/img/attachment.png'),
        $document_id
    );
    $documents_display[] = [
        'method' => 'hidden',
        'parameters' => ['documents', ['value' => $document_id, 'id' => 'attachment-' . $document_id]],
    ];
}
$documents_display[] = '</div>';

$this->Lil->insertIntoArray($send_document['form']['lines'], $documents_display, ['after' => 'subject']);

echo $this->Lil->form($send_document, 'Documents.Documents.email');
