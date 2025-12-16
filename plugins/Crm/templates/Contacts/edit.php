<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

if ($contact->id) {
    $title = __d('crm', 'Edit Contact');
} else {
    $title = __d('crm', 'Add a Person');
    if ($contact->kind == 'C') {
        $title = __d('crm', 'Add a Company');
    }
}

$editForm = [
    'title_for_layout' => $title,
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$contact, [
                    'id' => 'contact-form',
                    'idPrefix' => 'contact',
                    'context' => ['validator' => [
                        'PrimaryEmails' => 'contact',
                        'PrimaryPhones' => 'contact',
                        'PrimaryAddresses' => 'contact',
                        'PrimaryAccounts' => 'contact',
                    ]],
                ]],
            ],
            'id' => [
                'method' => 'control',
                'parameters' => [
                    'id',
                    'options' => ['type' => 'hidden'],
                ],
            ],
            'kind' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'kind',
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
        ],
    ],
];

if ($contact->kind != 'T') {
    $editForm['form']['lines'] += [
    'fs_basics_start' => sprintf('<fieldset><legend>%s</legend>', __d('crm', 'Company Data')),
    'title' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'title',
            'options' => [
                'label' => __d('crm', 'Title') . ':',
                'error' => __d('crm', 'Please enter contact\'s title.'),
            ],
        ],
    ],
    'reg_no' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'mat_no',
            'options' => ['label' => __d('crm', 'Mat no.') . ':'],
        ],
    ],
    'tax_no_div' => '<div class="input-field">',
    'tax_no' => [
        'method' => 'text',
        'parameters' => [
            'field' => 'tax_no',
            'options' => [
                'id' => 'contact-tax-no',
            ],
        ],
    ],
    'tax_no_label' => '<label for="contact-tax-no">' . __d('crm', 'Tax no.') . ':</label>',
    'tax_no_magic' => ' ' . $this->Html->image('Crm.wand.png', ['id' => 'magic-tax-lookup', 'class' => 'btn-small filled']),
    'tax_no_error' => [
        'method' => 'error',
        'parameters' => ['tax_no', __d('crm', 'Invalid or existing tax no.')],
    ],
    'tax_no_div_end' => '</div>',
    'tax_status' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'tax_status',
            'options' => ['label' => ' ' . __d('crm', 'Tax status')],
        ],
    ],
    'descript' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'descript',
            'options' => [
                'type' => 'text',
                'label' => __d('crm', 'Description') . ':',
            ],
        ],
    ],
    'fs_basics_end' => '</fieldset>',
    ];
} else {
    $editForm['form']['lines'] += [
        'fs_basics_start' => sprintf('<fieldset><legend>%s</legend>', __d('crm', 'Personal Data')),
        'name' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'name',
                'options' => ['label' => __d('crm', 'Name') . ':'],
            ],
        ],
        'surname' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'surname',
                'options' => [
                    'label' => __d('crm', 'Surname') . ':',
                    'error' => __d('crm', 'Please enter Contact\'s name or surname.'),
                ],
            ],
        ],
        'descript' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'descript',
                'options' => [
                    'type' => 'textarea',
                    'label' => __d('crm', 'Description') . ':',
                ],
            ],
        ],
        'syncable' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'syncable',
                'options' => [
                    'type' => 'checkbox',
                    'label' => __d('crm', 'Sync-able Contact'),
                    'id' => 'edit-contact-syncable',
                    'default' => false,
                ],
            ],
        ],
        'fs_basics_end' => '</fieldset>',
        'fs_work_start' => sprintf('<fieldset><legend>%s</legend>', __d('crm', 'Work Position')),
        'company.id' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'company_id',
                'options' => ['type' => 'hidden'],
            ],
        ],
        'company_id_unlock' => [
            'method' => 'unlockField',
            'parameters' => ['company_id'],
        ],
        'company.title' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'company.title',
                'options' => [
                    'label' => [
                        'text' => __d('crm', 'Company Name') . ':',
                        'class' => 'active',
                    ],
                    'required' => false,
                    'autocomplete' => 'off',
                ],
            ],
        ],
        // removed because double popup wont work with invoices
        'company_hint' => 1==2 ? null : sprintf(
            '<div class="helper-text">%s</div>',
            $this->Lil->link(
                __d('crm', 'Start typing to select employer. You can also add a [$1new company].'),
                [
                    1 => [
                        [
                            'plugin' => 'Crm',
                            'controller' => 'Contacts',
                            'action' => 'edit',
                            '?' => ['kind' => 'C'],
                        ],
                        [
                            'id' => 'AddCompanyLink',
                            'tabIndex' => -1,
                        ],
                    ],
                ]
            )
        ),
        'job' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'job',
                'options' => [
                    'label' => __d('crm', 'Job description') . ':',
                ],
            ],
        ],
        'fs_work_end' => '</fieldset>',
    ];
}

$editForm['form']['lines'] += ['row_start' => '<div class="row">'];

$editForm['form']['lines'] += [
    'fs_address_start' => sprintf(
        '<fieldset class="col l6 m6 s12"><legend>%s</legend>',
        __d('crm', 'Primary Address')
    ),
    'address_id' => [
        'method' => 'control',
        'parameters' => ['field' => 'primary_address.id', 'options' => ['type' => 'hidden']],
    ],
    'address_contact_id' => [
        'method' => 'control',
        'parameters' => ['field' => 'primary_address.contact_id', 'options' => ['type' => 'hidden']],
    ],
    'address_primary' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_address.primary',
            'options' => ['type' => 'hidden', 'value' => true],
        ],
    ],
    'address_street' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_address.street',
            'options' => [
                'label' => __d('crm', 'Street') . ':',
                'id' => 'contact-address-street',
                'required' => false,
            ],
        ],
    ],
    'address_zipcity_wrapper' => '<div class="input-field" id="contact-address-zip_city">',
    'address_zipcity_label' => '<label for="contact-address-zip">' . __d('crm', 'ZIP and City') . ':</label>',
    'address_kind' => [
        'method' => 'hidden',
        'parameters' => [
            'field' => 'primary_address.kind',
            'options' => ['default' => 'P'],
        ],
    ],
    'address_zip' => [
        'method' => 'text',
        'parameters' => [
            'field' => 'primary_address.zip',
            'options' => ['id' => 'contact-address-zip', 'style' => 'width: 100px'],
        ],
    ],
    'address_city' => [
        'method' => 'text',
        'parameters' => [
            'field' => 'primary_address.city',
            'options' => ['id' => 'contact-address-city'],
        ],
    ],
    'address_zipcity_end' => '</div>',
    'address_country_code' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_address.country_code',
            'options' => [
                'type' => 'select',
                'options' => Configure::read('Crm.countries'),
                'label' => __d('crm', 'Country') . ':',
                'id' => 'contact-address-country',
                'default' => Configure::read('Crm.defaultCountry'),
                'empty' => true,
            ],
        ],
    ],
    'fs_address_end' => '</fieldset>',
];

$editForm['form']['lines'] += [
    'fs_account_start' => sprintf(
        '<fieldset class="col l6 m6 s12"><legend>%s</legend>',
        __d('crm', 'Primary Account')
    ),
    'account_id' => [
        'method' => 'control',
        'parameters' => ['field' => 'primary_account.id', 'options' => ['type' => 'hidden']],
    ],
    'account_contact_id' => [
        'method' => 'control',
        'parameters' => ['field' => 'primary_account.contact_id', 'options' => ['type' => 'hidden']],
    ],
    'account_kind' => [
        'method' => 'hidden',
        'parameters' => [
            'field' => 'primary_account.kind',
            'options' => ['default' => 'P'],
        ],
    ],
    'account_primary' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_account.primary',
            'options' => ['type' => 'hidden', 'value' => true],
        ],
    ],
    'account_iban' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_account.iban',
            'options' => ['label' => __d('crm', 'IBAN') . ':', 'id' => 'contact-account-iban'],
        ],
    ],
    'account_bic' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_account.bic',
            'options' => ['label' => __d('crm', 'BIC') . ':', 'id' => 'contact-account-bic'],
        ],
    ],
    'account_bank' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_account.bank',
            'options' => ['label' => __d('crm', 'Bank') . ':', 'id' => 'contact-account-bank'],
        ],
    ],
    'fs_account_end' => '</fieldset>',
];

$editForm['form']['lines'] += [
    'fs_email_start' => sprintf(
        '<fieldset class="col l6 m6 s12"><legend>%s</legend>',
        __d('crm', 'Primary Email')
    ),
    'email_id' => [
        'method' => 'control',
        'parameters' => ['field' => 'primary_email.id', 'options' => ['type' => 'hidden']],
    ],
    'email_contact_id' => [
        'method' => 'control',
        'parameters' => ['field' => 'primary_email.contact_id', 'options' => ['type' => 'hidden']],
    ],
    'email_kind' => [
        'method' => 'hidden',
        'parameters' => [
            'field' => 'primary_email.kind',
            'options' => ['default' => 'P'],
        ],
    ],
    'email_primary' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_email.primary',
            'options' => ['type' => 'hidden', 'value' => true],
        ],
    ],
    'email_email' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_email.email',
            'options' => ['label' => __d('crm', 'Email') . ':', 'id' => 'contact-email-email'],
        ],
    ],
    'fs_email_end' => '</fieldset>',
    'fs_phone_start' => sprintf(
        '<fieldset class="col l6 m6 s12"><legend>%s</legend>',
        __d('crm', 'Primary Phone')
    ),
    'phone_id' => [
        'method' => 'control',
        'parameters' => ['field' => 'primary_phone.id', 'options' => ['type' => 'hidden']],
    ],
    'phone_contact_id' => [
        'method' => 'control',
        'parameters' => ['field' => 'primary_phone.contact_id', 'options' => ['type' => 'hidden']],
    ],
    'phone_kind' => [
        'method' => 'hidden',
        'parameters' => [
            'field' => 'primary_phone.kind',
            'options' => ['default' => 'P'],
        ],
    ],
    'phone_primary' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_phone.primary',
            'options' => ['type' => 'hidden', 'value' => true],
        ],
    ],
    'phone_email' => [
        'method' => 'control',
        'parameters' => [
            'field' => 'primary_phone.no',
            'options' => ['label' => __d('crm', 'Phone Number') . ':', 'id' => 'contact-phone-no'],
        ],
    ],
    'fs_phone_end' => '</fieldset>',
];

// finish class="row"
$editForm['form']['lines'] += ['row_end' => '</div>'];

$editForm['form']['lines'] += [
    'submit' => [
        'method' => 'button',
        'parameters' => [__d('crm', 'Save'), ['type' => 'submit']],
    ],
    'form_end' => [
        'method' => 'end',
        'parameters' => [],
    ],
];

echo $this->Lil->form($editForm, 'Crm.Contacts.edit');

?>

<script type="text/javascript">
    var banks = {<?php
    $bd = [];
    foreach ($banks = Configure::read('Crm.banks') as $bankId => $bank) {
        $bd[] = 'bd' . $bankId . ':"' . $bank['bic'] . '"';
        $bd[] = $bank['bic'] . ':"' . $bank['name'] . '"';
    }
    echo implode(', ', $bd);
    ?>};

    var AutocompleteZipCityUrl = "<?php echo Router::url([
        'plugin' => 'Crm',
        'controller' => 'ContactsAddresses',
        'action' => 'autocomplete-zip-city',
    ], true); ?>";

    var AutocompleteClientUrl = "<?php echo Router::url([
        'plugin' => 'Crm',
        'controller' => 'Contacts',
        'action' => 'autocomplete',
        'kind' => 'C',
    ], true); ?>";

    $(document).ready(function() {

        <?php
        if ($contact->kind == "T") {
        ?>

        // modal popup for adding new company
        // removed because double popup wont work with invoices
        //$("#AddCompanyLink").modalPopup({
        //    title: "<?= __d('crm', 'Add a Company') ?>",
        //    processSubmit: true,
        //    onJson: function(company) {
        //        $("#contact-company-id").val(company.id);
        //        $("#contact-company-title").val(company.title);
        //        $("#contact-company-title").parent("div").append("<div class='suffix'><i class='material-icons'>link</i></div>");
        //    }
        //});

        // autocomplete for selecting company
        M.Autocomplete.init(
            $("#contact-company-title").get(0),
            {
                onSearch: (text, autocomplete) => {
                    $.get(AutocompleteClientUrl + "?term=" + text).done(function(data) {
                        if (data.length > 1 || (data.length == 1 && text != data[0].value)) {
                            autocomplete.setMenuItems(data.map((item) => ({id: item.id, text: item.value})));
                            $("#contact-company-id").val("");
                            $("#contact-company-title").parent("div").children("div.suffix").remove();
                        }
                    });
                },
                onAutocomplete: (entries) => {
                    if (entries.length == 1) {
                        $("#contact-company-id").val(entries[0].id);
                        $("#contact-company-title").parent("div").append("<div class='suffix'><i class='material-icons'>link</i></div>");
                    }
                }
            }
        );

        <?php
        }
        ?>

        M.Autocomplete.init(
            $("#contact-address-zip").get(0),
            {
                onSearch: (text, autocomplete) => {
                    $.get(AutocompleteZipCityUrl + "/zip?term=" + $("#contact-address-zip").val()).done(function(data) {
                        autocomplete.setMenuItems(data.map((item) => ({id: item.id, text: item.value + " " + item.label})));
                    });
                },
                onAutocomplete: () => {
                    let ZipFieldValue = $("#contact-address-zip").val();
                    if (ZipFieldValue.indexOf(" ") > 0) {
                        $("#contact-address-zip").val(ZipFieldValue.substring(0, ZipFieldValue.indexOf(" ")));
                        $("#contact-address-city").val(ZipFieldValue.substring(ZipFieldValue.indexOf(" ")+ 1));
                    }
                }
            }
        );

        M.Autocomplete.init(
            $("#contact-address-city").get(0),
            {
                onSearch: (text, autocomplete) => {
                    $.get(AutocompleteZipCityUrl + "/city?term=" + $("#contact-address-city").val()).done(function(data) {
                        autocomplete.setMenuItems(data.map((item) => ({id: item.id, text: item.id + " " + item.label})));
                    });
                },
                onAutocomplete: () => {
                    let ZipFieldValue = $("#contact-address-city").val();
                    if (ZipFieldValue.indexOf(" ") > 0) {
                        $("#contact-address-zip").val(ZipFieldValue.substring(0, ZipFieldValue.indexOf(" ")));
                        $("#contact-address-city").val(ZipFieldValue.substring(ZipFieldValue.indexOf(" ")+ 1));
                    }
                }
            }
        );

        $("#magic-tax-lookup").click(function() {
            let inetisUrl = "<?php echo Router::url([
                'plugin' => 'Crm',
                'controller' => 'Contacts',
                'action' => 'inetis',
            ], true); ?>/";

            let parentForm = $(this).closest("form");

            let taxNo = $("#contact-tax-no").val();

            if (taxNo.trim() == "") {
                alert("No tax number entered!");

                return false;
            }

            $.get(inetisUrl + taxNo, function(data) {
                $("#contact-title", parentForm).val(data.title);
                $("#contact-mat-no", parentForm).val(data.mat_no);
                $("#contact-tax-no", parentForm).val(data.tax_no);
                $("#contact-tax-status", parentForm).attr("checked", data.tax_no.substr(0,2) == "SI");

                if (data.primary_address) {
                    $("#contact-address-street", parentForm).val(data.primary_address.street);
                    $("#contact-address-zip", parentForm).val(data.primary_address.zip);
                    $("#contact-address-city", parentForm).val(data.primary_address.city);
                    $("#contact-address-country-code", parentForm).val(data.primary_address.country_code);
                }

                if (data.primary_account) {
                    $("#contact-account-iban", parentForm).val(data.primary_account.iban);
                    $("#contact-account-bic", parentForm).val(data.primary_account.bic);
                    $("#contact-account-bank", parentForm).val(data.primary_account.bank);
                }
            })
            .fail(function() { alert("INETIS request failed."); });
        });

        // SHOW LINK ICON FOR CLIENT IF IT'S ID EXISTS
        if ($("#contact-company-id").val() !== "") {
            $("#contact-company-title").parent("div").append("<div class='suffix'><i class='material-icons'>link</i></div>");
        }

        $("#contact-account-iban").blur(function() {
            var iban = $("#contact-account-iban").val().split(" ").join("");
            var bic = $("#contact-account-bic").val().trim();
            var bank = $("#contact-account-bank").val().trim();
            if ((iban.substr(0, 4) == "SI56") && (bic == "") && typeof banks["bd" + iban.substr(4, 2)] != "undefined") {
                bic = banks["bd" + iban.substr(4, 2)];
                $("#contact-account-bic").val(bic);
                if (bank == "" && typeof banks[bic] != "undefined") {
                    $("#contact-account-bank").val(banks[bic]);
                }
            }
        });
    });
</script>
