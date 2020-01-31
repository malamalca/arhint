<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$editAddressForm = [
    'title_for_layout' => $address->id ? __d('lil_crm', 'Edit Address') : __d('lil_crm', 'Add Address'),
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
                        'label' => __d('lil_crm', 'Title') . ':',
                        'error' => __d('lil_crm', 'Title is required.'),
                        'id' => 'contacts-title',
                    ],
                ],
            ],
            'link_image' => $this->Html->image('/lil_crm/img/ico_contact_check.gif', [
                'style' => $address->contacts_address_id ? '' : 'display: none;',
                'id' => 'ImageContactCheck',
            ]),
            'street' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'street',
                    'options' => [
                        'label' => __d('lil_crm', 'Street') . ':',
                        'disabled' => $address->contacts_address_id ? 'disabled' : '',
                    ],
                ],
            ],
            '<div class="input-field text" id="contact-address-zip_city">',
            '<label for="#contact-address-zip" class="active">' . __d('lil_crm', 'ZIP and City') . ':</label>',
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
                        'label' => __d('lil_crm', 'Country') . ':',
                        'disabled' => $address->contacts_address_id ? 'disabled' : '',
                        'default' => Configure::read('LilCrm.defaultCountry'),
                        'options' => Configure::read('LilCrm.countries'),
                        'empty' => true,
                    ],
                ],
            ],
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_crm', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($editAddressForm, 'LilCrm.Labels.edit_address');

?>
<script type="text/javascript">
    $(document).ready(function() {
        $("#contacts-title").autocomplete({
            autoFocus: true,
            source: '<?php echo Router::url(['controller' => 'ContactsAddresses', 'action' => 'autocomplete']); ?>',
            search: function() {
                $('#contacts-address_id').val('');
                $('#ImageContactCheck').hide();

                $('#contact-address-street').attr('disabled', false);
                $('#contact-address-zip').attr('disabled', false);
                $('#contact-address-city').attr('disabled', false);
                $('#contact-address-country').attr('disabled', false);
            },
            select: function(event, ui) {
                if (ui.item) {
                    $('#ImageContactCheck').show();
                    $("#contacts-title").val(ui.item.title);
                    $("#contacts-address_id").val(ui.item.id);

                    $('#contact-address-street').val(ui.item.street).attr('disabled', true);
                    $('#contact-address-zip').val(ui.item.zip).attr('disabled', true);
                    $('#contact-address-city').val(ui.item.city).attr('disabled', true);
                    $('#contact-address-country').val(ui.item.country).attr('disabled', true);
                }
            }
        }).keyup(function() {
            if ($(this).val() === "") {
                $('#contacts-address_id').val('');
                $('#ImageContactCheck').hide();

                $('#contact-address-street').attr('disabled', false);
                $('#contact-address-zip').attr('disabled', false);
                $('#contact-address-city').attr('disabled', false);
                $('#contact-address-country').attr('disabled', false);
            }
        });
    });
</script>
