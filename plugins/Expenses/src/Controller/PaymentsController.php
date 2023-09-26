<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Payments Controller
 *
 * @property \Expenses\Model\Table\PaymentsTable $Payments
 */
class PaymentsController extends AppController
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

        $params = $this->Payments->filter($filter, $ownerId);

        $query = $this->Authorization->applyScope($this->Payments->find())
            ->select(['id', 'account_id', 'dat_happened', 'amount', 'descript'])
            ->where($params['conditions'])
            ->order($params['order']);

        $payments = $this->paginate($query);

        $minYear = $this->Payments->minYear($ownerId);
        $accounts = $this->Payments->PaymentsAccounts->listForOwner($ownerId, true);
        $this->set(compact('payments', 'accounts', 'filter', 'minYear'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Payment id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $payment = $this->Payments->get($id, contain: ['Expenses']);
        } else {
            $payment = $this->Payments->newEmptyEntity();
            $payment->owner_id = $this->getCurrentUser()->get('company_id');
            $expenseId = $this->getRequest()->getQuery('expense');
            if (!empty($expenseId)) {
                /** @var \Expenses\Model\Entity\Expense $expense */
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
                    return $this->response->withType('application/json')->withStringBody((string)json_encode($payment));
                }

                $this->Flash->success(__d('expenses', 'The payment has been saved.'));

                return $this->redirect($this->getRequest()->getData('referer') ?? ['action' => 'index']);
            } else {
                $this->Flash->error(__d('expenses', 'The payment could not be saved. Please, try again.'));
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
    public function delete(?string $id = null): ?Response
    {
        $payment = $this->Payments->get($id);

        $this->Authorization->authorize($payment);

        if ($this->Payments->delete($payment)) {
            $this->Flash->success(__d('expenses', 'The payment has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The payment could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->getRequest()->referer() ?? ['action' => 'index']);
    }

    /**
     * autocomplete method
     *
     * @return void
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
            throw new NotFoundException(__d('expenses', 'Invalid ajax call.'));
        }
    }
}
