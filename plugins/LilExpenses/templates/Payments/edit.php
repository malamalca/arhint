<?php
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;

$payment_edit = [
    'title_for_layout' => $payment->id ? __d('lil_expenses', 'Edit Payment') : __d('lil_expenses', 'Add Payment'),
    'menu' => [
        'delete' => [
            'title' => __d('lil_expenses', 'Delete'),
            'visible' => $payment->id,
            'url' => [
                'plugin' => 'LilExpenses',
                'controller' => 'Payments',
                'action' => 'delete',
                $payment->id,
            ],
            'params' => [
                'confirm' => __d('lil_expenses', 'Are you sure you want to delete this payment?'),
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
                'parameters' => [$payment],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'sepa_id' => [
                'method' => 'hidden',
                'parameters' => ['sepa_id', [
                    'default' => $this->getRequest()->getQuery('sepa_id'),
                ]],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', [
                    'default' => $this->getRequest()->referer(),
                ]],
            ],
            'fs_basics_start' => '<fieldset>',
            'lg_basics' => sprintf('<legend>%s</legend>', __d('lil_expenses', 'Basics')),

            'dat_happened' => [
                'method' => 'control',
                'parameters' => ['dat_happened', [
                    'type' => 'date',
                    'label' => __d('lil_expenses', 'Date') . ':',
                    'error' => __d('lil_expenses', 'Date is required.'),
                    'default' => $this->getRequest()->getQuery('date') ?: new FrozenTime(),
                ]],
            ],
            'amount' => [
                'method' => 'control',
                'parameters' => ['amount', [
                    'type' => 'number',
                    'step' => '0.01',
                    'label' => __d('lil_expenses', 'Amount') . ':',
                    'default' => $this->getRequest()->getQuery('amount') ? : null,
                ]],
            ],
            'account_id' => [
                'method' => 'control',
                'parameters' => ['account_id', [
                    'type' => 'select',
                    'label' => [
                        'text' => __d('lil_expenses', 'From/To Account') . ':',
                        'class' => 'active',
                    ],
                    'options' => $accounts,
                    'class' => 'browser-default',
                ]],
            ],
            'descript' => [
                'method' => 'control',
                'parameters' => ['descript', [
                    'type' => 'text',
                    'label' => __d('lil_expenses', 'Description') . ':',
                    'default' => $this->getRequest()->getQuery('descript'),
                ]],
            ],

            'fs_basics_end' => '</fieldset>',

            'fs_expenses_start' => '<fieldset>',
            'lg_expenses' => sprintf('<legend>%s</legend>', __d('lil_expenses', 'Links to Expenses')),
            'add_expense_table_start' => '<table id="add-expense-table"><tbody>',
            'add_expense_table_end' => '</tbody></table>',

            'add_expense_start' => '<div id="add-expense-form">',
            'add_expense_input' => [
                'method' => 'control',
                'parameters' => ['expense_descript', ['type' => 'text', 'id' => 'expense-descript']],
            ],
            'add_expense_cancel' => sprintf(
                ' <a href="javascript:void(0);" onclick="toggleAddExpense();">%s</a>',
                __d('lil_expenses', 'Cancel')
            ),
            'add_expense_end' => '</div>',

            'add_expense_link' => sprintf(
                '<div id="add-expense-link">' .
                '<a href="javascript:void(0);" id="add-expense-link" onclick="toggleAddExpense();">%s</a>' .
                '</div>',
                __d('lil_expenses', 'Add Expense')
            ),
            'fs_expenses_end' => '</fieldset>',

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_expenses', 'Save'),
                ],
            ],
            'unlock_ids' => [
                'method' => 'unlockField',
                'parameters' => ['expenses._ids'],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

// show existing expenses
if (!empty($payment->expenses)) {
    $existing_expenses = [];
    foreach ($payment->expenses as $i => $expense) {
        $existing_expenses['exp_' . $expense->id] = sprintf(
            '<tr>' .
            '<td class="expense_icon">%1$s</td>' .
            '<td class="expense_id">%2$s</td>' .
            '<td class="expense_title"><span>%3$s</span> %4$s</td>' .
            '</tr>',
            $this->LilExpense->icon($expense),
            $this->Form->control('expenses._ids.' . ($i + 1), [
                'type' => 'hidden',
                'value' => $expense->id,
            ]),
            $this->LilExpense->title($expense),
            $this->Html->link(
                $this->Html->image('/lil_expenses/img/remove.gif'),
                [
                    'action' => 'detach',
                    'payment' => $payment->id,
                    'expense' => $expense->id,
                ],
                ['escape' => false, 'onclick' => 'return removeExpenseRow(this);']
            )
        );
    }
    $this->Lil->insertIntoArray(
        $payment_edit['form']['lines'],
        $existing_expenses,
        ['before' => 'add_expense_table_end']
    );
}

echo $this->Lil->form($payment_edit, 'LilExpenses.Payments.edit');
?>

<script type="text/javascript">
    function toggleAddExpense() {
        if ($("#add-expense-link:visible").size() == 0) {
            $("#add-expense-link").show();
            $("#add-expense-form").hide();
        } else {
            $("#add-expense-link").hide();
            $("#add-expense-form").show();
            $("#add-expense-form>#expense-descript").val('').focus();
        }
        return false;
    }

    function addExpenseRow(expense) {
        var expenseExists = false;
        $('#add-expense-table > tbody:last > tr').each(function() {
            if ($('td.expense_id>input', this).val() == expense.id) {
                alert("<?php echo __d('lil_expenses', 'Expense already exists.'); ?>");
                expenseExists = true;
                return false;
            }
        });
        if (!expenseExists) {
            var row = $("<tr>");
            $('#add-expense-table > tbody:last').append(row);

            var tdIcon = $("<td>", {"class": "expense_icon"}).html(expense.icon).appendTo(row);
            var tdId = $("<td>", {"class": "expense_id", text: ""}).appendTo(row);
                var inputIdName = "expenses[_ids]["+ $('#add-expense-table > tbody:last > tr').length +"]";
                var inputId = $("<input>", {"type": "hidden", "value": expense.id, "name": inputIdName})
                    .appendTo(tdId);
            var tdTitle = $("<td>", {"class": "expense_title"})
                .html('<span>' + expense.value + '</span>').appendTo(row);

            var imgRemove = $("<a>")
                .html('<?= $this->Html->image('/lil_expenses/img/remove.gif'); ?>')
                .click(function() { return removeExpenseRow(this); })
                .appendTo(tdTitle);
        }
        toggleAddExpense();
    }

    function removeExpenseRow(link) {
        $(link).closest('tr').remove();
        return false;
    }

    $(document).ready(function() {

        $("#expense-descript").autocompleteajax({
            source: "<?= Router::url(['controller' => 'Expenses', 'action' => 'autocomplete'], true); ?>",
            limit: 20,
            onSelect: function(item) {
                $("#expense-descript").val("");
                addExpenseRow(item)
            }
        });

        if ($("#add-expense-table tr").length > 0) {
            $("#add-expense-form").hide();
        } else {
            $("#add-expense-link").hide();
        }
    });

</script>
