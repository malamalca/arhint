<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Accounts Controller – Chart of Accounts
 *
 * @property \Expenses\Model\Table\AccountsTable $Accounts
 */
class AccountsController extends AppController
{
    /**
     * Index method – displays accounts as a tree with optional search.
     *
     * @return void
     */
    public function index(): void
    {
        $filter = $this->getRequest()->getQueryParams();

        $params = array_merge_recursive([
            'contain' => [],
            'conditions' => [],
            'order' => ['Accounts.lft' => 'ASC'],
        ], $this->Accounts->filter($filter));

        $query = $this->Authorization->applyScope($this->Accounts->find())
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->order($params['order']);

        // When searching, return flat list; otherwise build threaded tree
        if (!empty($filter['search'])) {
            $accounts = $query->all()->toList();
            $treeMode = false;
        } else {
            $accounts = $query->all()->toList();
            $treeMode = true;
        }

        $this->set(compact('accounts', 'filter', 'treeMode'));
    }

    /**
     * Edit method – add or edit an account.
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $account = $this->Accounts->get((int)$id);
        } else {
            $account = $this->Accounts->newEmptyEntity();
        }

        $this->Authorization->authorize($account);

        $parentList = $this->Accounts->find('treeList', [
            'keyPath' => 'id',
            'valuePath' => 'name',
            'spacer' => '— ',
        ])->toArray();
        // Exclude self to prevent circular reference
        if ($id) {
            unset($parentList[(int)$id]);
        }
        // Add empty option
        $parentList = ['' => __d('expenses', '— None (root) —')] + $parentList;

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();
            if ($data['parent_id'] === '') {
                $data['parent_id'] = null;
            }
            $account = $this->Accounts->patchEntity($account, $data);
            if ($this->Accounts->save($account)) {
                $this->Flash->success(__d('expenses', 'The account has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('expenses', 'The account could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('account', 'parentList'));

        return null;
    }

    /**
     * Delete method.
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $account = $this->Accounts->get((int)$id);
        $this->Authorization->authorize($account);

        // Prevent deleting accounts that have children
        if ($this->Accounts->childCount($account, true) > 0) {
            $this->Flash->error(__d('expenses', 'Cannot delete an account that has child accounts.'));

            return $this->redirect(['action' => 'index']);
        }

        if ($this->Accounts->delete($account)) {
            $this->Flash->success(__d('expenses', 'The account has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The account could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Autocomplete method – search accounts by code or name.
     *
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When not an ajax call.
     */
    public function autocomplete(): void
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $term = $this->getRequest()->getQuery('term');
            if (is_string($term) && $term !== '') {
                $this->Authorization->skipAuthorization();
                $accounts = $this->Accounts->find()
                    ->where([
                        'Accounts.rght = Accounts.lft + 1',
                        'OR' => [
                            'Accounts.code LIKE' => '%' . $term . '%',
                            'Accounts.name LIKE' => '%' . $term . '%',
                        ],
                    ])
                    ->orderBy(['Accounts.code' => 'ASC'])
                    ->limit(20)
                    ->all();
                $this->set(compact('accounts'));
            } else {
                $this->Authorization->skipAuthorization();
            }
        } else {
            throw new NotFoundException(__d('expenses', 'Invalid ajax call.'));
        }
    }

    /**
     * Pick method – account picker for use in modal popups.
     *
     * @return void
     */
    public function pick(): void
    {
        $this->Authorization->skipAuthorization();

        $accounts = $this->Accounts->find()
            ->orderBy(['Accounts.lft' => 'ASC'])
            ->all()
            ->toList();

        $this->set(compact('accounts'));
    }
}
