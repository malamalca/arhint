<?php
declare(strict_types=1);

namespace Crm\Controller;

use Cake\Core\Configure;
use Cake\Mailer\MailerAwareTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Adremas Controller
 *
 * @property \Crm\Model\Table\AdremasTable $Adremas
 */
class AdremasController extends AppController
{
    use MailerAwareTrait;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $query = $this->Authorization->applyScope($this->Adremas->find());
        if ($this->getRequest()->getQuery('project')) {
            $query->where(['Adremas.project_id' => $this->getRequest()->getQuery('project')]);
        }

        $adremas = $this->paginate($query, ['limit' => 20, 'order' => ['Adremas.created' => 'DESC']]);

        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
        $projectsQuery = $this->getCurrentUser()->applyScope('index', $ProjectsTable->find());
        $projects = $ProjectsTable->findForOwner($this->getCurrentUser()->company_id, $projectsQuery);

        $this->set(compact('adremas', 'projects'));
    }

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
            $adrema = $this->Adremas->get($id);
        } else {
            $adrema = $this->Adremas->newEmptyEntity();
            $adrema->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($adrema);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            /** @var \Crm\Model\Entity\Adrema $adrema */
            $adrema = $this->Adremas->patchEntity($adrema, $this->getRequest()->getData());

            if ($this->Adremas->save($adrema)) {
                // copy contacts to duplicated adrema
                $sourceId = (string)$this->getRequest()->getQuery('adrema');
                if (!empty($sourceId)) {
                    $this->Adremas->copyAddresses($sourceId, $adrema->id);
                }
                $this->Flash->success(__d('crm', 'The adrema has been saved.'));

                return $this->redirect(['action' => 'adremaFields', $adrema->id]);
            } else {
                $this->Flash->error(__d('crm', 'The adrema could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('adrema'));
    }

    /**
     * adremaFields method
     *
     * @param string $id Adrema id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function adremaFields(string $id)
    {
        $adrema = $this->Adremas->get($id);
        $this->Authorization->authorize($adrema, 'edit');

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $adrema = $this->Adremas->patchEntity($adrema, $this->getRequest()->getData());
            if ($this->Adremas->save($adrema)) {
                $this->Flash->success(__d('crm', 'Adrema\'s fields have been saved.'));

                return $this->redirect(['action' => 'view', $adrema->id]);
            } else {
                $this->Flash->error(__d('crm', 'The adrema\'s fields could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('adrema'));
    }

    /**
     * View method
     *
     * @param string|null $id Adrema id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $adrema = $this->Adremas->get($id, contain: ['Attachments', 'FormAttachments']);
        $this->Authorization->authorize($adrema);

        $addresses = TableRegistry::getTableLocator()->get('Crm.AdremasContacts')
            ->find()
            ->where(['adrema_id' => $adrema->id])
            ->contain(['Contacts', 'ContactsAddresses', 'ContactsEmails'])
            ->all();

        $this->set(compact('addresses', 'adrema'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Adrema id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $adrema = $this->Adremas->get($id);
        $this->Authorization->authorize($adrema);

        if ($this->Adremas->delete($adrema)) {
            $this->Flash->success(__d('crm', 'The adrema has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The adrema could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Send email adrema
     *
     * @param string $adremaId Adrema id.
     * @return void
     */
    public function email(string $adremaId)
    {
        $adrema = $this->Adremas->get($adremaId, contain: ['Attachments', 'FormAttachments']);
        $this->Authorization->authorize($adrema, 'view');

        /** @var \Crm\Model\Table\AdremasContactsTable $AdremasContactsTable */
        $AdremasContactsTable = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');
        $addresses = $AdremasContactsTable
            ->find()
            ->where(['adrema_id' => $adrema->id])
            ->contain(['Contacts', 'ContactsAddresses', 'ContactsEmails', 'Attachments'])
            ->all();

        //$this->viewBuilder()->setTemplate('Labels' . DS . 'email' . DS . $adrema->kind_type);
        //$this->set(compact('adrema', 'addresses'));

        $errors = $this->Adremas->getValidator('email')->validate($adrema->toArray(), false);
        if (empty($errors)) {
            $sendErrors = [];
            foreach ($addresses as $address) {
                $this->getMailer('Crm.Crm')
                    ->send(Inflector::variable($adrema->kind_type), [$this->getCurrentUser(), $address, $adrema]);
            }

            $this->set(compact('adrema', 'addresses', 'sendErrors'));
        }
    }

    /**
     * Export label
     *
     * @param string $adremaId Adrema id.
     * @return void
     */
    public function labels(string $adremaId)
    {
        /** @var \Crm\Model\Entity\Adrema $adrema */
        $adrema = $this->Adremas->get($adremaId);
        $this->Authorization->authorize($adrema, 'view');

        $settings = Configure::read('Crm.label.' . $adrema->kind_type);
        unset($settings['form']);
        unset($settings['address']);

        // must be tcpdf because of the way the labels are constructed
        Configure::write('Lil.pdfEngine', 'TCPDF');

        $this->viewBuilder()->setClassName('Lil.Pdf');
        $this->viewBuilder()->setOptions($settings);
        $this->viewBuilder()->setTemplate($adrema->kind_type);

        /** @var \Crm\Model\Table\AdremasContactsTable $AdremasContactsTable */
        $AdremasContactsTable = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');
        $addresses = $AdremasContactsTable
            ->find()
            ->where(['adrema_id' => $adrema->id])
            ->contain(['Contacts', 'ContactsAddresses', 'ContactsEmails', 'Attachments'])
            ->all();

        $this->set(compact('addresses', 'adrema'));

        $this->setResponse($this->getResponse()->withType('pdf'));
    }
}
