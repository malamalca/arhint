<?php
use Cake\Core\Configure;

$attachedHeader = false;
if (!empty($counter->header) && substr($counter->header, 0, 2) == '{"') {
    $attachedHeader = json_decode($counter->header, true);
}
$attachedFooter = false;
if (!empty($counter->footer) && substr($counter->footer, 0, 2) == '{"') {
    $attachedFooter = json_decode($counter->footer, true);
}

$counter_edit = [
    'title_for_layout' =>
        $counter->id ? __d('lil_invoices', 'Edit Counter') : __d('lil_invoices', 'Add Counter'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form" id="edit-counter">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $counter, ['type' => 'file', 'idPrefix' => 'counter']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'fs_basics_start' => '<fieldset>',
            'lg_basics' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Basics')),

            'kind' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'kind',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('lil_invoices', 'Kind') . ':',
                            'class' => 'active',
                        ],
                        'options' => [
                            'issued' => __d('lil_invoices', 'Issued'),
                            'received' => __d('lil_invoices', 'Received'),
                        ],
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'docType' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'doc_type',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('lil_invoices', 'Type') . ':',
                            'class' => 'active',
                        ],
                        'options' => Configure::read('LilInvoices.documentTypes'),
                        'empty' => '-- ' . __d('lil_invoices', 'unspecified') . ' --',
                        'class' => 'browser-default',
                    ],
                ],
            ],

            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'label' => __d('lil_invoices', 'Title') . ':',
                    ],
                ],
            ],
            'counter' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'counter',
                    'options' => [
                        'label' => __d('lil_invoices', 'Counter Status') . ':',
                    ],
                ],
            ],
            'active' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'active',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('lil_invoices', 'This counter is active'),
                    ],
                ],
            ],
            'fs_basics_end' => '</fieldset>',

            'fs_templates_start' => '<fieldset>',
            'lg_templates' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Templates')),
            'mask' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'mask',
                    'options' => [
                        'label' => __d('lil_invoices', 'Invoice no. Template') . ':',
                        'default' => '[[no]]',
                    ],
                ],
            ],
            'mask_hint' => sprintf('<div class="helper-text">%s</div>', __d('lil_invoices', 'Use [[no]], [[no.2]], [[year]] templates.')),
            'template_descript' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'template_descript',
                    'options' => [
                        'type' => 'textarea',
                        'label' => __d('lil_invoices', 'Invoice description Template') . ':',
                    ],
                ],
            ],
            'fs_templates_end' => '</fieldset>',

            'fs_layout_start' => '<fieldset>',
            'lg_layout' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Default Layouts')),
            'tpl_header_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'tpl_header_id',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('lil_invoices', 'Header') . ':',
                            'class' => 'active',
                        ],
                        'options' => $templates['header'] ?? [],
                        'empty' => '-- ' . __d('lil_invoices', 'none') . ' --',
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'tpl_body_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'tpl_body_id',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('lil_invoices', 'Body') . ':',
                            'class' => 'active',
                        ],
                        'options' => $templates['body'] ?? [],
                        'empty' => '-- ' . __d('lil_invoices', 'default') . ' --',
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'tpl_footer_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'tpl_footer_id',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('lil_invoices', 'Footer') . ':',
                            'class' => 'active',
                        ],
                        'options' => $templates['footer'] ?? [],
                        'empty' => '-- ' . __d('lil_invoices', 'none') . ' --',
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'fs_layout_end' => '</fieldset>',

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

echo $this->Lil->form($counter_edit, 'LilInvoices.InvoicesCounters.edit');
