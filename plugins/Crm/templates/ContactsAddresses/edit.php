<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

if ($address->id) {
    $title = __d('crm', 'Edit Address');
} else {
    $title = __d('crm', 'Add an Address');
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
                            'label' => __d('crm', 'Kind') . ':',
                            'options' => Configure::read('Crm.addressTypes'),
                            'error' => [
                                'kindOccupied' => __d('crm', 'Entry of this type already exists.'),
                            ],
                        ],
                    ],
                ],

                'address_street' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'street',
                        'options' => ['label' => __d('crm', 'Street') . ':'],
                    ],
                ],
                '<div class="input-field" id="contact-address-zip_city">',
                'address_zip' => [
                    'method' => 'text',
                    'parameters' => [
                        'field' => 'zip',
                        'options' => ['id' => 'contact-address-zip', 'style' => 'width: 100px'],
                    ],
                ],
                'address_city' => [
                    'method' => 'text',
                    'parameters' => [
                        'field' => 'city',
                        'options' => ['id' => 'contact-address-city'],
                    ],
                ],
                '<label for="contact-address-zip" class="active">' . __d('crm', 'ZIP and City') . ':</label>',
                '</div>',
                'address_country' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'country_code',
                        'options' => [
                            'type' => 'select',
                            'options' => Configure::read('Crm.countries'),
                            'label' => __d('crm', 'Country') . ':',
                            'default' => Configure::read('Crm.defaultCountry'),
                            'empty' => true,
                        ],
                    ],
                ],
                'primary' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'primary',
                        'options' => [
                            'type' => 'checkbox',
                            'label' => __d('crm', 'This is a primary address'),
                            'default' => false,
                        ],
                    ],
                ],
                'submit' => [
                    'method' => 'submit',
                    'parameters' => ['label' => __d('crm', 'Save')],
                ],
                'form_end' => [
                    'method' => 'end',
                    'parameters' => [],
                ],
            ],
        ],
    ];

    echo $this->Lil->form($editForm, 'Crm.ContactsAddresses.edit');
    ?>
<script type="text/javascript">

    var AutocompleteZipCityUrl = "<?php echo Router::url([
        'plugin' => 'Crm',
        'controller' => 'ContactsAddresses',
        'action' => 'autocomplete-zip-city',
    ], true); ?>";

    $(document).ready(function() {
        M.Autocomplete.init(
            $('#contact-address-zip').get(0),
            {
                onSearch: (text, autocomplete) => {
                    $.get(AutocompleteZipCityUrl + "/zip?term=" + $("#contact-address-zip").val()).done(function(data) {
                        autocomplete.setMenuItems(data.map((item) => ({id: item.id, text: item.value + " " + item.label})));
                    });
                },
                onAutocomplete: () => {
                    let ZipFieldValue = $('#contact-address-zip').val();
                    if (ZipFieldValue.indexOf(" ") > 0) {
                        $('#contact-address-zip').val(ZipFieldValue.substring(0, ZipFieldValue.indexOf(" ")));
                        $('#contact-address-city').val(ZipFieldValue.substring(ZipFieldValue.indexOf(" ") + 1));
                    }
                }
            }
        );

        M.Autocomplete.init(
            $('#contact-address-city').get(0),
            {
                onSearch: (text, autocomplete) => {
                    $.get(AutocompleteZipCityUrl + "/city?term=" + $("#contact-address-city").val()).done(function(data) {
                        autocomplete.setMenuItems(data.map((item) => ({id: item.id, text: item.id + " " + item.label})));
                    });
                },
                onAutocomplete: () => {
                    let ZipFieldValue = $('#contact-address-city').val();
                    if (ZipFieldValue.indexOf(" ") > 0) {
                        $('#contact-address-zip').val(ZipFieldValue.substring(0, ZipFieldValue.indexOf(" ")));
                        $('#contact-address-city').val(ZipFieldValue.substring(ZipFieldValue.indexOf(" ")+ 1));
                    }
                }
            }
        );
    });
</script>
