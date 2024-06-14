<?php
declare(strict_types=1);

namespace Crm\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;

/**
 * ContactsAddresses Controller
 *
 * @property \Crm\Model\Table\ContactsAddressesTable $ContactsAddresses
 */
class ContactsAddressesController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Contacts Address id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if ($id) {
            /** @var \Crm\Model\Entity\ContactsAddress $address */
            $address = $this->ContactsAddresses->get($id);
        } else {
            /** @var \Crm\Model\Entity\ContactsAddress $address */
            $address = $this->ContactsAddresses->newEmptyEntity();
            $address->contact_id = $this->getRequest()->getQuery('contact');
        }

        $this->Authorization->authorize($address);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $this->ContactsAddresses->patchEntity($address, $this->getRequest()->getData());
            if (!$address->getErrors() && $this->ContactsAddresses->save($address)) {
                /** @var \Crm\Model\Entity\Contact $contact */
                $contact = $this->ContactsAddresses->Contacts->get($address->contact_id);
                $this->ContactsAddresses->Contacts->touch($contact);
                $this->ContactsAddresses->Contacts->save($contact);

                $this->Flash->success(__d('crm', 'The contacts\' address has been saved.'));

                return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $address->contact_id]);
            } else {
                $this->Flash->error(__d('crm', 'The contacts\' address could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('address'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Contacts Address id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        /** @var \Crm\Model\Entity\ContactsAddress $address */
        $address = $this->ContactsAddresses->get($id);

        $this->Authorization->authorize($address);

        if ($this->ContactsAddresses->delete($address)) {
            $this->Flash->success(__d('crm', 'The contacts address has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The contacts address could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $address->contact_id]);
    }

    /**
     * autocomplete_zip method
     *
     * @return object Renders json.
     */
    public function autocomplete()
    {
        if ($this->getRequest()->is('ajax')) {
            $term = (string)$this->getRequest()->getQuery('term');

            $result = $this->Authorization->applyScope($this->ContactsAddresses->find(), 'index')
                ->select()
                ->where(['OR' => [
                    'Contacts.title LIKE' => '%' . $term . '%',
                    'ContactsAddresses.street LIKE' => '%' . $term . '%',
                ]])
                ->order('Contacts.title')
                ->contain(['Contacts'])
                ->all();

            $data = [];
            foreach ($result as $a) {
                $data[] = [
                'id' => $a->id,
                'value' => $a->contact->title,
                'text' => $a->contact->title,
                'title' => $a->contact->title,
                'street' => $a->street,
                'city' => $a->city,
                'zip' => $a->zip,
                'country' => $a->country,
                ];
            }

            $response = $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($data));

            return $response;
        } throw new NotFoundException(__d('crm', 'Invalid address'));
    }

    /**
     * autocomplete_zip method
     *
     * @param string $zipCity Search by zip or city.
     * @return object Renders json.
     */
    public function autocompleteZipCity(string $zipCity = 'zip')
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is('ajax')) {
            $term = (string)$this->getRequest()->getQuery('term');
            $zipList = Configure::read('Crm.zip_codes');

            $data = [];
            foreach ($zipList as $zip => $city) {
                $q = $zipCity == 'zip' ? $zip : $city;

                if (mb_strtoupper(mb_substr((string)$q, 0, mb_strlen($term))) == mb_strtoupper($term)) {
                    $data[] = [
                        'id' => $zip,
                        'value' => $q,
                        'label' => $city,
                    ];
                }
            }
            $response = $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($data));

            return $response;
        } throw new NotFoundException(__d('crm', 'Invalid address'));
    }
}
