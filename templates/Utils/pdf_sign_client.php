<?php
$signForm = [
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
        'splice' => [
            'title' => __('PDF Splice'),
            'active' => $this->getRequest()->getParam('action') == 'pdfSplice',
            'visible' => true,
            'url' => [
                'action' => 'pdfSplice',
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

echo $this->Lil->form($signForm, 'App.Utils.PdfSignClient');
