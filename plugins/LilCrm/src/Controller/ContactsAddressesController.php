<?php
declare(strict_types=1);

namespace LilCrm\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;

/**
 * ContactsAddresses Controller
 *
 * @property \LilCrm\Model\Table\ContactsAddressesTable $ContactsAddresses
 */
class ContactsAddressesController extends AppController
{
    /**
     * Add method
     *
     * @param  string $contactId Contact uuid.
     * @return void
     */
    public function add($contactId)
    {
        $this->setAction('edit');
    }

    /**
     * Edit method
     *
     * @param  string|null $id Contacts Address id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            /** @var \LilCrm\Model\Entity\ContactsAddress $address */
            $address = $this->ContactsAddresses->get($id);
        } else {
            /** @var \LilCrm\Model\Entity\ContactsAddress $address */
            $address = $this->ContactsAddresses->newEmptyEntity();
            $address->contact_id = $this->getRequest()->getParam('pass.0');
        }

        $this->Authorization->authorize($address);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $this->ContactsAddresses->patchEntity($address, $this->getRequest()->getData());
            if (!$address->getErrors() && $this->ContactsAddresses->save($address)) {
                /** @var \LilCrm\Model\Entity\Contact $contact */
                $contact = $this->ContactsAddresses->Contacts->get($address->contact_id);
                $this->ContactsAddresses->Contacts->touch($contact);
                $this->ContactsAddresses->Contacts->save($contact);

                $this->Flash->success(__d('lil_crm', 'The contacts\' address has been saved.'));

                return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $address->contact_id]);
            } else {
                $this->Flash->error(__d('lil_crm', 'The contacts\' address could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('address'));
    }

    /**
     * Delete method
     *
     * @param  string|null $id Contacts Address id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        /** @var \LilCrm\Model\Entity\ContactsAddress $address */
        $address = $this->ContactsAddresses->get($id);

        $this->Authorization->authorize($address);

        if ($this->ContactsAddresses->delete($address)) {
            $this->Flash->success(__d('lil_crm', 'The contacts address has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_crm', 'The contacts address could not be deleted. Please, try again.'));
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
                ->find()
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
                'label' => $a->contact->title,
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
        } throw new NotFoundException(__d('lil_crm', 'Invalid address'));
    }

    /**
     * autocomplete_zip method
     *
     * @param  string $zipCity Search by zip or city.
     * @return object Renders json.
     */
    public function autocompleteZipCity($zipCity = 'zip')
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is('ajax')) {
            $term = (string)$this->getRequest()->getQuery('term');
            $zipList = Configure::read('LilCrm.zip_codes');

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
        } throw new NotFoundException(__d('lil_crm', 'Invalid address'));
    }
}
