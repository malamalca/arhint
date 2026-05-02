<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $entity
 * @var string $model
 * @var string $foreignId
 * @var array<string, string> $entityInfo
 * @var bool $isLocked
 * @var bool $showEditForm
 * @var array<int, array<string, mixed>> $displayEntries
 * @var string $initialBoId
 * @var string $initialBoLabel
 */
use Cake\Routing\Router;
use Expenses\Lib\BookingLinker;

// ── Entity info panel ────────────────────────────────────────────────────────
$infoLines = [];
$infoLines[] = '<div class="card green lighten-4 mt-4 mb-4"><div class="card-content">';
$infoLines[] = '<span class="card-title">' . h(BookingLinker::entityName($entity, $model)) . '</span><p>';
foreach ($entityInfo as $label => $value) {
    $infoLines[] = sprintf(
        '<div class="input-field"><span style="width:100px; display:inline-block;" class="blue-text">%s:</span> %s</div>',
        h($label),
        h($value),
    );
}
$infoLines[] = '</p></div></div>';

// ── Booking entries fieldset ──────────────────────────────────────────────
$entr = 0;
$entriesLines = [];
$entriesLines['entr_' . $entr++] = '<fieldset>';
$entriesLines['entr_' . $entr++] = '<legend>' . h(__d('expenses', 'Booking Entries')) . '</legend>';
$entriesLines['entr_' . $entr++] =
    '<table id="bookings-table" class="responsive-table">'
    . '<thead><tr>'
    . '<th style="width:2em">' . h(__d('expenses', 'No')) . '</th>'
    . '<th>' . h(__d('expenses', 'Account')) . '</th>'
    . '<th class="right-align" style="width:8em">' . h(__d('expenses', 'Debit')) . '</th>'
    . '<th class="right-align" style="width:8em">' . h(__d('expenses', 'Credit')) . '</th>'
    . '<th style="width:3em"></th>'
    . '</tr></thead>';
$entriesLines['entr_' . $entr++] = '<tbody>';
foreach ($displayEntries as $idx => $entry) {
    $entriesLines['entr_' . $entr++] = '<tr class="booking-row">';

    if ($isLocked) {
        // Read-only row for locked/posted booking orders.
        $entriesLines['entr_' . $entr++] =
            '<td class="center no-cell">' . h((string)($entry['no'] ?? $idx + 1)) . '</td>';
        $entriesLines['entr_' . $entr++] =
            '<td>' . h((string)$entry['account_label']) . '</td>';
        $entriesLines['entr_' . $entr++] =
            '<td class="right-align">' . h($this->Number->currency((float)$entry['debit'])) . '</td>';
        $entriesLines['entr_' . $entr++] =
            '<td class="right-align">' . h($this->Number->currency((float)$entry['credit'])) . '</td>';
        $entriesLines['entr_' . $entr++] =
            '<td class="center"><i class="material-icons grey-text">lock</i></td>';
    } else {
        $noValue = (int)($entry['no'] ?? $idx + 1);
        $noDisplay = $noValue > 0 ? (string)$noValue : '–';
        $entriesLines['entr_' . $entr++] =
            sprintf('<td class="center no-cell pt-1 pb-1"><span class="no-value">%s</span>', $noDisplay);
        $entriesLines['entr_' . $entr++] = [
            'method' => 'hidden',
            'parameters' => [
                'entries.' . $idx . '.no',
                ['id' => 'no-' . $idx, 'class' => 'no-hidden', 'value' => (string)$noValue],
            ],
        ];
        $entriesLines['entr_' . $entr++] = '</td>';

        $entriesLines['entr_' . $entr++] = '<td class="pt-1 pb-1">';
        $entriesLines['entr_' . $entr++] = [
            'method' => 'text',
            'parameters' => [
                'entries.' . $idx . '.account_search', [
                    'id' => 'account-search-' . $idx,
                    'class' => 'account-search browser-default',
                    'style' => 'width:100%',
                    'autocomplete' => 'off',
                    'placeholder' => __d('expenses', 'Type to search…'),
                    'value' => (string)$entry['account_label'],
                ],
            ],
        ];
        $entriesLines['entr_' . $entr++] = [
            'method' => 'hidden',
            'parameters' => [
                'entries.' . $idx . '.id',
                ['id' => 'id-' . $idx, 'class' => 'id', 'value' => (string)$entry['id']],
            ],
        ];
        $entriesLines['entr_' . $entr++] = [
            'method' => 'hidden',
            'parameters' => [
                'entries.' . $idx . '.account_id',
                ['id' => 'account-id-' . $idx, 'class' => 'account-id', 'value' => (string)$entry['account_id']],
            ],
        ];
        $entriesLines['entr_' . $entr++] = '</td>';

        $entriesLines['entr_' . $entr++] = '<td class="right-align pt-1 pb-1">';
        $entriesLines['entr_' . $entr++] = [
            'method' => 'text',
            'parameters' => [
                'entries.' . $idx . '.debit', [
                    'type' => 'number',
                    'class' => 'browser-default',
                    'step' => '0.01',
                    'min' => '0.00',
                    'default' => '0.00',
                    'label' => false,
                    'value' => (string)$entry['debit'],
                ],
            ],
        ];
        $entriesLines['entr_' . $entr++] = '</td>';

        $entriesLines['entr_' . $entr++] = '<td class="right-align pt-1 pb-1">';
        $entriesLines['entr_' . $entr++] = [
            'method' => 'text',
            'parameters' => [
                'entries.' . $idx . '.credit', [
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0.00',
                    'default' => '0.00',
                    'class' => 'browser-default',
                    'label' => false, 'value' => (string)$entry['credit'],
                ],
            ],
        ];
        $entriesLines['entr_' . $entr++] = '</td>';

        $entriesLines['entr_' . $entr++] = sprintf(
            '<td class="pt-1 pb-1">'
            . '<a href="#" class="remove-row btn-flat btn-small filled" title="%s">'
            . '<i class="material-icons">remove_circle_outline</i>'
            . '</a></td>',
            h(__d('expenses', 'Remove')),
        );
    }

    $entriesLines['entr_' . $entr++] = '</tr>';
}
$entriesLines['entr_' . $entr++] = '</tbody>';
if (!$isLocked) {
    $entriesLines['entr_' . $entr++] = sprintf(
        '<tfoot><tr><td colspan="5"><a href="#" id="add-booking-row" class="btn-flat btn-small">'
        . '<i class="material-icons left">add</i>%s</a></td></tr></tfoot>',
        h(__d('expenses', 'Add row')),
    );
}
$entriesLines['entr_' . $entr++] = '</table>';
$entriesLines['entr_' . $entr++] = '</fieldset>';

$pageView = [
    //'title_for_layout' => __d('expenses', 'Bookings: {0}', h($model)),
    'title_for_layout' => __d('expenses', 'Manage Booking Order Entries'),
    'menu' => [],
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [null, [
                    'url' => [
                        'plugin' => 'Expenses',
                        'controller' => 'BookingOrders',
                        'action' => 'links',
                        '?' => ['model' => $model, 'foreignid' => $foreignId],
                    ],
                ]],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'redirect', ['value' => $this->getRequest()->getQuery('redirect')]],
            ],
            'bo_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'bookingid', [
                    'id' => 'bo-id',
                    'value' => $initialBoId,
                ]],
            ],
            'bookingid_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['bookingid'],
            ],
            'bo_search' => [
                'method' => 'control',
                'parameters' => [
                    'bo-search', [
                        'type' => 'text',
                        'id' => 'bo-search',
                        'autocomplete' => 'off',
                        'placeholder' => __d('expenses', 'Type to search…'),
                        'label' => __d('expenses', 'Booking Order') . ':',
                        'value' => $initialBoLabel,
                    ],
                ],
            ],
            'bo_hint' => sprintf(
                '<div class="helper-text">%s</div>',
                $this->Lil->link(
                    __d('documents', 'Select your Booking Order or click [$1here] to add a new one.'),
                    [1 => [
                            ['plugin' => 'Expenses', 'controller' => 'BookingOrders', 'action' => 'edit'],
                            ['id' => 'AddBoLink', 'tabIndex' => -1],
                    ]],
                ),
            ),
            'partner_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'partner_id', ['id' => 'partner-id-top']]],
            'partner_id_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['partner_id', ['id' => 'partner-id-top', 'value' => $initialPartnerId]],
            ],
            'partner_search' => ['method' => 'control', 'parameters' => ['partner-search-top', [
                'type' => 'text',
                'id' => 'partner-search-top',
                'autocomplete' => 'off',
                'placeholder' => __d('expenses', 'Type to search…'),
                'label' => __d('expenses', 'Partner') . ':',
                'value' => $initalPartnerLabel,
            ]]],
            'partner_hint' => sprintf(
                '<div class="helper-text">%s</div>',
                $this->Lil->link(
                    __d('documents', 'Find and select an existing partner or click [$1here] to make an existing contact a partner.'),
                    [1 => [
                            ['plugin' => 'Expenses', 'controller' => 'Partners', 'action' => 'pickContact' ],
                            ['id' => 'AddPartnerLink', 'tabIndex' => -1],
                    ]],
                ),
            ),

            ...$infoLines,

            // ── Main form body (entries fieldset) ──────────────────────────────────────────
            ...$entriesLines,
            'submit' => $isLocked
                ? '<div class="card-panel orange lighten-4"><i class="material-icons left">lock</i>'
                    . h(__d('expenses', 'These entries belong to a locked booking order and cannot be modified.'))
                    . '</div>'
                : ['method' => 'submit', 'parameters' => ['label' => __d('expenses', 'Save')]],
            'form_end' => ['method' => 'end'],
        ],
    ],
    /*'panels' => [
        'entity_info' => [
            'lines' => $infoLines,
        ],
        'edit_form' => $editFormHtml !== null ? [
            'pre' => '<h4>' . h(__d('expenses', 'Booking Entries')) . '</h4>',
            'html' => $editFormHtml,
        ] : null,
    ],*/
];

echo $this->Lil->form($pageView, 'Expenses.BookingOrders.links');
?>

<script type="text/javascript">
    $(function() {
        var BoUrl            = <?= json_encode(Router::url(['plugin' => 'Expenses', 'controller' => 'BookingOrders', 'action' => 'autocomplete', '_ext' => 'json'], true)) ?>;
        var NextPositionUrl  = <?= json_encode(Router::url(['plugin' => 'Expenses', 'controller' => 'BookingOrders', 'action' => 'nextPosition', '_ext' => 'json'], true)) ?>;
        var AccountsUrl      = <?= json_encode(Router::url(['plugin' => 'Expenses', 'controller' => 'Accounts', 'action' => 'autocomplete', '_ext' => 'json'], true)) ?>;
        var PartnersUrl      = <?= json_encode(Router::url(['plugin' => 'Expenses', 'controller' => 'Partners', 'action' => 'autocomplete', '_ext' => 'json'], true)) ?>;

        // When a booking order is selected and all current rows are unsaved (new entity being
        // linked for the first time), renumber them starting from the order's next available no.
        function updateNoForNewRows(bookingOrderId) {
            var $rows = $("#bookings-table tbody tr.booking-row");
            if ($rows.length === 0) { return; }
            var allNew = $rows.toArray().every(function(row) {
                return $(row).find(".id").val() === "";
            });
            if (!allNew) { return; }
            $.get(NextPositionUrl + "?bookingorderid=" + encodeURIComponent(bookingOrderId))
                .done(function(data) {
                    var nextNo = (data && data.next_no) ? data.next_no : 1;
                    $rows.each(function(idx) {
                        var pos = nextNo + idx;
                        $(this).find(".no-value").text(pos);
                        $(this).find(".no-hidden").val(pos);
                    });
                });
        }

        // ── Booking Order autocomplete ───────────────────────────────────────
        M.Autocomplete.init($("#bo-search").get(0), {
            allowUnsafeHTML: true,
            onSearch: function(text, ac) {
                $.get(BoUrl + "?term=" + encodeURIComponent(text)).done(function(data) {
                    if (data.length > 0) {
                        ac.setMenuItems(data.map(function(i) { return {id: i.id, text: i.text}; }));
                    }
                });
            },
            onAutocomplete: function(entries) {
                if (entries.length === 1) {
                    $("#bo-search").val(entries[0].text);
                    $("#bo-search").next("label").addClass("active");
                    $("#bo-id").val(entries[0].id);
                    updateNoForNewRows(entries[0].id);
                }
            },
        });

        // ── "Add new Booking Order" popup ────────────────────────────────────
        $("#AddBoLink").modalPopup({
            title: <?= json_encode(__d('expenses', 'New Booking Order')) ?>,
            processSubmit: true,
            onJson: function(data) {
                var json = (typeof data === "object") ? data : JSON.parse(data);
                $("#bo-id").val(json.id);
                $("#bo-search").val(json.value);
                $("#bo-search").next("label").addClass("active");
                updateNoForNewRows(json.id);
            },
        });

        // ── Top-level Partner autocomplete ───────────────────────────────────
        M.Autocomplete.init($("#partner-search-top").get(0), {
            allowUnsafeHTML: true,
            onSearch: function(text, ac) {
                $.get(PartnersUrl + "?term=" + encodeURIComponent(text)).done(function(data) {
                    if (data.length > 0) {
                        ac.setMenuItems(data.map(function(i) { return {id: i.id, text: i.text}; }));
                    }
                });
            },
            onAutocomplete: function(entries) {
                if (entries.length === 1) {
                    $("#partner-search-top").val(entries[0].text);
                    $("#partner-search-top").next("label").addClass("active");
                    $("#partner-id-top").val(entries[0].id);
                }
            },
        });

        // ── "Make contact a partner" popup ───────────────────────────────────
        $("#AddPartnerLink").modalPopup({
            title: <?= json_encode(__d('expenses', 'Select Contact')) ?>,
            processSubmit: true,
            onJson: function(data) {
                var json = (typeof data === "object") ? data : JSON.parse(data);
                $("#partner-id-top").val(json.id);
                $("#partner-search-top").val(json.value);
                $("#partner-search-top").next("label").addClass("active");
            },
        });

        // ── Per-row account autocomplete ─────────────────────────────────────
        function initAccountAutocomplete($input) {
            M.Autocomplete.init($input.get(0), {
                allowUnsafeHTML: true,
                onSearch: function(text, ac) {
                    $.get(AccountsUrl + "?term=" + encodeURIComponent(text)).done(function(data) {
                        if (data.length > 0) {
                            ac.setMenuItems(data.map(function(i) { return {id:i.id, text:i.value}; }));
                        }
                    });
                },
                onAutocomplete: function(entries) {
                    if (entries.length === 1) {
                        $input.val(entries[0].text);
                        $input.closest("td").find(".account-id").val(entries[0].id);
                    }
                },
            });
        }
        $("#bookings-table .account-search").each(function() { initAccountAutocomplete($(this)); });

        // Re-index the form field names (entries[0], entries[1], …) so PHP receives a
        // contiguous array. Does NOT touch the displayed no / no-hidden values – those
        // are managed separately so existing rows keep their assigned positions.
        function reindexRows() {
            $("#bookings-table tbody tr.booking-row").each(function(idx) {
                $(this).find("input[name]").each(function() {
                    var n = $(this).attr("name");
                    if (n) $(this).attr("name", n.replace(/entries\[\d+\]/, "entries[" + idx + "]"));
                });
            });
        }

        // Returns the highest no value currently visible in the table (0 when none set yet).
        function maxVisibleNo() {
            var max = 0;
            $("#bookings-table tbody tr.booking-row").each(function() {
                var v = parseInt($(this).find(".no-hidden").val(), 10);
                if (!isNaN(v) && v > max) { max = v; }
            });
            return max;
        }

        $("#add-booking-row").on("click", function(e) {
            e.preventDefault();
            var $newRow = $("#bookings-table tbody tr.booking-row").last().clone();
            $newRow.find(".account-search").val("");
            $newRow.find(".account-id").val("");
            $newRow.find(".id").val("");
            $newRow.find("input[type=number]").val("0.00");
            var nextNo = maxVisibleNo() + 1;
            $newRow.find(".no-value").text(nextNo > 1 ? nextNo : "–");
            $newRow.find(".no-hidden").val(nextNo > 1 ? nextNo : 0);
            $("#bookings-table tbody").append($newRow);
            reindexRows();
            initAccountAutocomplete($newRow.find(".account-search"));
        });

        $(document).on("click", ".remove-row", function(e) {
            e.preventDefault();
            var $rows = $("#bookings-table tbody tr.booking-row");
            if ($rows.length > 1) {
                $(this).closest("tr").remove();
            } else {
                var $row = $(this).closest("tr");
                $row.find(".account-search").val("");
                $row.find(".account-id").val("");
                $row.find("input[type=number]").val("0.00");
            }
            reindexRows();
        });
    });
</script>
