<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

/**
 * Partners Controller
 *
 * @property \Expenses\Model\Table\PartnersTable $Partners
 * @method \App\Model\Entity\User getCurrentUser()
 */
class PartnersController extends AppController
{
    /**
     * Index method – list partners with optional filter.
     *
     * @return void
     */
    public function index(): void
    {
        /** @var array<string, mixed> $filter */
        $filter = $this->getRequest()->getQueryParams();

        $params = array_merge_recursive([
            'contain' => ['Contacts'],
            'conditions' => [],
            'order' => ['Contacts.name' => 'ASC'],
        ], $this->Partners->filter($filter));

        $partners = $this->Authorization->applyScope($this->Partners->find())
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->orderBy($params['order'])
            ->all();

        $roleList = $this->Partners->roleList();

        $contactId = $filter['contact_id'] ?? null;

        $this->set(compact('partners', 'filter', 'roleList', 'contactId'));
    }

    /**
     * Edit method – add or edit a partner.
     *
     * @param string|null $id Partner id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $partner = $this->Partners->get($id, contain: ['Contacts']);
        } else {
            $partner = $this->Partners->newEmptyEntity();
            $partner->contact_id = $this->getRequest()->getQuery('contact_id')
                ?? $this->getRequest()->getData('contact_id');
        }

        $this->Authorization->authorize($partner);

        $roleList = $this->Partners->roleList();

        /** @var \Crm\Model\Table\ContactsTable $Contacts */
        $Contacts = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $contactList = $Contacts->find()
            ->select(['id', 'name'])
            ->orderBy(['name' => 'ASC'])
            ->all()
            ->combine('id', 'name')
            ->toArray();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();

            if (isset($data['date_start']) && $data['date_start'] === '') {
                $data['date_start'] = null;
            }

            if (isset($data['date_end']) && $data['date_end'] === '') {
                $data['date_end'] = null;
            }

            $partner = $this->Partners->patchEntity($partner, $data);

            if ($this->Partners->save($partner)) {
                if ($this->getRequest()->is('ajax')) {
                    return $this->getResponse()
                        ->withType('application/json')
                        ->withStringBody((string)json_encode(['id' => $partner->id]));
                }

                $this->Flash->success(__d('expenses', 'The partner has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('expenses', 'The partner could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('partner', 'roleList', 'contactList'));

        return null;
    }

    /**
     * Autocomplete method – search contacts linked to partners by title.
     *
     * @return void
     */
    public function autocomplete(): void
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $term = $this->getRequest()->getQuery('term');
            if (is_string($term) && $term !== '') {
                $this->Authorization->skipAuthorization();
                $partners = $this->Partners->find()
                    ->contain(['Contacts'])
                    ->innerJoinWith('Contacts')
                    ->where(['Contacts.title LIKE' => '%' . $term . '%'])
                    ->orderBy(['Contacts.title' => 'ASC'])
                    ->limit(20)
                    ->all();
                $this->set(compact('partners'));
            } else {
                $this->Authorization->skipAuthorization();
            }
        } else {
            throw new NotFoundException(__d('expenses', 'Invalid ajax call.'));
        }
    }

    /**
     * Delete method.
     *
     * @param string|null $id Partner id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $partner = $this->Partners->get($id);
        $this->Authorization->authorize($partner);

        if ($this->Partners->delete($partner)) {
            $this->Flash->success(__d('expenses', 'The partner has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The partner could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->getRequest()->getQuery('redirect', [
            'plugin' => 'Crm',
            'controller' => 'Contacts',
            'action' => 'view',
            $partner->contact_id,
            '?' => ['tab' => 'partners'],
        ]));
    }

    /**
     * PickContact method – popup for creating a partner from an existing contact.
     *
     * GET:  returns an HTML contacts-table snippet (no layout) for use in a modalPopup.
     * POST: creates a new Partner for the given contact_id + role and returns JSON {id, value}.
     *
     * @return \Cake\Http\Response|null
     */
    public function pickContact(): ?Response
    {
        $this->Authorization->skipAuthorization();

        /** @var \Crm\Model\Table\ContactsTable $Contacts */
        $Contacts = TableRegistry::getTableLocator()->get('Crm.Contacts');

        if ($this->getRequest()->is(['post', 'put', 'patch'])) {
            $contactId = (string)$this->getRequest()->getData('contact_id', '');
            $role = (string)$this->getRequest()->getData('role', 'buyer');

            if (empty($contactId)) {
                return $this->getResponse()
                    ->withStatus(400)
                    ->withType('application/json')
                    ->withStringBody((string)json_encode(['error' => 'contact_id required']));
            }

            // Check if a partner already exists for this contact.
            $existing = $this->Partners->find()
                ->where(['contact_id' => $contactId])
                ->first();

            if ($existing) {
                $partner = $existing;
            } else {
                $partner = $this->Partners->newEntity([
                    'contact_id' => $contactId,
                    'role' => $role,
                ]);

                if (!$this->Partners->save($partner)) {
                    return $this->getResponse()
                        ->withStatus(422)
                        ->withType('application/json')
                        ->withStringBody((string)json_encode(['error' => 'Could not save partner']));
                }
            }

            $partner = $this->Partners->get($partner->id, contain: ['Contacts']);
            $value = $partner->contact->title ?? '';

            return $this->getResponse()
                ->withType('application/json')
                ->withStringBody((string)json_encode(['id' => $partner->id, 'value' => $value]));
        }

        $ownerId = (string)$this->getCurrentUser()->get('company_id');
        $contacts = $Contacts->find()
            ->where(['Contacts.owner_id' => $ownerId])
            ->select(['id', 'title', 'name'])
            ->orderBy(['Contacts.title' => 'ASC'])
            ->limit(200)
            ->all()
            ->toList();

        $roleList = $this->Partners->roleList();

        $this->set(compact('contacts', 'roleList'));

        return null;
    }
}
