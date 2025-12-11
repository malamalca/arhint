<?php
declare(strict_types=1);

namespace Crm\Controller;

/**
 * ContactsPhones Controller
 *
 * @property \Crm\Model\Table\ContactsPhonesTable $ContactsPhones
 */
class ContactsPhonesController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Contacts Phone id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if (!empty($id)) {
            /** @var \Crm\Model\Entity\ContactsPhone $phone */
            $phone = $this->ContactsPhones->get($id);
        } else {
            /** @var \Crm\Model\Entity\ContactsPhone $phone */
            $phone = $this->ContactsPhones->newEmptyEntity();
            $phone->contact_id = $this->getRequest()->getQuery('contact');
        }
        $this->Authorization->authorize($phone);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $this->ContactsPhones->patchEntity($phone, $this->getRequest()->getData());
            if (!$phone->getErrors() && $this->ContactsPhones->save($phone)) {
                /** @var \Crm\Model\Entity\Contact $contact */
                $contact = $this->ContactsPhones->Contacts->get($phone->contact_id);
                $this->ContactsPhones->Contacts->touch($contact);
                $this->ContactsPhones->Contacts->save($contact);

                if ($this->getRequest()->is('ajax')) {
                    $response = $this->getResponse()
                        ->withType('application/json')
                        ->withStringBody((string)json_encode(['phone' => $phone]));

                    return $response;
                } else {
                    $this->Flash->success(__d('crm', 'The contacts phone has been saved.'));

                    return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $phone->contact_id]);
                }
            } else {
                $this->Flash->error(__d('crm', 'The contacts phone could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('phone'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Contacts Phone id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        /** @var \Crm\Model\Entity\ContactsPhone $phone */
        $phone = $this->ContactsPhones->get($id);
        $this->Authorization->authorize($phone);

        if ($this->ContactsPhones->delete($phone)) {
            $this->Flash->success(__d('crm', 'The contacts phone has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The contacts phone could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $phone->contact_id]);
    }
}
