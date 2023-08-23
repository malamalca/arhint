<?php
use Cake\Routing\Router;

$uploadForm = [
    'title' => 'PDF Splice',
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
            'firstPage' => [
                'method' => 'control',
                'parameters' => ['firstPage', [
                    'type' => 'number',
                    'label' => [
                        'text' => __('First Page') . ':',
                        'class' => 'active',
                        'default' => 0,
                    ],
                ]],
            ],
            'lastPage' => [
                'method' => 'control',
                'parameters' => ['lastPage', [
                    'type' => 'number',
                    'label' => [
                        'text' => __('Last Page') . ':',
                        'class' => 'active',
                        'default' => 0,
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