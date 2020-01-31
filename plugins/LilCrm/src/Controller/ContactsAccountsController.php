<?php
declare(strict_types=1);

namespace LilCrm\Controller;

/**
 * ContactsAccounts Controller
 *
 * @property \LilCrm\Model\Table\ContactsAccountsTable $ContactsAccounts
 */
class ContactsAccountsController extends AppController
{
    /**
     * Add method
     *
     * @return void
     */
    public function add()
    {
        $this->setAction('edit');
    }

    /**
     * Edit method
     *
     * @param  string|null $id Contacts Account id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            /** @var \LilCrm\Model\Entity\ContactsAccount $account */
            $account = $this->ContactsAccounts->get($id);
        } else {
            /** @var \LilCrm\Model\Entity\ContactsAccount $account */
            $account = $this->ContactsAccounts->newEmptyEntity();
            $account->contact_id = $this->getRequest()->getParam('pass.0');
        }

        $this->Authorization->authorize($account);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $this->ContactsAccounts->patchEntity($account, $this->getRequest()->getData());
            if (!$account->getErrors() && $this->ContactsAccounts->save($account)) {
                /** @var \LilCrm\Model\Entity\Contact $contact */
                $contact = $this->ContactsAccounts->Contacts->get($account->contact_id);
                $this->ContactsAccounts->Contacts->touch($contact);
                $this->ContactsAccounts->Contacts->save($contact);

                $this->Flash->success(__d('lil_crm', 'The contacts\' account has been saved.'));

                return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $account->contact_id]);
            } else {
                $this->Flash->error(__d('lil_crm', 'The contacts\' account could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('account'));
    }

    /**
     * Delete method
     *
     * @param  string|null $id Contacts Account id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        /** @var \LilCrm\Model\Entity\ContactsAccount $account */
        $account = $this->ContactsAccounts->get($id);
        $this->Authorization->authorize($account);

        if ($this->ContactsAccounts->delete($account)) {
            $this->Flash->success(__d('lil_crm', 'The contacts account has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_crm', 'The contacts account could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $account->contact_id]);
    }
}
