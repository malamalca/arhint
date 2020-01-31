<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

if ($address->id) {
    $title = __d('lil_crm', 'Edit Address');
} else {
    $title = __d('lil_crm', 'Add an Address');
}

    $editForm = [
        'title_for_layout' => $title,
        'form' => [
            'pre' => '<div class="form">',
            'post' => '</div>',
            'defaultHelper' => $this->Form,
            'lines' => [
                'form_start' => [
                    'method' => 'create',
                    'parameters' => [$address, [
                        'id' => 'contacts-address-form',
                        'idPrefix' => 'contact-address',
                    ]],
                ],
                'id' => [
                    'method' => 'control',
                    'parameters' => ['id', 'options' => ['type' => 'hidden']],
                ],
                'contact_id' => [
                    'method' => 'control',
                    'parameters' => ['contact_id', 'options' => ['type' => 'hidden']],
                ],
                'referer' => [
                    'method' => 'control',
                    'parameters' => ['referer', 'options' => ['type' => 'hidden']],
                ],

                'kind' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'kind',
                        'options' => [
                            'type' => 'select',
                            'label' => [
                                'text' => __d('lil_crm', 'Kind') . ':',
                                'class' => 'active',
                            ],
                            'options' => Configure::read('LilCrm.addressTypes'),
                            'error' => [
                                'kindOccupied' => __d('lil_crm', 'Entry of this type already exists.'),
                            ],
                            'class' => 'browser-default',
                        ],
                    ],
                ],

                //'fs_address_start' => sprintf('<fieldset><legend>%s</legend>', __d('lil_crm', 'Address')),
                'address_street' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'street',
                        'options' => ['label' => __d('lil_crm', 'Street') . ':'],
                    ],
                ],
                '<div class="input-field" id="contact-address-zip_city">',
                'address_zip' => [
                    'method' => 'text',
                    'parameters' => [
                        'field' => 'zip',
                        'options' => ['id' => 'contact-address-zip'],
                    ],
                ],
                'address_city' => [
                    'method' => 'text',
                    'parameters' => [
                        'field' => 'city',
                        'options' => ['id' => 'contact-address-city'],
                    ],
                ],
                '<label for="contact-address-zip" class="active">' . __d('lil_crm', 'ZIP and City') . ':</label>',
                '</div>',
                'address_country' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'country_code',
                        'options' => [
                            'type' => 'select',
                            'options' => Configure::read('LilCrm.countries'),
                            'label' => [
                                'text' => __d('lil_crm', 'State') . ':',
                                'class' => 'active',
                            ],
                            'default' => Configure::read('LilCrm.defaultCountry'),
                            'empty' => true,
                            'class' => 'browser-default',
                        ],
                    ],
                ],
                //'fs_address_end' => '</fieldset>',
                'primary' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'primary',
                        'options' => [
                            'type' => 'checkbox',
                            'label' => __d('lil_crm', 'This is a primary address'),
                            'default' => false,
                        ],
                    ],
                ],
                'submit' => [
                    'method' => 'submit',
                    'parameters' => ['label' => __d('lil_crm', 'Save')],
                ],
                'form_end' => [
                    'method' => 'end',
                    'parameters' => [],
                ],
            ],
        ],
    ];

    echo $this->Lil->form($editForm, 'LilCrm.ContactsAddresses.edit');
    ?>
<script type="text/javascript">

    $(document).ready(function() {
        $('#contact-address-zip').autocompleteajax({
            source: "<?php echo Router::url([
                'plugin' => 'LilCrm',
                'controller' => 'ContactsAddresses',
                'action' => 'autocomplete-zip-city',
                'zip',
            ], true); ?>",
            onSelect: function(item) {
                $('#contact-address-zip').val(item.value);
                $('#contact-address-city').val(item.label);
            }
        });


        $('#contact-address-city').autocompleteajax({
            source: '<?php echo Router::url([
                'plugin' => 'LilCrm',
                'controller' => 'ContactsAddresses',
                'action' => 'autocomplete-zip-city',
                'city',
            ], true); ?>',
            onSelect: function(item) {
                $('#contact-address-zip').val(item.id);
                $('#contact-address-city').val(item.label);
            }
        });
    });
</script>
