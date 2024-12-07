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
            'address_zip' => [
                'method' => 'text',
                'parameters' => [
                    'field' => 'zip',
                    'options' => [
                        'div' => false,
                        'disabled' => $address->contacts_address_id ? 'disabled' : '',
                        'id' => 'contact-address-zip',
                        'style' => 'width: 100px',
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
            '<label for="#contact-address-zip" class="active">' . __d('crm', 'ZIP and City') . ':</label>',
            '</div>',
            'country' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'country',
                    'options' => [
                        'type' => 'select',
                        'label' => __d('crm', 'Country') . ':',
                        'disabled' => $address->contacts_address_id ? 'disabled' : '',
                        'default' => Configure::read('Crm.defaultCountry'),
                        'options' => Configure::read('Crm.countries'),
                        'empty' => true,
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

echo $this->Lil->form($editAddressForm, 'Crm.Labels.edit_address');

?>
<script type="text/javascript">
    $(document).ready(function() {
        var elem = document.querySelector("#contacts-title");

        if (elem) {
            var instance = M.Autocomplete.init(elem, {
                onSearch: (text, autocomplete) => {
                    $.get("<?php echo Router::url(['controller' => 'ContactsAddresses', 'action' => 'autocomplete']); ?>?term=" + text).done(function(data) {
                        if (data.length > 1 || (data.length == 1 && text != data[0].value)) {
                            autocomplete.setMenuItems(data);
                            $('#contacts-address_id').val('');
                            $('#contact-address-street').attr('readonly', false);
                            $('#contact-address-zip').attr('readonly', false);
                            $('#contact-address-city').attr('readonly', false);
                            $('#contact-address-country').attr('readonly', false);

                            // remove link icon
                            $("#contacts-title").parent("div").children("div.suffix").remove();
                        }
                    });
                },
                onAutocomplete: (entries) => {
                    if (entries.length == 1) {
                        let item = entries[0];
                        $("#contacts-title").val(item.title);
                        $("#contacts-address_id").val(item.id);

                        $('#contact-address-street').val(item.street).attr('readonly', true);
                        $('#contact-address-zip').val(item.zip).attr('readonly', true);
                        $('#contact-address-city').val(item.city).attr('readonly', true);
                        $('#contact-address-country').val(item.country).attr('readonly', true);

                        // add link icon
                        $("#contacts-title").parent("div").append("<div class='suffix'><i class='material-icons'>link</i></div>");
                    }
                }
            });

            $(elem)
                .on("keyup", function () {
                    if ($(this).val() === "") {
                        $('#contacts-address_id').val('');

                        $('#contact-address-street').attr('readonly', false);
                        $('#contact-address-zip').attr('readonly', false);
                        $('#contact-address-city').attr('readonly', false);
                        $('#contact-address-country').attr('readonly', false);
                    }
                });
        }

    });
</script>
