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
     * @param  string $adremaId Adremas uuid.
     * @return void
     */
    public function adrema($adremaId = null)
    {
        $this->loadModel('Crm.Adremas');
        $adremas = $this->Authorization->applyScope($this->Adremas->find('list'), 'index')
            ->toArray();

        if (empty($adremaId)) {
            $adremaId = key((array)$adremas);
        }

        $addresses = [];

        if (!empty($adremaId)) {
            $addresses = TableRegistry::getTableLocator()->get('Crm.AdremasContacts')
                ->find()
                ->where(['adrema_id' => $adremaId])
                ->contain(['ContactsAddresses'])
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

        $this->loadModel('Crm.Adremas');
        $adrema = $this->Adremas->get($adremaId);

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
        $data = $this->getRequest()->getQueryParams();
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

        $adremaId = $this->getRequest()->getQuery('adrema');

        $this->loadModel('Crm.Adremas');
        $adrema = $this->Adremas->get($adremaId);

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
     * Edit method
     *
     * @param  string|null $id Address id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function editAddress($id = null)
    {
        /** @var \Crm\Model\Table\AdremasContactsTable $AdremasContacts */
        $AdremasContacts = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');
        if ($id) {
            $address = $AdremasContacts->get($id, ['contain' => ['ContactsAddresses']]);
            if (!empty($address->contacts_address)) {
                $AdremasContacts->patchEntity($address, (array)$address->contacts_address);
            }
        } else {
            /** @var \Crm\Model\Entity\AdremasContact $address */
            $address = $AdremasContacts->newEmptyEntity();
            $address->adrema_id = $this->getRequest()->getQuery('adrema');
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
        $this->set(compact('address'));
    }

    /**
     * Delete method
     *
     * @param  string|null $id Label id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function deleteAddress($id = null)
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
