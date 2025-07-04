<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$form = [
    'title_for_layout' => __('Add Attachment for "{0}"', h($attachment->model)),
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$attachment, ['type' => 'file']]
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id']
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['redirect', [
                    'default' => $this->getRequest()->getQuery('redirect', ''),
                ]]
            ],

            'name' => [
                'method' => 'control',
                'parameters' => ['filename', [
                    'type' => 'file',
                    'label' => __('Filename') . ':',
                ]]
            ],

            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __('Upload'), ['type' => 'submit'],
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => []
            ],
        ]
    ]
];

echo $this->Lil->form($form, 'Attachments.edit');
