<?php
declare(strict_types=1);

namespace Crm\Controller;

/**
 * ContactsEmails Controller
 *
 * @property \Crm\Model\Table\ContactsEmailsTable $ContactsEmails
 */
class ContactsEmailsController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Contacts Email id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if ($id) {
            /** @var \Crm\Model\Entity\ContactsEmail $email */
            $email = $this->ContactsEmails->get($id);
        } else {
            /** @var \Crm\Model\Entity\ContactsEmail $email */
            $email = $this->ContactsEmails->newEmptyEntity();
            $email->contact_id = $this->getRequest()->getQuery('contact');
        }

        $this->Authorization->authorize($email);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $this->ContactsEmails->patchEntity($email, $this->getRequest()->getData());
            if (!$email->getErrors() && $this->ContactsEmails->save($email)) {
                /** @var \Crm\Model\Entity\Contact $contact */
                $contact = $this->ContactsEmails->Contacts->get($email->contact_id);
                $this->ContactsEmails->Contacts->touch($contact);
                $this->ContactsEmails->Contacts->save($contact);

                $this->Flash->success(__d('crm', 'The contacts email has been saved.'));

                return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $email->contact_id]);
            } else {
                $this->Flash->error(__d('crm', 'The contacts email could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('email'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Contacts Email id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        /** @var \Crm\Model\Entity\ContactsEmail $email */
        $email = $this->ContactsEmails->get($id);
        $this->Authorization->authorize($email);

        if ($this->ContactsEmails->delete($email)) {
            $this->Flash->success(__d('crm', 'The contacts email has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The contacts email could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $email->contact_id]);
    }
}
