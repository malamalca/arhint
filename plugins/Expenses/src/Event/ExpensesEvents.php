<?php
declare(strict_types=1);

namespace Expenses\Event;

use App\Lib\LilForm;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Documents\Model\Table\InvoicesTable;
use Documents\Model\Table\TravelOrdersTable;
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
            'App.Sidebar.beforeRender' => 'modifySidebar',
            'App.Form.Documents.DocumentsCounters.edit' => 'modifyCountersForm',
            'App.Panels.Documents.Invoices.view' => 'modifyDocumentsView',
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
    public function addScripts(Event $event, string $fileName): void
    {
        /** @var \App\View\AppView $view */
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
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();

        $user = $view->getRequest()->getAttribute('identity');

        if ($user && $user->canUsePlugin('Expenses')) {
            ExpensesSidebar::setAdminSidebar($event, $sidebar);
        }
    }

    /**
     * Adds income/expense field to documents counters form.
     *
     * @param \Cake\Event\Event $event Event object
     * @param \App\Lib\LilForm $form Form array
     * @return \App\Lib\LilForm
     */
    public function modifyCountersForm(Event $event, LilForm $form): LilForm
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();
        $expenseFieldset = [
            'fs_expense_start' => '<fieldset>',
            'lg_expense' => sprintf('<legend>%s</legend>', __d('expenses', 'Accounting')),
            'expense_kind' => [
                'method' => 'control',
                'parameters' => ['expense', [
                    'label' => __d('expenses', 'Income/Expense') . ':',
                    'type' => 'select',
                    'options' => [
                        constant('EXPENSES_COUNTER_INCOME') => __d('expenses', 'Income'),
                        constant('EXPENSES_COUNTER_EXPENSE') => __d('expenses', 'Expense'),
                    ],
                    'empty' => __d('expenses', 'Neither expense nor income'),
                ]],
            ],
            'fs_expense_end' => '</fieldset>',
        ];

        $form->form['lines'] = $form->form['lines'] ?? [];
        $view->Lil->insertIntoArray($form->form['lines'], $expenseFieldset, ['after' => 'fs_basics_end']);

        return $form;
    }

    /**
     * Add payments table to Documents View action
     *
     * @param \Cake\Event\Event $event Event object
     * @param mixed $panels Panels array
     * @return void
     */
    public function modifyDocumentsView(Event $event, mixed $panels): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();

        $invoice = $panels->entity;

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counter = $DocumentsCounters->get($invoice->counter_id);

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
                    __d('expenses', 'WARNING: There are multiple expenses for this document!'),
                );
            }

            $view->Lil->insertIntoArray($panels->panels, $paymentsPanels);
        }

        //return $panels;
        $event->setResult($panels);
    }

    /**
     * Create Expense on DocumentsCounter "expense" field set to "expense" or "income"
     *
     * @param \Cake\Event\Event $event Event object
     * @param \Cake\Datasource\EntityInterface $entity Entity object
     * @param \ArrayObject $options Options array
     * @return void
     */
    public function createExpenseOnSave(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        switch (get_class($event->getSubject())) {
            case InvoicesTable::class:
                $modelName = 'Invoice';
                break;
            case TravelOrdersTable::class:
                $modelName = 'TravelOrder';
                break;
            default:
                return;
        }

        if (get_class($event->getSubject()) == InvoicesTable::class) {
            /** @var \Documents\Model\Entity\Invoice $entity */
            $counter = $event->getSubject()->DocumentsCounters->get($entity->counter_id);

            if (($counter->expense === 0) || ($counter->expense === 1)) {
                /** @var \Expenses\Model\Table\ExpensesTable $Expenses */
                $Expenses = TableRegistry::getTableLocator()->get('Expenses.Expenses');

                if (!$entity->isNew()) {
                    /** @var \Expenses\Model\Entity\Expense $expense */
                    $expense = $Expenses->find()
                        ->where(['foreign_id' => $entity->id])
                        ->first();
                }

                if (empty($expense)) {
                    /** @var \Expenses\Model\Entity\Expense $expense */
                    $expense = $Expenses->newEmptyEntity();
                }

                $expense->owner_id = $entity->owner_id;
                $expense->model = $modelName;
                $expense->foreign_id = $entity->id;
                $expense->title = $entity->title;
                $expense->dat_happened = $entity->dat_issue;
                switch ((int)$counter->expense) {
                    case 0:
                        $expense->net_total = abs((float)$entity->net_total);
                        $expense->total = abs((float)$entity->total);
                        break;
                    case 1:
                        $expense->net_total = -abs((float)$entity->net_total);
                        $expense->total = -abs((float)$entity->total);
                        break;
                }

                $Expenses->save($expense);
            }
        }
    }
}
