<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BookingRuleAccountEntry $entry
 */

use Cake\Routing\Router;

$braeEdit = [
    'title_for_layout' => $entry->id
        ? __d('expenses', 'Edit Account Entry')
        : __d('expenses', 'Add Account Entry'),
    'menu' => [
        'delete' => [
            'title' => __d('expenses', 'Delete'),
            'visible' => (bool)$entry->id,
            'url' => [
                'plugin' => 'Expenses',
                'controller' => 'BookingRuleAccountEntries',
                'action' => 'delete',
                $entry->id,
            ],
            'params' => [
                'confirm' => __d('expenses', 'Are you sure you want to delete this account entry?'),
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
                'parameters' => ['model' => $entry],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'rule_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'rule_id'],
            ],

            'fs_start' => '<fieldset>',
            'lg' => sprintf('<legend>%s</legend>', __d('expenses', 'Account Entry')),

            'account_search' => [
                'method' => 'control',
                'parameters' => ['account_search', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Account') . ':',
                    'placeholder' => __d('expenses', 'Type to search…'),
                    'autocomplete' => 'off',
                    'id' => 'account-search',
                    'default' => $entry->account ? (string)$entry->account : '',
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

            'value' => [
                'method' => 'control',
                'parameters' => ['field' => 'value', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Value (field or amount)') . ':',
                    'placeholder' => __d('expenses', 'e.g. net_total, total, 0, -150.00'),
                ]],
            ],
            'value_hint' => sprintf(
                '<div class="helper-text">%s</div>',
                __d(
                    'expenses',
                    'Enter a source field name (e.g. net_total, total) or a fixed numeric amount. '
                    . 'Positive values go to Debit, negative values go to Credit.',
                ),
            ),
            'sort' => [
                'method' => 'control',
                'parameters' => ['field' => 'sort', [
                    'type' => 'number',
                    'step' => 1,
                    'label' => __d('expenses', 'Sort order') . ':',
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

echo $this->Lil->form($braeEdit, 'Expenses.BookingRuleAccountEntries.edit');
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
    });
</script>
