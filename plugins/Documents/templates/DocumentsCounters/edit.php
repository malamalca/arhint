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
        $counter->id ? __d('documents', 'Edit Counter') : __d('documents', 'Add Counter'),
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
            'lg_basics' => sprintf('<legend>%s</legend>', __d('documents', 'Basics')),

            'kind' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'kind',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('documents', 'Kind') . ':',
                            'class' => 'active',
                        ],
                        'options' => [
                            'issued' => __d('documents', 'Issued'),
                            'received' => __d('documents', 'Received'),
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
                            'text' => __d('documents', 'Type') . ':',
                            'class' => 'active',
                        ],
                        'options' => Configure::read('Documents.documentTypes'),
                        'empty' => '-- ' . __d('documents', 'unspecified') . ' --',
                        'class' => 'browser-default',
                    ],
                ],
            ],

            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'label' => __d('documents', 'Title') . ':',
                    ],
                ],
            ],
            'counter' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'counter',
                    'options' => [
                        'label' => __d('documents', 'Counter Status') . ':',
                    ],
                ],
            ],
            'active' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'active',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('documents', 'This counter is active'),
                    ],
                ],
            ],
            'fs_basics_end' => '</fieldset>',

            'fs_templates_start' => '<fieldset>',
            'lg_templates' => sprintf('<legend>%s</legend>', __d('documents', 'Templates')),
            'mask' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'mask',
                    'options' => [
                        'label' => __d('documents', 'Document no. Template') . ':',
                        'default' => '[[no]]',
                    ],
                ],
            ],
            'mask_hint' => sprintf('<div class="helper-text">%s</div>', __d('documents', 'Use [[no]], [[no.2]], [[year]] templates.')),
            'template_descript' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'template_descript',
                    'options' => [
                        'type' => 'textarea',
                        'label' => __d('documents', 'Document description Template') . ':',
                    ],
                ],
            ],
            'fs_templates_end' => '</fieldset>',

            'fs_pmt_start' => '<fieldset id="payment-templates">',
            'lg_pmt' => sprintf('<legend>%s</legend>', __d('documents', 'Payment Templates')),
            'pmt_days' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'pmt_days',
                    'options' => [
                        'label' => __d('documents', 'Days To Pay') . ':',
                        'default' => '8',
                    ],
                ],
            ],
            'fs_pmt_end' => '</fieldset>',


            'fs_layout_start' => '<fieldset>',
            'lg_layout' => sprintf('<legend>%s</legend>', __d('documents', 'Default Layouts')),
            'tpl_header_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'tpl_header_id',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('documents', 'Header') . ':',
                            'class' => 'active',
                        ],
                        'options' => $templates['header'] ?? [],
                        'empty' => '-- ' . __d('documents', 'none') . ' --',
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
                            'text' => __d('documents', 'Body') . ':',
                            'class' => 'active',
                        ],
                        'options' => $templates['body'] ?? [],
                        'empty' => '-- ' . __d('documents', 'default') . ' --',
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
                            'text' => __d('documents', 'Footer') . ':',
                            'class' => 'active',
                        ],
                        'options' => $templates['footer'] ?? [],
                        'empty' => '-- ' . __d('documents', 'none') . ' --',
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'fs_layout_end' => '</fieldset>',

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

echo $this->Lil->form($counter_edit, 'Documents.DocumentsCounters.edit');
?>

<script type="text/javascript">
    var invoiceDocTypes = <?= json_encode(Configure::read('Documents.invoiceDocTypes')) ?>;
    $(document).ready(function() {
        invoiceDocTypes.includes($("#counter-doc-type").val()) ? $("#payment-templates").show() : $("#payment-templates").hide();

        $("#counter-doc-type").change(function(e) {
            invoiceDocTypes.includes($(this).val()) ? $("#payment-templates").show() : $("#payment-templates").hide();
        });
    });
</script>
