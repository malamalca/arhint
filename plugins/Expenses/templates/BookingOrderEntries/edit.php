<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BookingOrderEntry $bookingOrderEntry
 * @var string $linkedTitle
 */
use Cake\Routing\Router;

$boeEdit = [
    'title_for_layout' => $bookingOrderEntry->id
        ? __d('expenses', 'Edit Entry')
        : __d('expenses', 'Add Entry'),
    'menu' => [
        'delete' => [
            'title' => __d('expenses', 'Delete'),
            'visible' => (bool)$bookingOrderEntry->id,
            'url' => [
                'plugin' => 'Expenses',
                'controller' => 'BookingOrderEntries',
                'action' => 'delete',
                $bookingOrderEntry->id,
            ],
            'params' => [
                'confirm' => __d('expenses', 'Are you sure you want to delete this entry?'),
            ],
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $bookingOrderEntry],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'booking_order_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'booking_order_id'],
            ],

            'fs_start' => '<fieldset>',
            'lg' => sprintf('<legend>%s</legend>', __d('expenses', 'Entry')),

            'account_search' => [
                'method' => 'control',
                'parameters' => ['account_search', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Account') . ':',
                    'placeholder' => __d('expenses', 'Type to search…'),
                    'autocomplete' => 'off',
                    'id' => 'account-search',
                    'default' => $bookingOrderEntry->account ? (string)$bookingOrderEntry->account : '',
                ]],
            ],
            'account_error' => [
                'method' => 'error',
                'parameters' => ['account_id'],
            ],
            'account_hint' => sprintf(
                '<div class="helper-text">%s</div>',
                $this->Lil->link(
                    __d('expenses', 'Type to search or [$1browse all accounts].'),
                    [
                        1 => [
                            [
                                'plugin' => 'Expenses',
                                'controller' => 'Accounts',
                                'action' => 'pick',
                            ],
                            [
                                'id' => 'AccountPickLink',
                                'tabindex' => -1,
                            ],
                        ],
                    ],
                ),
            ),
            'account_id' => [
                'method' => 'hidden',
                'parameters' => ['account_id', ['id' => 'account-id']],
            ],
            'account_id_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['account_id'],
            ],
            'partner_search' => [
                'method' => 'control',
                'parameters' => ['partner_search', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Partner') . ':',
                    'placeholder' => __d('expenses', 'Type to search…'),
                    'autocomplete' => 'off',
                    'id' => 'partner-search',
                    'default' => $bookingOrderEntry->partner && $bookingOrderEntry->partner->contact
                        ? $bookingOrderEntry->partner->contact->title
                        : '',
                ]],
            ],
            'partner_error' => [
                'method' => 'error',
                'parameters' => ['partner_id'],
            ],
            'partner_id' => [
                'method' => 'hidden',
                'parameters' => ['partner_id', ['id' => 'partner-id']],
            ],
            'partner_id_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['partner_id'],
            ],
            'model_select' => [
                'method' => 'control',
                'parameters' => ['model', [
                    'type' => 'select',
                    'empty' => __d('expenses', '— None —'),
                    'options' => [
                        'Invoices' => __d('expenses', 'Invoices'),
                        'Documents' => __d('expenses', 'Documents'),
                        'TravelOrders' => __d('expenses', 'Travel Orders'),
                        'BankStatements' => __d('expenses', 'Bank Statements'),
                    ],
                    'label' => __d('expenses', 'Model') . ':',
                    'id' => 'doc-model',
                ]],
            ],
            'foreign_search' => [
                'method' => 'control',
                'parameters' => ['foreign_search', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Document') . ':',
                    'placeholder' => __d('expenses', 'Type to search…'),
                    'autocomplete' => 'off',
                    'id' => 'foreign-search',
                    'default' => $linkedTitle ?? '',
                ]],
            ],
            'foreign_error' => [
                'method' => 'error',
                'parameters' => ['foreign_id'],
            ],
            'foreign_id' => [
                'method' => 'hidden',
                'parameters' => ['foreign_id', ['id' => 'foreign-id']],
            ],
            'foreign_id_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['foreign_id'],
            ],
            'descript' => [
                'method' => 'control',
                'parameters' => ['field' => 'descript', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Description') . ':',
                ]],
            ],
            'debit' => [
                'method' => 'control',
                'parameters' => ['field' => 'debit', [
                    'type' => 'number',
                    'step' => '0.01',
                    'label' => __d('expenses', 'Debit') . ':',
                ]],
            ],
            'credit' => [
                'method' => 'control',
                'parameters' => ['field' => 'credit', [
                    'type' => 'number',
                    'step' => '0.01',
                    'label' => __d('expenses', 'Credit') . ':',
                ]],
            ],

            'fs_end' => '</fieldset>',

            'submit' => [
                'method' => 'submit',
                'parameters' => ['label' => __d('expenses', 'Save')],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];

echo $this->Lil->form($boeEdit, 'Expenses.BookingOrderEntries.edit');
?>
<script type="text/javascript">
    $(document).ready(function() {
        var AccountsAutocompleteUrl = "<?= Router::url([
            'plugin' => 'Expenses',
            'controller' => 'Accounts',
            'action' => 'autocomplete',
            '_ext' => 'json',
        ], true); ?>";

        M.Autocomplete.init(
            $("#account-search").get(0),
            {
                allowUnsafeHTML: true,
                onSearch: (text, autocomplete) => {
                    $.get(AccountsAutocompleteUrl + "?term=" + encodeURIComponent(text)).done(function(data) {
                        if (data.length > 0) {
                            autocomplete.setMenuItems(
                                data.map((item) => ({ id: item.id, text: item.value }))
                            );
                        }
                    });
                },
                onAutocomplete: (entries) => {
                    if (entries.length === 1) {
                        let item = entries[0];
                        $("#account-search").val(item.text);
                        $("#account-id").val(item.id);
                    }
                },
            }
        );

        $("#AccountPickLink").modalPopup({
            title: <?= json_encode(__d('expenses', 'Select Account')) ?>,
            onOpen: function(popup) {
                var $filter = $("#account-pick-filter", popup);
                $filter.on("input", function() {
                    var q = $(this).val().toLowerCase();
                    $(".account-pick-row", popup).each(function() {
                        var visible = q === ""
                            || $(this).data("code").toLowerCase().indexOf(q) !== -1
                            || $(this).data("name").toLowerCase().indexOf(q) !== -1;
                        $(this).toggle(visible);
                    });
                }).trigger("focus");
                $(".account-pick-row", popup).on("click", function() {
                    var id   = $(this).data("id");
                    var code = $(this).data("code");
                    var name = $(this).data("name");
                    $("#account-id").val(id);
                    $("#account-search").val(code + " \u2013 " + name);
                    $("#account-search").next("label").addClass("active");
                    $(".modal-close", popup).trigger("click");
                });
            },
        });

        var PartnersAutocompleteUrl = "<?= Router::url([
            'plugin' => 'Expenses',
            'controller' => 'Partners',
            'action' => 'autocomplete',
            '_ext' => 'json',
        ], true); ?>";

        M.Autocomplete.init(
            $("#partner-search").get(0),
            {
                allowUnsafeHTML: true,
                onSearch: (text, autocomplete) => {
                    $.get(PartnersAutocompleteUrl + "?term=" + encodeURIComponent(text)).done(function(data) {
                        if (data.length > 0) {
                            autocomplete.setMenuItems(
                                data.map((item) => ({ id: item.id, text: item.value }))
                            );
                        }
                    });
                },
                onAutocomplete: (entries) => {
                    if (entries.length === 1) {
                        let item = entries[0];
                        $("#partner-search").val(item.text);
                        $("#partner-id").val(item.id);
                    }
                },
            }
        );
        var ModelAutocompleteUrl = "<?= Router::url([
            'plugin' => 'Expenses',
            'controller' => 'BookingOrderEntries',
            'action' => 'modelAutocomplete',
            '_ext' => 'json',
        ], true); ?>";

        $('#doc-model').on('change', function() {
            $('#foreign-search').val('');
            $('#foreign-id').val('');
        });

        M.Autocomplete.init(
            $("#foreign-search").get(0),
            {
                allowUnsafeHTML: true,
                onSearch: (text, autocomplete) => {
                    var model = $('#doc-model').val();
                    if (!model) return;
                    $.get(
                        ModelAutocompleteUrl
                        + '?model=' + encodeURIComponent(model)
                        + '&term=' + encodeURIComponent(text)
                    ).done(function(data) {
                        if (data.length > 0) {
                            autocomplete.setMenuItems(
                                data.map((item) => ({ id: item.id, text: item.value }))
                            );
                        }
                    });
                },
                onAutocomplete: (entries) => {
                    if (entries.length === 1) {
                        let item = entries[0];
                        $("#foreign-search").val(item.text);
                        $("#foreign-id").val(item.id);
                    }
                },
            }
        );
    });
</script>
