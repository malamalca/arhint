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
            'email_id' => [
                'method' => 'hidden',
                'parameters' => [
                    'field' => 'contacts_email_id',
                    ['id' => 'contacts-email_id'],
                ],
            ],
            'address_id_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['contacts_address_id'],
            ],
            'email_id_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['contacts_email_id'],
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
            'address_start' => '<br /><fieldset id="ManualAddress">',
            'email' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'email',
                    'options' => [
                        'label' => __d('crm', 'Email') . ':',
                        'disabled' => $address->email ? 'disabled' : '',
                        'id' => 'contacts-email',
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
            'address_end' => '</fieldset>',
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
                    $.get("<?php echo Router::url(['controller' => 'Contacts', 'action' => 'autocomplete']); ?>?full=1&term=" + text).done(function(data) {

                        if (data.length > 1 || (data.length == 1 && text != data[0].value)) {
                            $('#contacts-address_id').val('');
                            $('#contacts-email_id').val('');
                            $('#contact-address-street').attr('readonly', false);
                            $('#contact-address-zip').attr('readonly', false);
                            $('#contact-address-city').attr('readonly', false);
                            $('#contact-address-country').attr('readonly', false);
                            $('#contacts-email').attr('readonly', false);

                            // remove link icon
                            $("#contacts-title").parent("div").children("div.suffix").remove();
                        }

                        autocomplete.setMenuItems(data);
                    });
                },
                onAutocomplete: (entries) => {
                    if (entries.length == 1) {
                        let item = entries[0];
                        $("#contacts-title").val(item.client.title);
                        
                        if (item.client.contacts_addresses.length > 0) {
                            $("#contacts-address_id").val(item.client.contacts_addresses[0].id);
                            $('#contact-address-street').val(item.client.contacts_addresses[0].street).attr('readonly', true);
                            $('#contact-address-zip').val(item.client.contacts_addresses[0].zip).attr('readonly', true);
                            $('#contact-address-city').val(item.client.contacts_addresses[0].city).attr('readonly', true);
                            $('#contact-address-country').val(item.client.contacts_addresses[0].country).attr('readonly', true);
                        }

                        console.log(item.client.contacts_emails);
                        if (item.client.contacts_emails.length > 0) {
                            $("#contacts-email_id").val(item.client.contacts_emails[0].id);
                            $('#contacts-email').val(item.client.contacts_emails[0].email).attr('readonly', true);
                        }

                        // add link icon
                        $("#contacts-title").parent("div").append("<div class='suffix'><i class='material-icons'>link</i></div>");
                    }
                }
            });

            $(elem)
                .on("keyup", function () {
                    if ($(this).val() === "") {
                        $('#contacts-address_id').val('');
                        $('#contacts-email_id').val('');

                        $('#contact-address-street').attr('readonly', false);
                        $('#contact-address-zip').attr('readonly', false);
                        $('#contact-address-city').attr('readonly', false);
                        $('#contact-address-country').attr('readonly', false);
                        $('#contacts-email').attr('readonly', false);
                    }
                });
        }

    });
</script>
