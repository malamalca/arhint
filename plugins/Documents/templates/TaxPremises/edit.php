<?php
$control = fn(string $field, string $label, array $options = []): array => [
    'method' => 'control',
    'parameters' => [
        'field' => $field,
        'options' => $options + ['label' => $label . ':'],
    ],
];

$taxPremiseEdit = [
    'title_for_layout' =>
        $taxPremise->id ? __d('documents', 'Edit Business Premise') : __d('documents', 'Add Business Premise'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form" id="edit-tax-premise">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $taxPremise],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'fs_basics_start' => '<fieldset>',
            'lg_basics' => sprintf('<legend>%s</legend>', __d('documents', 'Basics')),
            'no' => $control('no', __d('documents', 'Premise Id'), ['maxlength' => 20]),
            'title' => $control('title', __d('documents', 'Title')),
            'kind' => $control('kind', __d('documents', 'Kind'), [
                'type' => 'select',
                'options' => [
                    'RL' => __d('documents', 'Real Estate'),
                    'MO' => __d('documents', 'Moveable Object'),
                ],
            ]),
            'validity_date' => $control('validity_date', __d('documents', 'Valid From'), ['type' => 'date']),
            'closed' => $control('closed', '', [
                'type' => 'checkbox',
                'label' => __d('documents', 'Business premise is closed'),
            ]),
            'fs_basics_end' => '</fieldset>',

            'fs_re_start' => '<fieldset id="tax-premise-re">',
            'lg_re' => sprintf('<legend>%s</legend>', __d('documents', 'Real Estate')),
            'casadral_number' => $control('casadral_number', __d('documents', 'Cadastral Number'), ['maxlength' => 4]),
            'building_number' => $control('building_number', __d('documents', 'Building Number'), ['maxlength' => 5]),
            'building_section_number' => $control(
                'building_section_number',
                __d('documents', 'Building Section Number'),
                ['maxlength' => 4],
            ),
            'street' => $control('street', __d('documents', 'Street')),
            'house_number' => $control('house_number', __d('documents', 'House Number'), ['maxlength' => 10]),
            'house_number_additional' => $control(
                'house_number_additional',
                __d('documents', 'House Number Additional'),
                ['maxlength' => 10],
            ),
            'community' => $control('community', __d('documents', 'Community')),
            'city' => $control('city', __d('documents', 'City')),
            'postal_code' => $control('postal_code', __d('documents', 'Postal Code'), ['maxlength' => 4]),
            'fs_re_end' => '</fieldset>',

            'fs_mo_start' => '<fieldset id="tax-premise-mo">',
            'lg_mo' => sprintf('<legend>%s</legend>', __d('documents', 'Moveable Object')),
            'mo_type' => $control('mo_type', __d('documents', 'Type'), [
                'type' => 'select',
                'options' => [
                    'A' => __d('documents', 'Moveable Object'),
                    'B' => __d('documents', 'Object at Permanent Location'),
                    'C' => __d('documents', 'Individual Electronic Device'),
                ],
                'empty' => '-- ' . __d('documents', 'select') . ' --',
            ]),
            'fs_mo_end' => '</fieldset>',

            'fs_sw_start' => '<fieldset>',
            'lg_sw' => sprintf('<legend>%s</legend>', __d('documents', 'Software Supplier')),
            'sw_taxno' => $control('sw_taxno', __d('documents', 'Tax Number'), ['maxlength' => 8]),
            'sw_title' => $control('sw_title', __d('documents', 'Title')),
            'notes' => $control('notes', __d('documents', 'Notes'), ['type' => 'textarea']),
            'fs_sw_end' => '</fieldset>',

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

echo $this->Lil->form($taxPremiseEdit, 'Documents.TaxPremises.edit');
?>

<script type="text/javascript">
    $(document).ready(function() {
        function toggleKind() {
            var isRealEstate = $("#kind").val() == "RL";
            isRealEstate ? $("#tax-premise-re").show() : $("#tax-premise-re").hide();
            isRealEstate ? $("#tax-premise-mo").hide() : $("#tax-premise-mo").show();
        }

        toggleKind();
        $("#kind").change(toggleKind);
    });
</script>
