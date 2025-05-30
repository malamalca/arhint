<?php
declare(strict_types=1);

namespace Crm\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * Labels Controller
 *
 * @property \Crm\Model\Table\LabelsTable $Labels
 * @property \Crm\Model\Table\AdremasTable $Adremas
 */
class LabelsController extends AppController
{
    /**
     * adrema method
     *
     * STEP 1: Select adrema, add contact to adrema, save, rename, duplicate and delete
     * selected adrema.
     *
     * @param string $adremaId Adremas uuid.
     * @return void
     */
    public function adrema(?string $adremaId = null)
    {
        $AdremasTable = $this->fetchTable('Crm.Adremas');
        $adremas = $this->Authorization->applyScope($AdremasTable->find('list'), 'index')
            ->toArray();

        if (empty($adremaId)) {
            $adremaId = key((array)$adremas);
        }

        $addresses = [];

        if (!empty($adremaId)) {
            $addresses = TableRegistry::getTableLocator()->get('Crm.AdremasContacts')
                ->find()
                ->where(['adrema_id' => $adremaId])
                ->contain(['Contact', 'ContactsAddresses', 'ContactsEmails'])
                ->all();

            if (!in_array($adremaId, array_keys($adremas))) {
                throw new NotFoundException(__d('crm', 'Invalid adrema'));
            }
        }

        $this->set(compact('addresses', 'adremas', 'adremaId'));
    }

    /**
     * label method
     *
     * STEP 2: Select label template from predefined list. Template will be used for preselected
     * adrema contacts in step 1.
     *
     * @return void
     */
    public function label()
    {
        $label = $this->getRequest()->getQuery('label');
        $adremaId = $this->getRequest()->getQuery('adrema');

        $AdremasTable = $this->fetchTable('Crm.Adremas');
        $adrema = $AdremasTable->get($adremaId);

        $this->Authorization->authorize($adrema, 'view');

        $this->set(compact('label', 'adrema'));
    }

    /**
     * export method
     *
     * STEP 3: Print labels to PDF
     *
     * @return void
     */
    public function export()
    {
        $data = $this->getRequest()->getData();
        if (empty($data['label'])) {
            throw new NotFoundException(__d('crm', 'Invalid label'));
        }

        $settings = Configure::read('Crm.label.' . $data['label']);
        unset($settings['form']);

        // must be tcpdf because of the way the labels are constructed
        Configure::write('Lil.pdfEngine', 'TCPDF');

        $this->viewBuilder()->setClassName('Lil.Pdf');
        $this->viewBuilder()->setOptions($settings);
        $this->viewBuilder()->setTemplate($data['label']);

        /** @var \Crm\Model\Table\AdremasTable $AdremasTable */
        $AdremasTable = $this->fetchTable('Crm.Adremas');
        $adrema = $AdremasTable->get($data['adrema']);

        $this->Authorization->authorize($adrema, 'view');

        /** @var \Crm\Model\Table\AdremasContactsTable $AdremasContactsTable */
        $AdremasContactsTable = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');
        $addresses = $AdremasContactsTable
            ->find()
            ->where(['adrema_id' => $adrema->id])
            ->contain(['ContactsAddresses'])
            ->all();

        $this->set(compact('addresses', 'data'));

        $this->setResponse($this->getResponse()->withType('pdf'));
    }

    /**
     * export method
     *
     * STEP 3: Email adrema
     *
     * @return void
     */
    public function email()
    {
        $data = $this->getRequest()->getData();
        if (empty($data['label'])) {
            throw new NotFoundException(__d('crm', 'Invalid label'));
        }

        $settings = Configure::read('Crm.emailAdrema.' . $data['label']);
        unset($settings['form']);

        /** @var \Crm\Model\Table\AdremasTable $AdremasTable */
        $AdremasTable = $this->fetchTable('Crm.Adremas');
        $adrema = $AdremasTable->get($data['adrema']);

        $this->Authorization->authorize($adrema, 'view');

        /** @var \Crm\Model\Table\AdremasContactsTable $AdremasContactsTable */
        $AdremasContactsTable = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');
        $addresses = $AdremasContactsTable
            ->find()
            ->where(['adrema_id' => $adrema->id])
            ->contain(['ContactsAddresses'])
            ->all();

        $this->viewBuilder()->setTemplate('Labels' . DS . 'email' . DS . $data['label']);

        $this->set(compact('addresses', 'data'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Address id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function editAddress(?string $id = null)
    {
        /** @var \Crm\Model\Table\AdremasContactsTable $AdremasContacts */
        $AdremasContacts = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');
        if ($id) {
            $address = $AdremasContacts->get($id, contain: ['Contact', 'ContactsAddresses', 'ContactsEmails']);
            if (!empty($address->contacts_address)) {
                $AdremasContacts->patchEntity($address, $address->contacts_address->toArray());
            }

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
        } else {
            /** @var \Crm\Model\Entity\AdremasContact $address */
            $address = $AdremasContacts->newEmptyEntity();
            $address->adrema_id = $this->getRequest()->getQuery('adrema');

            $addresses = [];
            $emails = [];
        }

        $this->Authorization->authorize($address, 'edit');

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            /** @var \Crm\Model\Entity\AdremasContact $address */
            $address = $AdremasContacts->patchEntity($address, $this->getRequest()->getData());
            if (!$address->getErrors() && $AdremasContacts->save($address)) {
                $this->Flash->success(__d('crm', 'The address has been saved.'));

                return $this->redirect(['action' => 'adrema', $address->adrema_id]);
            } else {
                $this->Flash->error(__d('crm', 'The address could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('address', 'addresses', 'emails'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Label id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function deleteAddress(?string $id = null)
    {
        $AdremasContacts = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');
        /** @var \Crm\Model\Entity\AdremasContact $address */
        $address = $AdremasContacts->get($id);

        $this->Authorization->authorize($address, 'delete');

        if ($AdremasContacts->delete($address)) {
            $this->Flash->success(__d('crm', 'The label has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The label could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'adrema', $address->adrema_id]);
    }
}
