<?php
declare(strict_types=1);

namespace LilCrm\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use SoapClient;

/**
 * Contacts Controller
 *
 * @property \LilCrm\Model\Table\ContactsTable $Contacts
 */
class ContactsController extends AppController
{
    /**
     * Initialize function
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    /**
     * Index method
     *
     * @return void
     */
    public function index()
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
            $this->Contacts->filter($filter)
        );

        $query = $this->Authorization->applyScope($this->Contacts->find())
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->order($params['order']);

        $contacts = $this->Paginator->paginate($query, ['limit' => 20]);

        // redirect when only single contact found
        if (count($contacts) == 1 && !empty($filter['search'])) {
            //$this->redirect(['action' => 'view', $contacts->first()->id]);
        }

        $this->set(compact('contacts', 'filter'));
    }

    /**
     * View method
     *
     * @param  string $id Contact id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view($id)
    {
        $contact = $this->Contacts->get($id, ['contain' => [
            'ContactsEmails', 'ContactsPhones', 'ContactsAddresses', 'ContactsAccounts', 'Companies']]);

        $this->Authorization->authorize($contact, 'view');

        $employees = $this->Contacts->find()
            ->where(['Contacts.company_id' => $id])
            ->contain(['PrimaryEmails', 'PrimaryPhones'])
            ->all();

        $this->set(compact('contact', 'employees'));
    }

    /**
     * Add method.
     *
     * @return mixed
     */
    public function add()
    {
        $ret = $this->setAction('edit');

        return $ret;
    }

    /**
     * Edit method.
     *
     * @param  string|null $id Contact id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            /** @var \LilCrm\Model\Entity\Contact $contact */
            $contact = $this->Contacts->get($id, ['contain' => ['Companies',
                'PrimaryAddresses', 'PrimaryAccounts', 'PrimaryEmails', 'PrimaryPhones']]);
        } else {
            /** @var \LilCrm\Model\Entity\Contact $contact */
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

                $this->Flash->success(__d('lil_crm', 'Contact has been saved.'));

                return $this->redirect(['action' => 'view', $contact->id, '?' => ['kind' => $contact->kind]]);
            }

            $this->Flash->error(__d('lil_crm', 'Unable to save your contact.'));
        }

        $this->set(compact('contact'));

        return null;
    }

    /**
     * Delete method
     *
     * @param  string|null $id Project Constructions id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        /** @var \LilCrm\Model\Entity\Contact $contact */
        $contact = $this->Contacts->get($id);

        $this->Authorization->authorize($contact);

        if ($this->Contacts->delete($contact)) {
            $this->Flash->success(__d('lil_crm', 'The contact been deleted.'));

            return $this->redirect(['action' => 'index', '?' => ['kind' => $contact->kind]]);
        } else {
            $this->Flash->error(__d('lil_crm', 'The contact could not be deleted. Please, try again.'));

            return $this->redirect(['action' => 'view', $id]);
        }
    }

    /**
     * admin_autocomplete method
     *
     * @return object
     * @throws \Cake\Http\Exception\NotFoundException When no ajax call.
     */
    public function autocomplete()
    {
        if ($this->getRequest()->is('ajax')) {
            $searchTerm = (string)$this->getRequest()->getQuery('term');

            $data = [];

            if ($searchTerm !== null) {
                $conditions = [
                    'Contacts.title LIKE' => '%' . $searchTerm . '%',
                ];

                $kind = $this->getRequest()->getQuery('kind');
                if (!empty($kind)) {
                    $conditions['Contacts.kind'] = $kind;
                }

                if ($this->getRequest()->getQuery('detailed')) {
                    $contacts = $this->Authorization->applyScope($this->Contacts->find(), 'index')
                        ->select()
                        ->where($conditions)
                        ->order('title')
                        ->contain(['PrimaryAddresses', 'PrimaryAccounts', 'PrimaryEmails', 'PrimaryPhones'])
                        ->limit(50)
                        ->all();

                    foreach ($contacts as $c) {
                        $data[] = ['label' => $c->title, 'value' => $c->title, 'client' => $c];
                    }
                } else {
                    $result = $this->Authorization->applyScope($this->Contacts->find(), 'index')
                        ->select()
                        ->where($conditions)
                        ->order('title')
                        ->limit(50)
                        ->combine('id', 'title')
                        ->toArray();

                    foreach ($result as $k => $c) {
                        $data[] = ['id' => $k, 'label' => $c, 'value' => $c];
                    }
                }
            }

            $response = $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($data));

            return $response;
        } else {
            throw new NotFoundException(__d('lil_crm', 'Invalid Request'));
        }
    }

    /**
     * setSyncable method
     *
     * @param  string $id Contacts id.
     * @param  bool $syncable Syncable flag.
     * @return object Response.
     */
    public function setSyncable($id, $syncable)
    {
        if ($this->getRequest()->is('ajax')) {
            if (!empty($id)) {
                /** @var \LilCrm\Model\Entity\Contact $contact */
                $contact = $this->Contacts->get($id);

                $this->Authorization->authorize($contact, 'edit');

                $contact->syncable = (bool)$syncable;
                $this->Contacts->save($contact);

                return $this->response;
            }
        }
        throw new NotFoundException(__d('lil_crm', 'Invalid ajax call or contact does not exist.'));
    }

    /**
     * inetis method
     *
     * @param string $ddv VAT number.
     * @return mixed Response.
     */
    public function inetis($ddv)
    {
        $this->Authorization->skipAuthorization();

        $search = ['iskalni_niz' => $ddv];
        $client = new SoapClient('http://ddv.inetis.com/Iskalnik.asmx?WSDL');
        $data = [];
        if (is_callable([$client, 'Isci'])) {
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

                /** @var \LilCrm\Model\Entity\Contact $c */
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
                    $banks = Configure::read('LilCrm.banks');
                    $bic = isset($banks[$bankId]) ? $banks[$bankId]['bic'] : null;
                    $bank = isset($banks[$bankId]) ? $banks[$bankId]['name'] : null;

                    $c->primary_account = $this->Contacts->ContactsAccounts->newEntity([
                        'iban' => 'SI56' . $bban,
                        'bic' => $bic,
                        'bank' => $bank,
                    ]);
                }

                $data = $c;
            } else {
                throw new NotFoundException(__d('lil_crm', 'Soap call failed'));
            }

            $response = $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($data));

            return $response;
        } else {
            throw new NotFoundException(__d('lil_crm', 'Soap call failed'));
        }
    }
}
