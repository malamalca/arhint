<?php
declare(strict_types=1);

namespace Crm\Controller;

/**
 * ContactsAccounts Controller
 *
 * @property \Crm\Model\Table\ContactsAccountsTable $ContactsAccounts
 */
class ContactsAccountsController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Contacts Account id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if ($id) {
            /** @var \Crm\Model\Entity\ContactsAccount $account */
            $account = $this->ContactsAccounts->get($id);
        } else {
            /** @var \Crm\Model\Entity\ContactsAccount $account */
            $account = $this->ContactsAccounts->newEmptyEntity();
            $account->contact_id = $this->getRequest()->getQuery('contact');
        }

        $this->Authorization->authorize($account);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $this->ContactsAccounts->patchEntity($account, $this->getRequest()->getData());
            if (!$account->getErrors() && $this->ContactsAccounts->save($account)) {
                /** @var \Crm\Model\Entity\Contact $contact */
                $contact = $this->ContactsAccounts->Contacts->get($account->contact_id);
                $this->ContactsAccounts->Contacts->touch($contact);
                $this->ContactsAccounts->Contacts->save($contact);

                if ($this->getRequest()->is('ajax')) {
                    $response = $this->getResponse()
                        ->withType('application/json')
                        ->withStringBody((string)json_encode(['account' => $account]));

                    return $response;
                } else {
                    $this->Flash->success(__d('crm', 'The contacts\' account has been saved.'));

                    return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $account->contact_id]);
                }
            } else {
                $this->Flash->error(__d('crm', 'The contacts\' account could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('account'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Contacts Account id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        /** @var \Crm\Model\Entity\ContactsAccount $account */
        $account = $this->ContactsAccounts->get($id);
        $this->Authorization->authorize($account);

        if ($this->ContactsAccounts->delete($account)) {
            $this->Flash->success(__d('crm', 'The contacts account has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The contacts account could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $account->contact_id]);
    }
}
