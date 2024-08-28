<?php
declare(strict_types=1);

namespace Crm\Controller;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * ContactsLogs Controller
 *
 * @property \Crm\Model\Table\ContactsLogsTable $ContactsLogs
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class ContactsLogsController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index(): void
    {
        $contactId = $this->getRequest()->getParam('pass.0');

        $sourceRequest = null;
        if (is_null($contactId)) {
            $request = new ServerRequest(['url' => $this->getRequest()->getQuery('source')]);

            $sourceRequest = Router::parseRequest($request);
            $contactId = $sourceRequest['pass'][0] ?? null;
        }

        $contact = TableRegistry::getTableLocator()->get('Crm.Contacts')->get($contactId);
        $this->Authorization->authorize($contact, 'view');

        $query = $this->ContactsLogs->find()
            ->select()
            ->where(['contact_id' => $contactId])
            ->order('created DESC');

        $contactsLogs = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('contactsLogs', 'sourceRequest'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Contacts Log id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            /** @var \Crm\Model\Entity\ContactsLog $contactsLog */
            $contactsLog = $this->ContactsLogs->get($id);
        } else {
            /** @var \Crm\Model\Entity\ContactsLog $contactsLog */
            $contactsLog = $this->ContactsLogs->newEmptyEntity();
            $contactsLog->contact_id = $this->getRequest()->getQuery('contact');
            $contactsLog->user_id = $this->getCurrentUser()->get('id');
        }

        $this->Authorization->Authorize($contactsLog);

        if ($this->request->is(['patch', 'post', 'put'])) {
            /** @var \Crm\Model\Entity\ContactsLog $contactsLog */
            $contactsLog = $this->ContactsLogs->patchEntity($contactsLog, $this->request->getData());

            if ($this->ContactsLogs->save($contactsLog)) {
                if ($this->getRequest()->is('ajax')) {
                    header('Content-Type: text/hml');

                    /** @var \App\Model\Entity\User $user */
                    $user = TableRegistry::getTableLocator()->get('Users')->get($contactsLog->user_id);
                    $this->set(compact('contactsLog', 'user'));
                    die((string)$this->render('/Element/contacts_log'));
                }

                $this->Flash->success(__d('crm', 'The contact log has been saved.'));

                $redirect = $this->getRequest()->getData('redirect');
                if (!empty($redirect)) {
                    return $this->redirect(base64_decode($redirect));
                }

                return $this->redirect([
                    'controller' => 'Contacts',
                    'action' => 'view',
                    $contactsLog->contact_id,
                    '?' => ['tab' => 'logs'],
                ]);
            }
            $this->Flash->error(__d('crm', 'The contacts log could not be saved. Please, try again.'));
        }

        $contact = TableRegistry::getTableLocator()->get('Crm.Contacts')->get($contactsLog->contact_id);
        $this->set(compact('contactsLog', 'contact'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id ContactsLogs Log id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete', 'get']);

        /** @var \Crm\Model\Entity\ContactsLog $contactsLog */
        $contactsLog = $this->ContactsLogs->get($id);
        $this->Authorization->Authorize($contactsLog);

        if ($this->ContactsLogs->delete($contactsLog)) {
            $this->Flash->success(__d('crm', 'The contacts log has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The contacts log could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Contacts', 'action' => 'view', $contactsLog->contact_id]);
    }
}
