<?php
declare(strict_types=1);

namespace LilExpenses\Controller;

use App\Lib\ArhintReport;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use LilExpenses\Lib\LilExpensesImport;

/**
 * Expenses Controller
 *
 * @property \LilExpenses\Model\Table\ExpensesTable $Expenses
 * @property \LilExpenses\Model\Table\PaymentsExpensesTable $PaymentsExpenses
 *
 * @method \App\Model\Entity\User getCurrentUser()
 */
class ExpensesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $ownerId = $this->getCurrentUser()->get('company_id');
        $filter = $this->getRequest()->getQuery();
        if ($this->getRequest()->is('ajax')) {
            $filter['ajax'] = $this->getRequest()->getData();
        }
        if (!isset($filter['span'])) {
            $filter['span'] = 'month';
        }

        $params = array_merge_recursive([
            'contain' => ['Invoices'],
            'conditions' => [],
            'order' => 'Expenses.created DESC',
        ], $this->Expenses->filter($filter, $ownerId));

        $expenses = $this->Authorization->applyScope($this->Expenses->find())
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->order($params['order'])
            ->all();

        $minYear = $this->Expenses->minYear($ownerId);
        $this->set(compact('expenses', 'filter', 'minYear'));

        return null;
    }

    /**
     * autocomplete method
     *
     * @return \Cake\Http\Response|null
     */
    public function autocomplete()
    {
        $term = $this->getRequest()->getQuery('term');
        if ($this->getRequest()->is('ajax') && !empty($term)) {
            $expenses = $this->Authorization->applyScope($this->Expenses->find(), 'index')
                ->select()
                ->where([
                    'OR' => [
                        'Expenses.title LIKE' => '%' . $term . '%',
                        'Invoices.no LIKE' => $term . '%',
                    ],
                ])
                ->contain(['Invoices'])
                ->all();
            $this->set(compact('expenses'));
        } else {
            throw new NotFoundException(__d('lil_expenses', 'Invalid ajax call.'));
        }

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Expense id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $expense = $this->Expenses->get($id, ['contain' => ['Payments']]);
        $this->Authorization->authorize($expense);

        /** @var \LilExpenses\Model\Table\PaymentsAccountsTable $PaymentsAccounts */
        $PaymentsAccounts = TableRegistry::getTableLocator()->get('LilExpenses.PaymentsAccounts');
        $accounts = $PaymentsAccounts->listForOwner($this->getCurrentUser()->get('company_id'));

        $this->set(compact('expense', 'accounts'));

        return null;
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null
     */
    public function add()
    {
        $ret = $this->setAction('edit');

        return $ret;
    }

    /**
     * Edit method
     *
     * @param string|null $id Expense id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            $expense = $this->Expenses->get($id, [
                'contain' => ['Payments'],
            ]);
        } else {
            $expense = $this->Expenses->newEmptyEntity();
            $expense->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($expense);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $expense = $this->Expenses->patchEntity($expense, $this->getRequest()->getData());

            if ($this->Expenses->save($expense)) {
                if (!empty($this->getRequest()->getData('auto_payment'))) {
                    // auto create payment
                    $this->Expenses->Payments->save($this->Expenses->Payments->newEntity([
                        'owner_id' => $this->getCurrentUser()->get('company_id'),
                        'account_id' => $this->getRequest()->getData('auto_payment'),
                        'dat_happened' => $expense->dat_happened,
                        'descript' => $expense->title,
                        'amount' => $expense->total,
                        'sepa_id' => $this->getRequest()->getData('sepa_id'),
                        'expenses' => ['_ids' => [$expense->id]],
                    ]));
                }

                if ($this->getRequest()->is('ajax')) {
                    return $this->response->withType('application/json')->withStringBody(json_encode($expense));
                }

                $this->Flash->success(__d('lil_expenses', 'The expense has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('lil_expenses', 'The expense could not be saved. Please, try again.'));
            }
        }

        /** @var \LilExpenses\Model\Table\PaymentsAccountsTable $PaymentsAccounts */
        $PaymentsAccounts = TableRegistry::getTableLocator()->get('LilExpenses.PaymentsAccounts');
        $accounts = $PaymentsAccounts->listForOwner($this->getCurrentUser()->get('company_id'), true);
        $this->set(compact('expense', 'accounts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Expense id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $expense = $this->Expenses->get($id);
        $this->Authorization->authorize($expense);

        if ($this->Expenses->delete($expense)) {
            $this->Flash->success(__d('lil_expenses', 'The expense has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_expenses', 'The expense could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * ReportUnpaid method
     *
     * @return void Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function reportUnpaid()
    {
        $this->Authorization->skipAuthorization();

        $counters = [];
        if (Plugin::isLoaded('LilInvoices')) {
            $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');
            $counters = $this->Authorization->applyScope($InvoicesCounters->find(), 'index')
                ->where(['active' => true])
                ->order(['kind', 'title'])
                ->combine('id', function ($entity) {
                    return $entity;
                })
                ->toArray();
            $this->set(compact('counters'));
        }

        if ($this->getRequest()->is(['post'])) {
            $filter = $this->getRequest()->getData();

            $this->loadModel('LilExpenses.PaymentsExpenses');

            if (!empty($filter['counter'])) {
                $filter['counter'] = array_intersect_key((array)$filter['counter'], array_keys($counters));
            } else {
                $filter['counter'] = array_keys($counters);
            }

            $paymentsQuery = $this->PaymentsExpenses->find();
            $paymentsQuery
                ->select([
                    'payments_amount' => $paymentsQuery->func()->sum('Payments.amount'),
                ])
                ->contain('Payments')
                ->where(['PaymentsExpenses.expense_id' => $paymentsQuery->newExpr()->add('Expenses.id')])
                ->group('PaymentsExpenses.expense_id');

            $query = $this->Authorization->applyScope($this->Expenses->find(), 'index');
            $query
                ->select($this->Expenses)
                ->select([
                    'payments_total' => $paymentsQuery,
                ])
                ->select($this->Expenses->Invoices)
                ->select($this->Expenses->Invoices->Issuers)
                ->select($this->Expenses->Invoices->Receivers)
                ->contain(['Invoices' => ['Issuers', 'Receivers']])
                ->where([
                    'Expenses.model' => 'Invoice',
                    'Invoices.counter_id IN' => (array)$filter['counter'],
                ])
                ->order('Expenses.dat_happened DESC')
                ->having($query->newExpr()->add('ABS(Expenses.total - COALESCE(payments_total, 0)) > 0.01'));
            $data = $query->all();

            $report = new ArhintReport(
                'Expenses.unpaid',
                $this->request,
                ['title' => __d('lil_expenses', 'Unpaid Expenses')]
            );
            $report->set(compact('data', 'filter', 'counters'));

            $tmpName = $report->export();

            $this->redirect([
                'plugin' => false,
                'controller' => 'Pages',
                'action' => 'report',
                'Expenses.unpaid',
                substr($tmpName, 0, -4),
            ]);
        }
    }

    /**
     * Show yearly report
     *
     * @return \Cake\Http\Response|null
     */
    public function graphYearly()
    {
        $this->Authorization->skipAuthorization();

        if (!$this->getRequest()->is('ajax')) {
            $year = (int)$this->getRequest()->getQuery('year', '2020');
            $kind = $this->getRequest()->getQuery('kind', 'income');
            if (!in_array($kind, ['income', 'expenses'])) {
                $kind = 'income';
            }

            $options = ['cummulative' => true, 'kind' => $kind];

            $query = $this->Authorization->applyScope($this->Expenses->find(), 'index');
            $data1 = $this->Expenses->monthlyTotals($query, array_merge($options, ['year' => $year]));

            $query = $this->Authorization->applyScope($this->Expenses->find(), 'index');
            $data2 = $this->Expenses->monthlyTotals($query, array_merge($options, ['year' => $year - 1]));

            $query = $this->Authorization->applyScope($this->Expenses->find(), 'index');
            $data3 = $this->Expenses->monthlyTotals($query, array_merge($options, ['year' => $year - 2]));

            $this->set(compact('data1', 'data2', 'data3', 'year', 'kind'));
        }

        return null;
    }

    /**
     * Import Sepa XML payments and offer import actions
     *
     * @return \Cake\Http\Response|null
     */
    public function importSepa()
    {
        $this->Authorization->skipAuthorization();

        $Importer = new LilExpensesImport($this->getCurrentUser()->get('company_id'));

        $this->viewBuilder()->setTemplate('import_sepa_step1');

        if ($this->getRequest()->is('post')) {
            $sepafile = $this->getRequest()->getData('sepafile');
            if (!empty($sepafile)) {
                $tmpFile = $sepafile['tmp_name'];
                $realFilename = $sepafile['name'];
                $Importer->addFromFile($tmpFile, pathinfo($realFilename, PATHINFO_EXTENSION));

                return $this->redirect(['action' => 'importSepa']);
            }
        } elseif ($this->getRequest()->getQuery('clearcache') !== null) {
            $Importer->clear();
        } elseif (!empty($Importer->getPayments())) {
            // TODO: check for count of payments
            $payments = $Importer->getPayments();
            $filter = $this->getRequest()->getQuery('filter');
            $this->set(compact('filter'));
            $this->set('importedPayments', $payments);
            $this->viewBuilder()->setTemplate('import_sepa_step2');
        }

        return null;
    }

    /**
     * Links sepa payment to an existing payment
     *
     * @return \Cake\Http\Response|null
     */
    public function importSepaLink()
    {
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $payment = $this->Expenses->Payments->get($this->getRequest()->getData('payment_id'));
            $this->Authorization->authorize($payment, 'edit');

            if (!empty($payment)) {
                $payment->sepa_id = $this->getRequest()->getData('sepa_id');

                if ($this->Expenses->Payments->save($payment)) {
                    if ($this->getRequest()->is('ajax')) {
                        return $this->getResponse()
                            ->withType('application/json')
                            ->withStringBody(json_encode($payment));
                    }

                    $this->Flash->success(__d('lil_expenses', 'The payment has been saved.'));

                    return $this->redirect(['action' => 'import_sepa']);
                } else {
                    $this->Flash->error(__d('lil_expenses', 'The expense could not be saved. Please, try again.'));
                }
            }
        } else {
            $this->Authorization->skipAuthorization();
        }

        return null;
    }
}
