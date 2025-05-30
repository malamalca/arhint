<?php
declare(strict_types=1);

namespace Crm\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use SoapClient;
use SoapFault;

/**
 * Contacts Controller
 *
 * @property \Crm\Model\Table\ContactsTable $Contacts
 */
class ContactsController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index(): void
    {
        $filter = [];

        $filter['kind'] = strtoupper($this->getRequest()->getQuery('kind', 'T'));
        if (!in_array($filter['kind'], ['T', 'C'])) {
            $filter['kind'] = 'T';
        }

        $searchStr = $this->getRequest()->getData('search');
        if (!empty($searchStr)) {
            $filter['search'] = $searchStr;
        } else {
            $searchStr = $this->getRequest()->getQuery('term');
            if (!empty($searchStr)) {
                $filter['search'] = $searchStr;
            }
        }
        $filter = array_merge((array)$this->getRequest()->getQuery(), $filter);

        $params = array_merge_recursive(
            [
                'contain' => ['ContactsEmails', 'ContactsPhones', 'PrimaryAddresses', 'Companies'],
                'conditions' => [],
                'order' => 'Contacts.title',
            ],
            $this->Contacts->filter($filter),
        );

        $query = $this->Authorization->applyScope($this->Contacts->find())
            ->select(['id', 'kind', 'title', 'job', 'descript', 'syncable'])
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->orderBy($params['order']);

        $contacts = $this->paginate($query, ['limit' => 20]);

        // redirect when only single contact found
        if (count($contacts) == 1 && !empty($filter['search'])) {
            //$this->redirect(['action' => 'view', $contacts->first()->id]);
        }

        $this->set(compact('contacts', 'filter'));
    }

    /**
     * View method
     *
     * @param string $id Contact id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(string $id): void
    {
        $contact = $this->Contacts->get($id, contain: [
            'ContactsEmails', 'ContactsPhones', 'ContactsAddresses', 'ContactsAccounts', 'Companies']);

        $this->Authorization->authorize($contact, 'view');

        $employees = $this->Contacts->find()
            ->where(['Contacts.company_id' => $id])
            ->contain(['PrimaryEmails', 'PrimaryPhones'])
            ->all();

        $this->set(compact('contact', 'employees'));
    }

    /**
     * Edit method.
     *
     * @param string|null $id Contact id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if ($id) {
            /** @var \Crm\Model\Entity\Contact $contact */
            $contact = $this->Contacts->get($id, contain: ['Companies',
                'PrimaryAddresses', 'PrimaryAccounts', 'PrimaryEmails', 'PrimaryPhones']);
        } else {
            /** @var \Crm\Model\Entity\Contact $contact */
            $contact = $this->Contacts->newEmptyEntity();
            $contact->owner_id = $this->getCurrentUser()->get('company_id');
            $contact->kind = strtoupper($this->getRequest()->getQuery('kind', 'T'));
            if (!in_array($contact->kind, ['T', 'C'])) {
                $contact->kind = 'T';
            }

            $contact->primary_address = $this->Contacts->ContactsAddresses->newEmptyEntity();
            $contact->primary_account = $this->Contacts->ContactsAccounts->newEmptyEntity();
            $contact->primary_email = $this->Contacts->ContactsEmails->newEmptyEntity();
            $contact->primary_phone = $this->Contacts->ContactsPhones->newEmptyEntity();
        }

        $this->Authorization->authorize($contact);

        if ($this->getRequest()->is(['post', 'put'])) {
            $this->Contacts->patchEntity($contact, $this->getRequest()->getData(), [
                'associated' => [
                    'PrimaryAddresses' => ['validate' => false],
                    'PrimaryAccounts' => ['validate' => false],
                    'PrimaryEmails' => ['validate' => false],
                    'PrimaryPhones' => ['validate' => false],
                ],
            ]);

            $contact->owner_id = $this->getCurrentUser()->get('company_id');

            // do not update company data, only company_id
            unset($contact->company);
            if (empty($contact->primary_address->street)) {
                unset($contact->primary_address);
            } else {
                $contact->setDirty('primary_address', true);
            }
            if (empty($contact->primary_account->iban)) {
                unset($contact->primary_account);
            } else {
                $contact->setDirty('primary_account', true);
            }
            if (empty($contact->primary_email->email)) {
                unset($contact->primary_email);
            } else {
                $contact->setDirty('primary_email', true);
            }
            if (empty($contact->primary_phone->no)) {
                unset($contact->primary_phone);
            } else {
                $contact->setDirty('primary_phone', true);
            }

            if (!$contact->getErrors() && $this->Contacts->save($contact)) {
                if ($this->getRequest()->is('ajax')) {
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody((string)json_encode($contact));
                }

                $this->Flash->success(__d('crm', 'Contact has been saved.'));

                return $this->redirect(['action' => 'view', $contact->id, '?' => ['kind' => $contact->kind]]);
            }

            $this->Flash->error(__d('crm', 'Unable to save your contact.'));
        }

        $this->set(compact('contact'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Project Constructions id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): mixed
    {
        /** @var \Crm\Model\Entity\Contact $contact */
        $contact = $this->Contacts->get($id);

        $this->Authorization->authorize($contact);

        if ($this->Contacts->delete($contact)) {
            $this->Flash->success(__d('crm', 'The contact been deleted.'));

            return $this->redirect(['action' => 'index', '?' => ['kind' => $contact->kind]]);
        } else {
            $this->Flash->error(__d('crm', 'The contact could not be deleted. Please, try again.'));

            return $this->redirect(['action' => 'view', $id]);
        }
    }

    /**
     * Autocomplete emails method
     *
     * @return object
     * @throws \Cake\Http\Exception\NotFoundException When no ajax call.
     */
    public function autocompleteEmail(): object
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $searchTerm = (string)$this->getRequest()->getQuery('term');

            $data = [];

            if (strlen($searchTerm) > 0) {
                $conditions = [
                    'Contacts.title LIKE' => '%' . $searchTerm . '%',
                ];

                $kind = $this->getRequest()->getQuery('kind');
                if (!empty($kind)) {
                    $conditions['Contacts.kind'] = $kind;
                }

                $query = $this->Authorization->applyScope($this->Contacts->find(), 'index')
                    ->select($this->Contacts)
                    ->select(['Contacts__email' => 'c.email'])
                    ->where($conditions)
                    ->orderBy('title')
                    ->join([
                        'table' => 'contacts_emails',
                        'alias' => 'c',
                        'type' => 'INNER',
                        'conditions' => [
                            'c.contact_id = Contacts.id',
                            'c.email LIKE' => '%' . $searchTerm . '%',
                        ],
                    ])
                    ->limit(50);
                $contacts = $query->all();

                foreach ($contacts as $c) {
                    $data[] = ['id' => $c->id, 'text' => $c->email, 'value' => $c->email, 'client' => $c];
                }
            } else {
                $this->Authorization->skipAuthorization();
            }

            $response = $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($data));

            return $response;
        } else {
            throw new NotFoundException(__d('crm', 'Invalid Request'));
        }
    }

    /**
     * admin_autocomplete method
     *
     * @return object
     * @throws \Cake\Http\Exception\NotFoundException When no ajax call.
     */
    public function autocomplete(): object
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $searchTerm = (string)$this->getRequest()->getQuery('term');

            $data = [];

            if ($searchTerm) {
                $conditions = [
                    'Contacts.title LIKE' => '%' . $searchTerm . '%',
                ];

                $kind = $this->getRequest()->getQuery('kind');
                if (!empty($kind)) {
                    $conditions['Contacts.kind'] = $kind;
                }

                if ($this->getRequest()->getQuery('detailed')) {
                    $query = $this->Authorization->applyScope($this->Contacts->find(), 'index')
                        ->select()
                        ->where($conditions)
                        ->orderBy('title')
                        ->contain(['PrimaryAddresses', 'PrimaryAccounts', 'PrimaryEmails', 'PrimaryPhones'])
                        ->limit(50);
                    $contacts = $query->all();

                    foreach ($contacts as $c) {
                        $data[] = [
                            'id' => $c->id,
                            'text' => $c->title,
                            'label' => $c->title,
                            'value' => $c->title,
                            'client' => $c,
                        ];
                    }
                } elseif ($this->getRequest()->getQuery('full')) {
                    $query = $this->Authorization->applyScope($this->Contacts->find(), 'index')
                        ->select()
                        ->where($conditions)
                        ->orderBy('title')
                        ->contain(['ContactsAddresses', 'ContactsAccounts', 'ContactsEmails', 'ContactsPhones'])
                        ->limit(50);
                    $contacts = $query->all();

                    foreach ($contacts as $c) {
                        $data[] = [
                            'id' => $c->id,
                            'text' => $c->title,
                            'label' => $c->title,
                            'value' => $c->title,
                            'client' => $c,
                        ];
                    }
                } else {
                    $result = $this->Authorization->applyScope($this->Contacts->find(), 'index')
                        ->select()
                        ->where($conditions)
                        ->orderBy('title')
                        ->limit(50)
                        ->all()
                        ->combine('id', 'title')
                        ->toArray();

                    foreach ($result as $k => $c) {
                        $data[] = ['id' => $k, 'text' => $c, 'label' => $c, 'value' => $c];
                    }
                }
            } else {
                $this->Authorization->skipAuthorization();
            }

            $response = $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($data));

            return $response;
        } else {
            throw new NotFoundException(__d('crm', 'Invalid Request'));
        }
    }

    /**
     * setSyncable method
     *
     * @param string $id Contacts id.
     * @param bool $syncable Syncable flag.
     * @return object Response.
     */
    public function setSyncable(string $id, bool $syncable): object
    {
        if ($this->getRequest()->is('ajax')) {
            if (!empty($id)) {
                /** @var \Crm\Model\Entity\Contact $contact */
                $contact = $this->Contacts->get($id);

                $this->Authorization->authorize($contact, 'edit');

                $contact->syncable = $syncable;
                $this->Contacts->save($contact);

                return $this->response;
            }
        }
        throw new NotFoundException(__d('crm', 'Invalid ajax call or contact does not exist.'));
    }

    /**
     * inetis method
     *
     * @param string $ddv VAT number.
     * @return mixed Response.
     */
    public function inetis(string $ddv): mixed
    {
        $this->Authorization->skipAuthorization();

        $search = ['iskalni_niz' => $ddv];
        $data = [];

        try {
            $client = new SoapClient('http://ddv.inetis.com/Iskalnik.asmx?WSDL');

            $result = $client->Isci($search);
            if (isset($result->IsciResult->anyType)) {
                $result = $result->IsciResult->anyType->enc_value;

                $street = $result->xmlNaslov;
                $zip = '';
                $city = '';

                $postPos = mb_strrpos($street, ', ');
                if ($postPos !== false) {
                    $zip = mb_substr($street, $postPos + 2, 4);
                    $city = mb_substr($street, $postPos + 6);
                    $street = mb_substr($street, 0, $postPos);
                }

                /** @var \Crm\Model\Entity\Contact $c */
                $c = $this->Contacts->newEntity([
                    'title' => $result->xmlNaziv,
                    'mat_no' => $result->xmlMaticnaStevilka,
                    'tax_no' => $result->xmlDavcnaStevilka,
                    'tax_status' => $result->xmlPlacnikDDV,
                ]);

                $c->primary_address = $this->Contacts->ContactsAddresses->newEntity([
                    'street' => $street,
                    'zip' => $zip,
                    'city' => $city,
                    'country' => 'Slovenija',
                    'country_code' => 'SI',
                ]);

                $racun = null;
                if (isset($result->xmlTransakcijskiRacuni)) {
                    if (is_array($result->xmlTransakcijskiRacuni)) {
                        $racun = $result->xmlTransakcijskiRacuni[0]->xmlTransakcijskiRacun;
                    } else {
                        $racun = $result->xmlTransakcijskiRacuni->xmlTransakcijskiRacun;
                    }
                }

                $c->primary_account = null;
                if ($racun) {
                    if (is_array($racun)) {
                        $racun = reset($racun);
                    }
                    $bban = strtr($racun->xmlTRR, ['-' => '']);
                    $bankId = substr($bban, 0, 2);
                    $banks = Configure::read('Crm.banks');
                    $bic = isset($banks[$bankId]) ? $banks[$bankId]['bic'] : null;
                    $bank = isset($banks[$bankId]) ? $banks[$bankId]['name'] : null;

                    $c->primary_account = $this->Contacts->ContactsAccounts->newEntity([
                        'iban' => 'SI56' . $bban,
                        'bic' => $bic,
                        'bank' => $bank,
                    ]);
                }

                $data = $c;
            }

            $response = $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($data));

            return $response;
        } catch (SoapFault $e) {
            throw new NotFoundException(__d('crm', 'Soap call failed'));
        }
    }
}
