<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$editAddressForm = [
    'title_for_layout' => $address->id ? __d('crm', 'Edit Address') : __d('crm', 'Add Address'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$address, ['idPrefix' => 'contact-address']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'owner_id' => [
                'method' => 'hidden',
                'parameters' => [
                    'field' => 'owner_id',
                    'options' => [
                        'default' => '1',
                    ],
                ],
            ],
            'address_id' => [
                'method' => 'hidden',
                'parameters' => [
                    'field' => 'contacts_address_id',
                    ['id' => 'contacts-address_id'],
                ],
            ],
            'address_id_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['contacts_address_id'],
            ],
            'adrema_id' => [
                'method' => 'hidden',
                'parameters' => [
                    'field' => 'adrema_id',
                ],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'label' => __d('crm', 'Title') . ':',
                        'error' => __d('crm', 'Title is required.'),
                        'id' => 'contacts-title',
                        'autocomplete' => 'off',
                    ],
                ],
            ],
            'link_image' => $this->Html->image('/crm/img/ico_contact_check.gif', [
                'style' => $address->contacts_address_id ? '' : 'display: none;',
                'id' => 'ImageContactCheck',
            ]),
            'street' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'street',
                    'options' => [
                        'label' => __d('crm', 'Street') . ':',
                        'disabled' => $address->contacts_address_id ? 'disabled' : '',
                    ],
                ],
            ],
            '<div class="input-field text" id="contact-address-zip_city">',
            '<label for="#contact-address-zip" class="active">' . __d('crm', 'ZIP and City') . ':</label>',
            'address_zip' => [
                'method' => 'text',
                'parameters' => [
                    'field' => 'zip',
                    'options' => [
                        'div' => false,
                        'disabled' => $address->contacts_address_id ? 'disabled' : '',
                        'id' => 'contact-address-zip',
                    ],
                ],
            ],
            ' ',
            'address_city' => [
                'method' => 'text',
                'parameters' => [
                    'field' => 'city',
                    'options' => [
                        'div' => false,
                        'disabled' => $address->contacts_address_id ? 'disabled' : '',
                        'id' => 'contact-address-city',
                    ],
                ],
            ],
            '</div>',
            'country' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'country',
                    'options' => [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('crm', 'Country') . ':',
                            'class' => 'active',
                        ],
                        'disabled' => $address->contacts_address_id ? 'disabled' : '',
                        'default' => Configure::read('Crm.defaultCountry'),
                        'options' => Configure::read('Crm.countries'),
                        'empty' => true,
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'country_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['country'],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('crm', 'Add'),
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

echo $this->Lil->form($editAddressForm, 'Crm.Labels.edit_address');

?>
<script type="text/javascript">
    $(document).ready(function() {
        var elem = document.querySelector("#contacts-title");

        if (elem) {
            var instance = M.AutocompleteAjax.init(elem, {
                source: '<?php echo Router::url(['controller' => 'ContactsAddresses', 'action' => 'autocomplete']); ?>',
                onSearch: function () {
                    $('#contacts-address_id').val('');
                    $('#ImageContactCheck').hide();

                    $('#contact-address-street').attr('readonly', false);
                    $('#contact-address-zip').attr('readonly', false);
                    $('#contact-address-city').attr('readonly', false);
                    $('#contact-address-country').attr('readonly', false);
                },
                onSelect: function (item) {
                    if (item) {
                        $('#ImageContactCheck').show();
                        $("#contacts-title").val(item.title);
                        $("#contacts-address_id").val(item.id);

                        $('#contact-address-street').val(item.street).attr('readonly', true);
                        $('#contact-address-zip').val(item.zip).attr('readonly', true);
                        $('#contact-address-city').val(item.city).attr('readonly', true);
                        $('#contact-address-country').val(item.country).attr('readonly', true);

                        M.updateTextFields();
                    }
                }
            });

            $(elem)
                .on("keyup", function () {
                    if ($(this).val() === "") {
                        $('#contacts-address_id').val('');
                        $('#ImageContactCheck').hide();

                        $('#contact-address-street').attr('readonly', false);
                        $('#contact-address-zip').attr('readonly', false);
                        $('#contact-address-city').attr('readonly', false);
                        $('#contact-address-country').attr('readonly', false);
                    }
                });
        }

    });
</script>
