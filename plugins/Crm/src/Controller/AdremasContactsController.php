<?php
declare(strict_types=1);

namespace Crm\Controller;

use Cake\ORM\TableRegistry;

/**
 * AdremasContacts Controller
 *
 * @property \Crm\Model\Table\AdremasContactsTable $AdremasContacts
 */
class AdremasContactsController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Adrema id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if ($id) {
            $address = $this->AdremasContacts->get(
                $id,
                contain: ['Contacts', 'ContactsAddresses', 'ContactsEmails', 'Attachments'],
            );
            /** @var \Crm\Model\Entity\Adrema $adrema */
            $adrema = TableRegistry::getTableLocator()->get('Crm.Adremas')->get($address->adrema_id);
        } else {
            $adremaId = $this->getRequest()->getQuery('adrema');

            /** @var \Crm\Model\Entity\Adrema $adrema */
            $adrema = TableRegistry::getTableLocator()->get('Crm.Adremas')->get($adremaId);

            $address = $this->AdremasContacts->newEmptyEntity();
            $address->adrema_id = $adrema->id;
        }

        $this->Authorization->authorize($address, 'edit');

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $address = $this->AdremasContacts->patchEntity($address, $this->getRequest()->getData());
            if (
                !$address->getErrors() &&
                $this->AdremasContacts->save(
                    $address,
                    ['uploadedFiles' => $this->getRequest()->getUploadedFiles()['data'] ?? []],
                )
            ) {
                $this->Flash->success(__d('crm', 'The address has been saved.'));

                return $this->redirect(['controller' => 'Adremas', 'action' => 'view', $address->adrema_id]);
            } else {
                $this->Flash->error(__d('crm', 'The address could not be saved. Please, try again.'));
            }
        }

        $addresses = [];
        $emails = [];
        if ($address->contact_id) {
            $addresses = TableRegistry::getTableLocator()->get('Crm.ContactsAddresses')->find()
                ->where(['contact_id' => $address->contact_id])
                ->all()
                ->combine('id', fn($entity) => $entity)
                ->toArray();

            $emails = TableRegistry::getTableLocator()->get('Crm.ContactsEmails')->find()
                ->where(['contact_id' => $address->contact_id])
                ->all()
                ->combine('id', fn($entity) => $entity)
                ->toArray();
        }

        $this->set(compact('adrema', 'address', 'addresses', 'emails'));
    }

    /**
     * Delete method
     *
     * @param string|null $id AdremasContact id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $address = $this->AdremasContacts->get($id);

        $this->Authorization->authorize($address, 'delete');

        if ($this->AdremasContacts->delete($address)) {
            $this->Flash->success(__d('crm', 'The label has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The label could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Adremas', 'action' => 'view', $address->adrema_id]);
    }
}
