<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Http\Response;

/**
 * PaymentsAccounts Controller
 *
 * @property \Expenses\Model\Table\PaymentsAccountsTable $PaymentsAccounts
 */
class PaymentsAccountsController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $filter = [];

        $params = array_merge_recursive([
                'contain' => [],
                'conditions' => [],
                'order' => 'PaymentsAccounts.title',
            ], $this->PaymentsAccounts->filter($filter));

        $accounts = $this->Authorization->applyScope($this->PaymentsAccounts->find())
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->all();

        $this->set(compact('accounts', 'filter'));
    }

    /**
     * View method
     *
     * @param string|null $id Payments Account id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $paymentsAccount = $this->PaymentsAccounts->get($id);
        $this->Authorization->authorize($paymentsAccount);

        $this->set('account', $paymentsAccount);
    }

    /**
     * Edit method
     *
     * @param string|null $id Payments Account id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $account = $this->PaymentsAccounts->get($id);
        } else {
            $account = $this->PaymentsAccounts->newEmptyEntity();
            $account->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($account);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $account = $this->PaymentsAccounts->patchEntity($account, $this->getRequest()->getData());
            if ($this->PaymentsAccounts->save($account)) {
                $this->Flash->success(__d('expenses', 'The payments account has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('expenses', 'The payments account could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('account'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Payments Account id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $paymentsAccount = $this->PaymentsAccounts->get($id);
        $this->Authorization->authorize($paymentsAccount);

        if ($this->PaymentsAccounts->delete($paymentsAccount)) {
            $this->Flash->success(__d('expenses', 'The payments account has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The payments account could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
