<?php
use Cake\Routing\Router;

$uploadForm = [
    'title' => 'PDF Sign',
    'menu' => [
        'sign' => [
            'title' => __('PDF Sign'),
            'active' => $this->getRequest()->getParam('action') == 'pdfSign',
            'visible' => true,
            'url' => [
                'action' => 'pdfSign',
            ],
        ],
        'merge' => [
            'title' => __('PDF Merge'),
            'active' => $this->getRequest()->getParam('action') == 'pdfMerge',
            'visible' => true,
            'url' => [
                'action' => 'pdfMerge',
            ],
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [null, ['type' => 'file']],
            ],
            'file' => [
                'method' => 'control',
                'parameters' => ['file', [
                    'type' => 'file',
                    'accept' => 'application/pdf',
                    'label' => [
                        'text' => __('PDF') . ':',
                        'class' => 'active',
                    ],
                ]],
            ],
            'cert' => [
                'method' => 'control',
                'parameters' => ['cert', [
                    'type' => 'file',
                    'label' => [
                        'text' => __('Certificate') . ':',
                        'class' => 'active',
                    ],
                ]],
            ],
            'pass' => [
                'method' => 'control',
                'parameters' => ['pass', [
                    'type' => 'text',
                    'label' => [
                        'text' => __('Password') . ':',
                        'class' => 'active',
                    ],
                ]],
            ],
            'signature' => [
                'method' => 'control',
                'parameters' => ['signature', [
                    'type' => 'file',
                    'label' => [
                        'text' => __('Signature') . ':',
                        'class' => 'active',
                    ],
                ]],
            ],
            'page' => [
                'method' => 'control',
                'parameters' => ['page', [
                    'type' => 'text',
                    'label' => [
                        'text' => __('Page') . ':',
                        'class' => 'active',
                        'default' => 0,
                    ],
                ]],
            ],
            'x' => [
                'method' => 'control',
                'parameters' => ['x', [
                    'type' => 'text',
                    'label' => [
                        'text' => __('X') . ' [%]:',
                        'class' => 'active',
                    ],
                ]],
            ],
            'y' => [
                'method' => 'control',
                'parameters' => ['y', [
                    'type' => 'text',
                    'label' => [
                        'text' => __('Y') . ' [%]:',
                        'class' => 'active',
                    ],
                ]],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [__('Submit'), [
                    'type' => 'submit',
                    'id' => 'submit',
                ]],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($uploadForm, 'App.Utils.PdfSign');