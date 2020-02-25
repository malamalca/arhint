<?php
declare(strict_types=1);

namespace LilExpenses\Event;

use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use LilExpenses\Lib\LilExpensesSidebar;

class LilExpensesEvents implements EventListenerInterface
{
    /**
     * Setup event methods
     *
     * @access public
     * @return array
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
        echo $view->Html->css('LilExpenses.lil_expenses');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'LilExpenses') {
            $view->set('admin_title', __d('lil_expenses', 'Income and Expenses'));
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
        LilExpensesSidebar::setAdminSidebar($event, $sidebar);
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
            'lg_expense' => sprintf('<legend>%s</legend>', __d('lil_expenses', 'Accounting')),
            'expense_kind' => [
                'method' => 'control',
                'parameters' => ['expense', [
                    'label' => __d('lil_expenses', 'Income/Expense') . ':',
                    'type' => 'select',
                    'options' => [
                        constant('LILEXPENSES_COUNTER_INCOME') => __d('lil_expenses', 'Income'),
                        constant('LILEXPENSES_COUNTER_EXPENSE') => __d('lil_expenses', 'Expense'),
                    ],
                    'empty' => __d('lil_expenses', 'Neither expense nor income'),
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
        $view->loadHelper('LilInvoices.LilDocument');

        if ($view->LilDocument->isInvoice($invoice)) {
            /** @var \LilInvoices\Model\Table\InvoicesCountersTable $InvoicesCounters */
            $InvoicesCounters = TableRegistry::get('LilInvoices.InvoicesCounters');
            $counter = $InvoicesCounters->get($invoice->counter_id);

            if (!is_null($counter->expense)) {
                $expenses = TableRegistry::get('LilExpenses.Expenses')->find()
                    ->where(['model' => 'Invoice', 'foreign_id' => $invoice->id])
                    ->contain(['Payments'])
                    ->all();

                /** @var \LilExpenses\Model\Table\PaymentsAccountsTable $PaymentsAccountsTable */
                $PaymentsAccountsTable = TableRegistry::get('LilExpenses.PaymentsAccounts');
                $accounts = $PaymentsAccountsTable->listForOwner($invoice->owner_id);

                $paymentsPanels = [
                    'payments_title' => '<h3>' . __d('lil_expenses', 'Payments') . '</h3>',
                    'payments_table' => $view->element('LilExpenses.payments_list', [
                        'expense' => $expenses->first(),
                        'accounts' => $accounts,
                    ]),
                ];

                if ($expenses->count() > 1) {
                    $paymentsPanels['payments_warning'] = sprintf(
                        '<div class="error">%s</div>',
                        __d('lil_expenses', 'WARNING: There are multiple expenses for this invoice!')
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
        if (get_class($event->getSubject()) == 'LilInvoices\Model\Table\InvoicesTable') {
            $counter = $event->getSubject()->InvoicesCounters->get($invoice->counter_id);
            if (($counter->expense === 0) || ($counter->expense === 1)) {
                $Expenses = TableRegistry::get('LilExpenses.Expenses');

                if (!$invoice->isNew()) {
                    /** @var \LilExpenses\Model\Entity\Expense $expense */
                    $expense = $Expenses->find()
                        ->where(['foreign_id' => $invoice->id])
                        ->first();
                }

                if (empty($expense)) {
                    /** @var \LilExpenses\Model\Entity\Expense $expense */
                    $expense = $Expenses->newEmptyEntity();
                }

                $expense->owner_id = $invoice->owner_id;
                $expense->model = 'Invoice';
                $expense->foreign_id = $invoice->id;
                $expense->title = $invoice->title;
                $expense->dat_happened = $invoice->dat_issue;
                switch ((int)$counter->expense) {
                    case 0:
                        $expense->net_total = abs($invoice->net_total);
                        $expense->total = abs($invoice->total);
                        break;
                    case 1:
                        $expense->net_total = -abs($invoice->net_total);
                        $expense->total = -abs($invoice->total);
                        break;
                }

                $Expenses->save($expense);
            }
        }
    }
}
