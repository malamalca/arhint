<?php
declare(strict_types=1);

namespace Crm\Controller;

use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
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
            throw new RecordNotFoundException(__d('crm', 'Invalid label'));
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
            ->contain(['Contacts', 'ContactsAddresses', 'ContactsEmails', 'Attachments'])
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
            throw new RecordNotFoundException(__d('crm', 'Invalid label'));
        }

        $settings = Configure::read('Crm.emailAdrema.' . $data['label']);
        unset($settings['form']);

        /** @var \Crm\Model\Table\AdremasTable $AdremasTable */
        $AdremasTable = $this->fetchTable('Crm.Adremas');
        $adrema = $AdremasTable->get($data['adrema']);

        $this->Authorization->authorize($adrema, 'view');

        $attachments = TableRegistry::getTableLocator()->get('Attachments')
            ->find('forModel', model: 'Adrema', foreignId: $adrema->id)
            ->select()
            ->all();

        /** @var \Crm\Model\Table\AdremasContactsTable $AdremasContactsTable */
        $AdremasContactsTable = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');
        $addresses = $AdremasContactsTable
            ->find()
            ->where(['adrema_id' => $adrema->id])
            ->contain(['Contacts', 'ContactsAddresses', 'ContactsEmails', 'Attachments'])
            ->all();

        $this->viewBuilder()->setTemplate('Labels' . DS . 'email' . DS . $data['label']);

        $this->set(compact('addresses', 'data', 'attachments'));
    }
}
