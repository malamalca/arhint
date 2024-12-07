<?php

use Cake\Core\Configure;
use Cake\Routing\Router;

$clientEdit = [
    'title_for_layout' => __d('documents', 'Edit Client'),
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $client],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'document_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'document_id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['default' => Router::url($this->getRequest()->referer(), true)]],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'label' => __d('documents', 'Title') . ':',
                        'error' => __d('documents', 'Title is required.'),
                    ],
                ],
            ],
            'tax_no' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'tax_no',
                    'options' => [
                        'label' => __d('documents', 'Tax no.') . ':',
                        'error' => __d('documents', 'Tax no is required.'),
                    ],
                ],
            ],
            'mat_no' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'mat_no',
                    'options' => [
                        'label' => __d('documents', 'Mat no.') . ':',
                        'error' => __d('documents', 'Mat no is required.'),
                    ],
                ],
            ],

            'fs_address_start' => sprintf(
                '<fieldset class="col l6 m6 s12"><legend>%s</legend>',
                __d('documents', 'Address')
            ),
            'street' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'street',
                    'options' => [
                        'label' => __d('documents', 'Street') . ':',
                        'error' => __d('documents', 'Street is required.'),
                    ],
                ],
            ],
            'address_zipcity_wrapper' => '<div class="input-field" id="client-address-zip_city">',
            'address_zip' => [
                'method' => 'text',
                'parameters' => [
                    'field' => 'zip',
                    'options' => ['id' => 'client-address-zip', 'style' => 'width: 100px;'],
                ],
            ],
            'address_city' => [
                'method' => 'text',
                'parameters' => [
                    'field' => 'city',
                    'options' => ['id' => 'client-address-city'],
                ],
            ],
            'address_zipcity_label' => '<label for="client-address-zip">' . __d('documents', 'ZIP and City') . ':</label>',
            'address_zipcity_end' => '</div>',
            'address_country' => [
                'method' => 'hidden',
                'parameters' => [
                    'field' => 'country',
                    'options' => ['id' => 'client-address-country'],
                ],
            ],
            'address_country-unlock' => [
                'method' => 'unlockField',
                'parameters' => [
                    'field' => 'country',
                ],
            ],
            'address_country_code' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'country_code',
                    'options' => [
                        'type' => 'select',
                        'options' => Configure::read('Crm.countries'),
                        'label' => __d('documents', 'Country') . ':',
                        'default' => Configure::read('Crm.defaultCountry'),
                        'empty' => true,
                    ],
                ],
            ],
            'fs_address_end' => '</fieldset>',

            'fs_account_start' => sprintf(
                '<fieldset class="col l6 m6 s12"><legend>%s</legend>',
                __d('documents', 'Account Data')
            ),
            'account_iban' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'iban',
                    'options' => ['label' => __d('documents', 'IBAN') . ':'],
                ],
            ],
            'account_bic' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'bic',
                    'options' => ['label' => __d('documents', 'BIC') . ':'],
                ],
            ],
            'account_bank' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'bank',
                    'options' => ['label' => __d('documents', 'Bank') . ':'],
                ],
            ],
            'fs_account_end' => '</fieldset>',

            'fs_contact-data_start' => sprintf(
                '<fieldset class="col l6 m6 s12"><legend>%s</legend>',
                __d('documents', 'Contact Data')
            ),
            'person' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'person',
                    'options' => ['label' => __d('documents', 'Contact Person') . ':'],
                ],
            ],
            'phone' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'phone',
                    'options' => ['label' => __d('documents', 'Phone') . ':'],
                ],
            ],
            'fax' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'fax',
                    'options' => ['label' => __d('documents', 'Fax') . ':'],
                ],
            ],
            'email' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'email',
                    'options' => ['label' => __d('documents', 'Email') . ':'],
                ],
            ],
            'fs_contact-data_end' => '</fieldset>',

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

echo $this->Lil->form($clientEdit, 'Documents.DocumentsClients.edit');
?>
<script type="text/javascript">
    $(document).ready(function() {
        $("#client-address-zip").autocomplete({
            autoFocus: true,
            source: "<?php echo Router::url([
                'plugin' => 'Crm',
                'controller' => 'ContactsAddresses',
                'action' => 'autocomplete-zip-city',
                'zip',
            ], true); ?>",
            select: function(event, ui) {
                if (ui.item) {
                    $("#client-address-zip").val(ui.item.value);
                    $("#client-address-city").val(ui.item.label);
                }
            }
        });

        $('#client-address-city').autocomplete({
            autoFocus: true,
            source: "<?php echo Router::url([
                'plugin' => 'Crm',
                'controller' => 'ContactsAddresses',
                'action' => 'autocomplete-zip-city',
                'city',
            ], true); ?>",
            select: function(event, ui) {
                if (ui.item) {
                    $("#client-address-zip").val(ui.item.id);
                    $("#client-address-city").val(ui.item.label);
                }
            }
        });

        $("select#country-code").on("change", function(e) {
            $("input#client-address-country").val($("select#country-code option:selected").text());
        });
    });
</script>
