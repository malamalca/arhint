<?php
declare(strict_types=1);

namespace Expenses\Controller;

use App\Lib\ArhintReport;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Expenses\Lib\ExpensesImport;

/**
 * Expenses Controller
 *
 * @property \Expenses\Model\Table\ExpensesTable $Expenses
 * @property \Expenses\Model\Table\PaymentsExpensesTable $PaymentsExpenses
 * @method \App\Model\Entity\User getCurrentUser()
 */
class ExpensesController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $ownerId = $this->getCurrentUser()->get('company_id');

        /** @var array<string, mixed> $filter */
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
    }

    /**
     * autocomplete method
     *
     * @return void
     */
    public function autocomplete()
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $term = $this->getRequest()->getQuery('term');
            if (is_string($term) && $term != '') {
                $expenses = $this->Authorization->applyScope($this->Expenses->find(), 'index')
                    ->select()
                    ->where([
                        'OR' => [
                            'Expenses.title LIKE' => '%' . $term . '%',
                            'Invoices.no LIKE' => $term . '%',
                        ],
                    ])
                    ->order(['Expenses.dat_happened DESC'])
                    ->limit(20)
                    ->contain(['Invoices'])
                    ->all();
                $this->set(compact('expenses'));

                $this->response = $this->response->withType('application/json');
            } else {
                $this->Authorization->skipAuthorization();
            }
        } else {
            throw new NotFoundException(__d('expenses', 'Invalid ajax call.'));
        }
    }

    /**
     * View method
     *
     * @param string|null $id Expense id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $expense = $this->Expenses->get($id, contain: ['Payments']);
        $this->Authorization->authorize($expense);

        /** @var \Expenses\Model\Table\PaymentsAccountsTable $PaymentsAccounts */
        $PaymentsAccounts = TableRegistry::getTableLocator()->get('Expenses.PaymentsAccounts');
        $accounts = $PaymentsAccounts->listForOwner($this->getCurrentUser()->get('company_id'));

        $this->set(compact('expense', 'accounts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Expense id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if ($id) {
            $expense = $this->Expenses->get($id, contain: ['Payments']);
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
                    $PaymentsTable = TableRegistry::getTableLocator()->get('Expenses.Payments');

                    $payment = $PaymentsTable->newEntity([
                        'owner_id' => $this->getCurrentUser()->get('company_id'),
                        'account_id' => $this->getRequest()->getData('auto_payment'),
                        'dat_happened' => $expense->dat_happened,
                        'descript' => $expense->title,
                        'amount' => $expense->total,
                        'sepa_id' => $this->getRequest()->getData('sepa_id'),
                        'expenses' => ['_ids' => [$expense->id]],
                    ]);

                    $PaymentsTable->save($payment);
                }

                if ($this->getRequest()->is('ajax')) {
                    return $this->response->withType('application/json')->withStringBody((string)json_encode($expense));
                }

                //$this->Flash->success(__d('expenses', 'The expense has been saved.'));
                //return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('expenses', 'The expense could not be saved. Please, try again.'));
            }
        }

        /** @var \Expenses\Model\Table\PaymentsAccountsTable $PaymentsAccounts */
        $PaymentsAccounts = TableRegistry::getTableLocator()->get('Expenses.PaymentsAccounts');
        $accounts = $PaymentsAccounts->listForOwner($this->getCurrentUser()->get('company_id'), true);
        $this->set(compact('expense', 'accounts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Expense id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $expense = $this->Expenses->get($id);
        $this->Authorization->authorize($expense);

        if ($this->Expenses->delete($expense)) {
            $this->Flash->success(__d('expenses', 'The expense has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The expense could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * ReportUnpaid method
     *
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function reportUnpaid(): void
    {
        $this->Authorization->skipAuthorization();

        $counters = [];
        if (Plugin::isLoaded('Documents')) {
            $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
            $counters = $this->Authorization->applyScope($DocumentsCounters->find(), 'index')
                ->where(['active' => true, 'kind' => 'invoices'])
                ->order(['kind', 'title'])
                ->all()
                ->combine('id', function ($entity) {
                    return $entity;
                })
                ->toArray();
            $this->set(compact('counters'));
        }

        if ($this->getRequest()->is(['post'])) {
            $filter = $this->getRequest()->getData();

            $PaymentsExpensesTable = $this->fetchTable('Expenses.PaymentsExpenses');

            if (!empty($filter['counter'])) {
                $filter['counter'] = array_intersect_key((array)$filter['counter'], array_keys($counters));
            } else {
                $filter['counter'] = array_keys($counters);
            }

            $paymentsQuery = $PaymentsExpensesTable->find();
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
                ['title' => __d('expenses', 'Unpaid Expenses')]
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
     * @return void
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
    }

    /**
     * Show expenses for specified year
     *
     * @return void
     */
    public function graphExpenses()
    {
        $this->Authorization->skipAuthorization();

        if (!$this->getRequest()->is('ajax')) {
            $year = (int)$this->getRequest()->getQuery('year', '2020');
            $options = ['cummulative' => true];

            $query = $this->Authorization->applyScope($this->Expenses->find(), 'index');
            $data1 = $this->Expenses->monthlyTotals(
                $query,
                array_merge($options, ['kind' => 'expenses', 'year' => $year])
            );

            $query = $this->Authorization->applyScope($this->Expenses->find(), 'index');
            $data2 = $this->Expenses->monthlyTotals(
                $query,
                array_merge($options, ['kind' => 'income', 'year' => $year])
            );

            $this->set(compact('data1', 'data2', 'year'));
        }
    }

    /**
     * Import Sepa XML payments and offer import actions
     *
     * @return \Cake\Http\Response|null
     */
    public function importSepa(): ?Response
    {
        $this->Authorization->skipAuthorization();

        $Importer = new ExpensesImport($this->getCurrentUser()->get('company_id'));

        $this->viewBuilder()->setTemplate('import_sepa_step1');

        if ($this->getRequest()->is('post')) {
            $sepafile = $this->getRequest()->getData('sepafile');
            if (!empty($sepafile) && !$sepafile->getError()) {
                $tmpFile = $sepafile->getStream()->getMetadata('uri');
                $realFilename = $sepafile->getClientFilename();
                $Importer->addFromFile($tmpFile, pathinfo($realFilename, PATHINFO_EXTENSION));

                return $this->redirect(['action' => 'importSepa']);
            }
        } elseif ($this->getRequest()->getQuery('clearcache') !== null) {
            $Importer->clear();
        } elseif (!empty($Importer->getPayments())) {
            // TODO: check for count of payments
            $payments = $Importer->getPayments();
            usort($payments, fn ($payment1, $payment2) => $payment1['date'] <=> $payment2['date']);

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
    public function importSepaLink(): ?Response
    {
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $payment = $this->Expenses->Payments->get($this->getRequest()->getData('payment_id'));
            $this->Authorization->authorize($payment, 'edit');

            $payment->sepa_id = $this->getRequest()->getData('sepa_id');

            if ($this->Expenses->Payments->save($payment)) {
                if ($this->getRequest()->is('ajax')) {
                    return $this->getResponse()
                        ->withType('application/json')
                        ->withStringBody((string)json_encode($payment));
                }

                $this->Flash->success(__d('expenses', 'The payment has been saved.'));

                return $this->redirect(['action' => 'import_sepa']);
            } else {
                $this->Flash->error(__d('expenses', 'The expense could not be saved. Please, try again.'));
            }
        } else {
            $this->Authorization->skipAuthorization();
        }

        return null;
    }
}
