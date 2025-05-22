<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$noteForm = [
    'title_for_layout' => $note->isNew() ? __('Add Note') : __('Edit Note'),
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$note]
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id']
            ],
            'user_id' => [
                'method' => 'hidden',
                'parameters' => ['user_id']
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', [
                    'default' => $this->getRequest()->referer()
                ]]
            ],

            'note' => [
                'method' => 'control',
                'parameters' => ['note', [
                    'type' => 'textarea',
                    'label' => __('Note') . ':',
                ]]
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __('Save')
                ]
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => []
            ],
        ]
    ]
];

echo $this->Lil->form($noteForm, 'DashboardNotes.edit');
