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
            'adrema_id' => [
                'method' => 'hidden',
                'parameters' => [
                    'field' => 'adrema_id',
                ],
            ],
            'contact_id' => [
                'method' => 'hidden',
                'parameters' => [
                    'field' => 'contact_id',
                    ['id' => 'contacts-contact_id'],
                ],
            ],
            'unlock_contact_id' => [
                'method' => 'unlockField',
                'parameters' => ['contact_id'],
            ],
             'unlock_contacts_address_id' => [
                'method' => 'unlockField',
                'parameters' => ['contacts_address_id'],
            ],
             'unlock_contacts_email_id' => [
                'method' => 'unlockField',
                'parameters' => ['contacts_email_id'],
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
                        'value' => $address->contact->title ?? '',
                    ],
                ],
            ],
            'address_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'contacts_address_id',
                    [
                        'id' => 'contacts-address_id',
                        'class' => 'browser-default',
                        'label' => false,
                        'options' => $addresses,
                        'value' => $address->contacts_address_id,
                    ],
                ],
            ],
            'email_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'contacts_email_id',
                    [
                        'id' => 'contacts-email_id',
                        'class' => 'browser-default',
                        'label' => false,
                        'options' => $emails,
                        'value' => $address->contacts_email_id,
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

/** Show costum fields form selected label */
$fields = explode(PHP_EOL, $adrema->additional_fields);
if (count($fields) > 0) {
    $additionalFields = [];
    $additionalData = $address->descript ? json_decode($address->descript, true) : [];
    foreach ($fields as $field) {
        $fieldParts = explode(':', $field);
        $additionalFields[$fieldParts[0]] = [
            'method' => 'control',
            'parameters' => [
                'field' => 'data.' . $fieldParts[0],
                [
                    'type' => $fieldParts[1],
                    'value' => $additionalData[$fieldParts[0]] ?? '',
                ],
            ],
        ];
    }
    $this->Lil->insertIntoArray($editAddressForm['form']['lines'], $additionalFields, ['before' => 'submit']);
}


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
                            $('#contacts-contact_id').val('');
                            $('#contacts-address_id').find("option").remove();
                            $('#contacts-email_id').find("option").remove();

                            // remove link icon
                            $("#contacts-title").parent("div").children("div.suffix").remove();
                        }

                        autocomplete.setMenuItems(data);
                    });
                },
                onAutocomplete: (entries) => {
                    if (entries.length == 1) {
                        let item = entries[0];
                        $('#contacts-contact_id').val(item.client.id);
                        $("#contacts-title").val(item.client.title);

                        $('#contacts-address_id').find("option").remove();
                        $('#contacts-email_id').find("option").remove();
                        
                        if (item.client.contacts_addresses.length > 0) {
                            $(item.client.contacts_addresses).each(function () {
                                $("<option />", {
                                    val: this.id,
                                    text: this.street + ', ' + this.zip + ' ' + this.city + ', ' + this.country
                                }).appendTo($("#contacts-address_id"));
                            });
                        }

                        if (item.client.contacts_emails.length > 0) {
                            $(item.client.contacts_emails).each(function () {
                                $("<option />", {
                                    val: this.id,
                                    text: this.email
                                }).appendTo($("#contacts-email_id"));
                            });
                        }

                        // add link icon
                        $("#contacts-title").parent("div").append("<div class='suffix'><i class='material-icons'>link</i></div>");
                    }
                }
            });

            $(elem)
                .on("keyup", function () {
                    if ($(this).val() === "") {
                        $('#contacts-contact_id').val('');
                        $('#contacts-address_id').find("option").remove();
                        $('#contacts-email_id').find("option").remove();

                        // remove link icon
                        $("#contacts-title").parent("div").children("div.suffix").remove();
                    }
                });
        }

    });
</script>
