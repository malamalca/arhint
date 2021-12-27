<?php
declare(strict_types=1);

namespace Expenses\Event;

use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Expenses\Lib\ExpensesSidebar;

class ExpensesEvents implements EventListenerInterface
{
    /**
     * Setup event methods
     *
     * @access public
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'View.beforeRender' => 'addScripts',
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
            'Lil.Form.LilInvoices.InvoicesCounters.edit' => 'modifyCountersForm',
            'Lil.Panels.LilInvoices.Invoices.view' => 'modifyInvoicesView',
            'Model.afterSave' => 'createExpenseOnSave',
        ];
    }

    /**
     * Add styles and scripts to main layout.
     *
     * @param \Cake\Event\Event $event Event object
     * @param string $fileName Filename
     * @return void
     */
    public function addScripts($event, $fileName)
    {
        $view = $event->getSubject();
        $view->append('script');
        echo $view->Html->css('Expenses.expenses');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Expenses') {
            $view->set('admin_title', __d('expenses', 'Income and Expenses'));
        }
    }

    /**
     * Add actions to sidebar.
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $sidebar Sidebar object
     * @return void
     */
    public function modifySidebar($event, $sidebar)
    {
        $user = $event->getSubject()->getRequest()->getAttribute('identity');

        if ($user && $user->canUsePlugin('Expenses')) {
            ExpensesSidebar::setAdminSidebar($event, $sidebar);
        }
    }

    /**
     * Adds income/expense field to invoices counters form.
     *
     * @param \Cake\Event\Event $event Event object
     * @param \Lil\Lib\LilForm $form Form array
     * @return \Lil\Lib\LilForm
     */
    public function modifyCountersForm(Event $event, $form)
    {
        $view = $event->getSubject();
        $expenseFieldset = [
            'fs_expense_start' => '<fieldset>',
            'lg_expense' => sprintf('<legend>%s</legend>', __d('expenses', 'Accounting')),
            'expense_kind' => [
                'method' => 'control',
                'parameters' => ['expense', [
                    'label' => [
                        'text' => __d('expenses', 'Income/Expense') . ':',
                        'class' => 'active',
                    ],
                    'type' => 'select',
                    'options' => [
                        constant('Expenses_COUNTER_INCOME') => __d('expenses', 'Income'),
                        constant('Expenses_COUNTER_EXPENSE') => __d('expenses', 'Expense'),
                    ],
                    'empty' => __d('expenses', 'Neither expense nor income'),
                    'class' => 'browser-default',
                ]],
            ],
            'fs_expense_end' => '</fieldset>',
        ];

        $view->Lil->insertIntoArray($form->form['lines'], $expenseFieldset, ['after' => 'fs_basics_end']);

        return $form;
    }

    /**
     * Add payments table to Invoices View action
     *
     * @param \Cake\Event\Event $event Event object
     * @param \Lil\Lib\LilPanels $panels Panels array
     * @return \Lil\Lib\LilPanels
     */
    public function modifyInvoicesView(Event $event, $panels)
    {
        $view = $event->getSubject();

        $invoice = $panels->entity;

        if ($invoice->isInvoice()) {
            /** @var \LilInvoices\Model\Table\InvoicesCountersTable $InvoicesCounters */
            $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');
            $counter = $InvoicesCounters->get($invoice->counter_id);

            if (!is_null($counter->expense)) {
                $expenses = TableRegistry::getTableLocator()->get('Expenses.Expenses')->find()
                    ->where(['model' => 'Invoice', 'foreign_id' => $invoice->id])
                    ->contain(['Payments'])
                    ->all();

                /** @var \Expenses\Model\Table\PaymentsAccountsTable $PaymentsAccountsTable */
                $PaymentsAccountsTable = TableRegistry::getTableLocator()->get('Expenses.PaymentsAccounts');
                $accounts = $PaymentsAccountsTable->listForOwner($invoice->owner_id);

                $paymentsPanels = [
                    'payments_title' => '<h3>' . __d('expenses', 'Payments') . '</h3>',
                    'payments_table' => $view->element('Expenses.payments_list', [
                        'expense' => $expenses->first(),
                        'accounts' => $accounts,
                    ]),
                ];

                if ($expenses->count() > 1) {
                    $paymentsPanels['payments_warning'] = sprintf(
                        '<div class="error">%s</div>',
                        __d('expenses', 'WARNING: There are multiple expenses for this invoice!')
                    );
                }

                $view->Lil->insertIntoArray($panels->panels, $paymentsPanels);
            }
        }

        return $panels;
    }

    /**
     * Create Expense on InvoicesCounter "expense" field set to "expense" or "income"
     *
     * @param \Cake\Event\Event $event Event object
     * @param \LilInvoices\Model\Entity\Invoice $invoice Entity object
     * @param \ArrayObject $options Options array
     * @return void
     */
    public function createExpenseOnSave(Event $event, $invoice, ArrayObject $options)
    {
        if (get_class($event->getSubject()) == \LilInvoices\Model\Table\InvoicesTable::class) {
            $counter = $event->getSubject()->InvoicesCounters->get($invoice->counter_id);
            if (($counter->expense === 0) || ($counter->expense === 1)) {
                $Expenses = TableRegistry::getTableLocator()->get('Expenses.Expenses');

                if (!$invoice->isNew()) {
                    /** @var \Expenses\Model\Entity\Expense $expense */
                    $expense = $Expenses->find()
                        ->where(['foreign_id' => $invoice->id])
                        ->first();
                }

                if (empty($expense)) {
                    /** @var \Expenses\Model\Entity\Expense $expense */
                    $expense = $Expenses->newEmptyEntity();
                }

                $expense->owner_id = $invoice->owner_id;
                $expense->model = 'Invoice';
                $expense->foreign_id = $invoice->id;
                $expense->title = $invoice->title;
                $expense->dat_happened = $invoice->dat_issue;
                switch ((int)$counter->expense) {
                    case 0:
                        $expense->net_total = abs((float)$invoice->net_total);
                        $expense->total = abs((float)$invoice->total);
                        break;
                    case 1:
                        $expense->net_total = -abs((float)$invoice->net_total);
                        $expense->total = -abs((float)$invoice->total);
                        break;
                }

                $Expenses->save($expense);
            }
        }
    }
}
