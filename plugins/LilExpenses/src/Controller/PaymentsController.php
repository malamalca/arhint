<?php
declare(strict_types=1);

namespace LilExpenses\Controller;

use Cake\Http\Exception\NotFoundException;

/**
 * Payments Controller
 *
 * @property \LilExpenses\Model\Table\PaymentsTable $Payments
 */
class PaymentsController extends AppController
{
    /**
     * Index method
     *
     * @return null
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
                'contain' => ['PaymentsAccounts'],
                'conditions' => ['Payments.owner_id' => $ownerId],
            ], $this->Payments->filter($filter, $ownerId));

        $query = $this->Authorization->applyScope($this->Payments->find())
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->order($params['order']);

        $payments = $query->all();

        $minYear = $this->Payments->minYear($ownerId);
        $accounts = $this->Payments->PaymentsAccounts->listForOwner($ownerId, true);
        $this->set(compact('payments', 'accounts', 'filter', 'minYear'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Payment id.
     * @return null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $payment = $this->Payments->get($id, [
            'contain' => ['Accounts', 'Expenses'],
        ]);

        $this->Authorization->authorize($payment);

        $this->set('payment', $payment);

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
     * @param string|null $id Payment id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            $payment = $this->Payments->get($id, ['contain' => ['Expenses']]);
        } else {
            $payment = $this->Payments->newEmptyEntity();
            $payment->owner_id = $this->getCurrentUser()->get('company_id');
            $expenseId = $this->getRequest()->getQuery('expense');
            if (!empty($expenseId)) {
                /** @var \LilExpenses\Model\Entity\Expense $expense */
                $expense = $this->Payments->Expenses->get($expenseId);
                $payment->expenses = [$expense];
                $payment->dat_happened = $expense->dat_happened;
                $payment->descript = $expense->title;
            }
        }

        $this->Authorization->authorize($payment);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $payment->expenses = [];
            $payment = $this->Payments->patchEntity($payment, $this->getRequest()->getData(), [
                'associated' => ['Expenses' => ['onlyIds' => true]],
            ]);
            if ($this->Payments->save($payment)) {
                if ($this->getRequest()->is('ajax')) {
                    return $this->response->withType('application/json')->withStringBody(json_encode($payment));
                }

                $this->Flash->success(__d('lil_expenses', 'The payment has been saved.'));

                return $this->redirect($this->getRequest()->getData('referer') ?? ['action' => 'index']);
            } else {
                $this->Flash->error(__d('lil_expenses', 'The payment could not be saved. Please, try again.'));
            }
        }

        $accounts = $this->Payments->PaymentsAccounts->listForOwner($this->getCurrentUser()->get('company_id'), true);
        $this->set(compact('payment', 'accounts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Payment id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $payment = $this->Payments->get($id);

        $this->Authorization->authorize($payment);

        if ($this->Payments->delete($payment)) {
            $this->Flash->success(__d('lil_expenses', 'The payment has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_expenses', 'The payment could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->getRequest()->referer());
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
            $payments = $this->Authorization->applyScope($this->Payments->find(), 'index')
                ->select()
                ->where([
                    'Payments.owner_id' => $this->getCurrentUser()->get('company_id'),
                    'Payments.descript LIKE' => '%' . $term . '%',
                ])
                ->order('Payments.dat_happened DESC')
                ->limit(30)
                ->all();
            $this->set(compact('payments'));
        } else {
            throw new NotFoundException(__d('lil_expenses', 'Invalid ajax call.'));
        }

        return null;
    }
}
