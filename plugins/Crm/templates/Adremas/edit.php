<?php
use Cake\Core\Configure;

$adremaEdit = [
    'title_for_layout' => $adrema->id ? __d('crm', 'Edit Adrema') : __d('crm', 'Add Adrema'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$adrema],
            ],
            'id' => [
                'method' => 'control',
                'parameters' => [
                    'id',
                    'options' => ['type' => 'hidden'],
                ],
            ],
            'owner_id' => [
                'method' => 'control',
                'parameters' => [
                    'owner_id',
                    'options' => ['type' => 'hidden'],
                ],
            ],
            'referer' => [
                'method' => 'control',
                'parameters' => [
                    'referer',
                    'options' => ['type' => 'hidden'],
                ],
            ],
            'kind' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'kind',
                    'options' => [
                        'type' => 'select',
                        'options' => [
                            'labels' => __d('crm', 'Labels'),
                            'email' => __d('crm', 'Emails'),
                        ],
                        'label' => __d('crm', 'Kind') . ':',
                        'error' => [
                            'empty' => __d('crm', 'Adrema kind is required.'),
                        ],
                        'id' => 'kind-select',
                    ],
                ],
            ],
            'kind_label' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'kind_type_label',
                    'options' => [
                        'type' => 'select',
                        'label' => __d('crm', 'Label Type') . ':',
                        'options' => Configure::read('Crm.labelTemplates'),
                        'id' => 'label-template-select',
                    ],
                ],
            ],
            'kind_email' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'kind_type',
                    'options' => [
                        'type' => 'select',
                        'label' => __d('crm', 'Email Template') . ':',
                        'options' => Configure::read('Crm.emailTemplates'),
                        'id' => 'email-template-select',
                    ],
                ],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'label' => __d('crm', 'Title') . ':',
                        'error' => [
                            'empty' => __d('crm', 'Adrema title is required.'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('crm', 'Save'),
                    ['type' => 'submit'],
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($adremaEdit, 'Crm.Adremas.edit');
?>
<script type="text/javascript">
    $(document).ready(function() {
        // Toggle visibility of template fields based on selected kind
        function toggleTemplateFields() {
            var labelsTemplateSelect = $("#label-template-select");
            var emailTemplateSelect = $("#email-template-select");
            
            if ($("#kind-select").val() === 'labels') {
                $(labelsTemplateSelect).closest(".input-field.select").show();
                $(emailTemplateSelect).closest(".input-field.select").hide();
            } else {
                $(labelsTemplateSelect).closest(".input-field.select").hide();
                $(emailTemplateSelect).closest(".input-field.select").show();
            }
        }

        $("#kind-select").on("change", toggleTemplateFields);
        toggleTemplateFields(); // Initial call to set the correct visibility
    });
</script>