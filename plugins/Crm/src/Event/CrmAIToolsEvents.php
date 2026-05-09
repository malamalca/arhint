<?php
declare(strict_types=1);

namespace Crm\Event;

use App\Lib\AITool;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use SoapClient;
use SoapFault;

class CrmAIToolsEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'App.AIAssistant.registerModule' => 'aiAssistantRegisterModule',
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    /**
     * Register the Crm module for AI assistant module detection.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $modulesList Modules list to append to.
     * @return void
     */
    public function aiAssistantRegisterModule(Event $event, ArrayObject $modulesList): void
    {
        $modulesList['Crm'] = 'CRM tools for managing contacts, companies, and communication logs.';
    }

    /**
     * Add AI assistant tools
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $toolsList List of tools
     * @return void
     */
    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        $toolsList->append(new AITool(
            name: 'Crm.navigate_to_contact',
            arguments: [
                'search' => [
                    'type' => 'string',
                    'description' => 'Name or partial name of the company or person to navigate to. Required.',
                ],
                'kind' => [
                    'type' => 'string',
                    'description' => 'Contact kind: "C" for companies, "T" for persons. Defaults to "C".',
                ],
            ],
            description: 'Finds a CRM contact by name and navigates directly to their detail page. '
                . 'Use when the user asks to open or go to a specific contact or company page.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.search_contacts',
            arguments: [
                'search' => [
                    'type' => 'string',
                    'description' => 'Search term to filter contacts by name or phone number.',
                ],
                'kind' => [
                    'type' => 'string',
                    'description' => 'Contact kind: "C" for companies, "T" for persons. Defaults to "C".',
                ],
            ],
            description: 'Searches CRM contacts by name or phone number. Returns a list of matching contacts '
                . 'including their title, tax number, and primary email/phone/address.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.get_contact',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the contact to retrieve.'],
            ],
            description: 'Fetches full details of a single CRM contact by ID, including all emails, '
                . 'phones, addresses, and bank accounts.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.get_contact_logs',
            arguments: [
                'contact_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the contact whose interaction logs to retrieve.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of log entries to return. Defaults to 20.',
                ],
            ],
            description: 'Returns the interaction/communication history (log entries) for a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.add_contact_log',
            arguments: [
                'contact_id' => ['type' => 'string', 'description' => 'UUID of the contact to add the log entry to.'],
                'descript' => ['type' => 'string', 'description' => 'Content of the log entry. HTML is allowed.'],
                'kind' => [
                    'type' => 'string',
                    'description' => 'Log kind: "E" for email, "N" for note. Defaults to "N".',
                ],
            ],
            description: 'Creates a new interaction log entry for a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.lookup_company',
            arguments: [
                'tax_no' => [
                    'type' => 'string',
                    'description' => 'Slovenian VAT/tax number (davčna številka) to look up.',
                ],
            ],
            description: 'Looks up a Slovenian company by its VAT/tax number using the inetis registry. '
                . 'Returns company name, registration number, tax number, address, and bank account.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.create_contact',
            arguments: [
                'kind' => [
                    'type' => 'string',
                    'description' => 'Contact kind: "C" for company, "T" for person. Required.',
                ],
                'name' => ['type' => 'string', 'description' => 'First name (for persons).'],
                'surname' => ['type' => 'string', 'description' => 'Last name (for persons).'],
                'title' => [
                    'type' => 'string',
                    'description' => 'Display name / company name. Required for companies.',
                ],
                'tax_no' => ['type' => 'string', 'description' => 'VAT/tax number.'],
                'mat_no' => ['type' => 'string', 'description' => 'Registration/matriculation number.'],
                'job' => ['type' => 'string', 'description' => 'Job title (for persons).'],
                'email' => ['type' => 'string', 'description' => 'Primary email address.'],
                'phone' => ['type' => 'string', 'description' => 'Primary phone number.'],
                'street' => ['type' => 'string', 'description' => 'Primary address street.'],
                'zip' => ['type' => 'string', 'description' => 'Primary address postal code.'],
                'city' => ['type' => 'string', 'description' => 'Primary address city.'],
                'country_code' => [
                    'type' => 'string',
                    'description' => 'Primary address country code (e.g. "SI"). Defaults to "SI".',
                ],
            ],
            description: 'Creates a new CRM contact (person or company) with optional primary email, '
                . 'phone, and address.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.update_contact',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the contact to update. Required.'],
                'name' => ['type' => 'string', 'description' => 'First name (for persons).'],
                'surname' => ['type' => 'string', 'description' => 'Last name (for persons).'],
                'title' => ['type' => 'string', 'description' => 'Display name / company name.'],
                'tax_no' => ['type' => 'string', 'description' => 'VAT/tax number.'],
                'mat_no' => ['type' => 'string', 'description' => 'Registration/matriculation number.'],
                'job' => ['type' => 'string', 'description' => 'Job title (for persons).'],
                'descript' => ['type' => 'string', 'description' => 'Notes/description for the contact.'],
            ],
            description: 'Updates fields on an existing CRM contact.',
        ));

        // --- Phones ---
        $toolsList->append(new AITool(
            name: 'Crm.add_contact_phone',
            arguments: [
                'contact_id' => ['type' => 'string', 'description' => 'UUID of the contact.'],
                'no' => ['type' => 'string', 'description' => 'Phone number. Required.'],
                'kind' => [
                    'type' => 'string',
                    'description' => 'Phone type: H=home, M=mobile, W=work, F=fax, P=other. Defaults to W.',
                ],
                'primary' => [
                    'type' => 'boolean',
                    'description' => 'Whether this is the primary phone. Defaults to false.',
                ],
            ],
            description: 'Adds a phone number to a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.edit_contact_phone',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the phone entry to update.'],
                'no' => ['type' => 'string', 'description' => 'Phone number.'],
                'kind' => [
                    'type' => 'string',
                    'description' => 'Phone type: H=home, M=mobile, W=work, F=fax, P=other.',
                ],
                'primary' => ['type' => 'boolean', 'description' => 'Whether this is the primary phone.'],
            ],
            description: 'Updates a phone entry on a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.delete_contact_phone',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the phone entry to delete.'],
            ],
            description: 'Deletes a phone entry from a CRM contact.',
        ));

        // --- Emails ---
        $toolsList->append(new AITool(
            name: 'Crm.add_contact_email',
            arguments: [
                'contact_id' => ['type' => 'string', 'description' => 'UUID of the contact.'],
                'email' => ['type' => 'string', 'description' => 'Email address. Required.'],
                'kind' => ['type' => 'string', 'description' => 'Email type: P=personal, W=work. Defaults to W.'],
                'primary' => [
                    'type' => 'boolean',
                    'description' => 'Whether this is the primary email. Defaults to false.',
                ],
            ],
            description: 'Adds an email address to a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.edit_contact_email',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the email entry to update.'],
                'email' => ['type' => 'string', 'description' => 'Email address.'],
                'kind' => ['type' => 'string', 'description' => 'Email type: P=personal, W=work.'],
                'primary' => ['type' => 'boolean', 'description' => 'Whether this is the primary email.'],
            ],
            description: 'Updates an email entry on a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.delete_contact_email',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the email entry to delete.'],
            ],
            description: 'Deletes an email entry from a CRM contact.',
        ));

        // --- Addresses ---
        $toolsList->append(new AITool(
            name: 'Crm.add_contact_address',
            arguments: [
                'contact_id' => ['type' => 'string', 'description' => 'UUID of the contact.'],
                'street' => ['type' => 'string', 'description' => 'Street address. Required.'],
                'zip' => ['type' => 'string', 'description' => 'Postal code.'],
                'city' => ['type' => 'string', 'description' => 'City.'],
                'country' => ['type' => 'string', 'description' => 'Country name.'],
                'country_code' => ['type' => 'string', 'description' => 'ISO country code (e.g. SI). Defaults to SI.'],
                'kind' => [
                    'type' => 'string',
                    'description' => 'Address type: P=permanent, H=home, W=work, O=other. Defaults to W.',
                ],
                'primary' => [
                    'type' => 'boolean',
                    'description' => 'Whether this is the primary address. Defaults to false.',
                ],
            ],
            description: 'Adds an address to a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.edit_contact_address',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the address entry to update.'],
                'street' => ['type' => 'string', 'description' => 'Street address.'],
                'zip' => ['type' => 'string', 'description' => 'Postal code.'],
                'city' => ['type' => 'string', 'description' => 'City.'],
                'country' => ['type' => 'string', 'description' => 'Country name.'],
                'country_code' => ['type' => 'string', 'description' => 'ISO country code.'],
                'kind' => ['type' => 'string', 'description' => 'Address type: P=permanent, H=home, W=work, O=other.'],
                'primary' => ['type' => 'boolean', 'description' => 'Whether this is the primary address.'],
            ],
            description: 'Updates an address entry on a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.delete_contact_address',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the address entry to delete.'],
            ],
            description: 'Deletes an address entry from a CRM contact.',
        ));

        // --- Bank accounts ---
        $toolsList->append(new AITool(
            name: 'Crm.add_contact_account',
            arguments: [
                'contact_id' => ['type' => 'string', 'description' => 'UUID of the contact.'],
                'iban' => ['type' => 'string', 'description' => 'IBAN number. Required.'],
                'bic' => ['type' => 'string', 'description' => 'BIC/SWIFT code.'],
                'bank' => ['type' => 'string', 'description' => 'Bank name.'],
                'kind' => ['type' => 'string', 'description' => 'Account type: P=personal, B=business. Defaults to B.'],
                'primary' => [
                    'type' => 'boolean',
                    'description' => 'Whether this is the primary account. Defaults to false.',
                ],
            ],
            description: 'Adds a bank account to a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.edit_contact_account',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the bank account entry to update.'],
                'iban' => ['type' => 'string', 'description' => 'IBAN number.'],
                'bic' => ['type' => 'string', 'description' => 'BIC/SWIFT code.'],
                'bank' => ['type' => 'string', 'description' => 'Bank name.'],
                'kind' => ['type' => 'string', 'description' => 'Account type: P=personal, B=business.'],
                'primary' => ['type' => 'boolean', 'description' => 'Whether this is the primary account.'],
            ],
            description: 'Updates a bank account entry on a CRM contact.',
        ));

        $toolsList->append(new AITool(
            name: 'Crm.delete_contact_account',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the bank account entry to delete.'],
            ],
            description: 'Deletes a bank account entry from a CRM contact.',
        ));
    }

    /**
     * Execute AI assistant tool
     *
     * @param \Cake\Event\Event $event Event object
     * @param string $tool Tool name
     * @param array<mixed> $arguments Tool arguments
     * @return void
     */
    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        $currentUser = $event->getData()[2] ?? null;

        match ($tool) {
            'Crm.navigate_to_contact' => $this->executeNavigateToContact($event, $arguments, $currentUser),
            'Crm.search_contacts' => $this->executeSearchContacts($event, $arguments, $currentUser),
            'Crm.get_contact' => $this->executeGetContact($event, $arguments, $currentUser),
            'Crm.get_contact_logs' => $this->executeGetContactLogs($event, $arguments, $currentUser),
            'Crm.add_contact_log' => $this->executeAddContactLog($event, $arguments, $currentUser),
            'Crm.lookup_company' => $this->executeLookupCompany($event, $arguments),
            'Crm.create_contact' => $this->executeCreateContact($event, $arguments, $currentUser),
            'Crm.update_contact' => $this->executeUpdateContact($event, $arguments, $currentUser),
            'Crm.add_contact_phone' => $this->executeAddContactPhone($event, $arguments, $currentUser),
            'Crm.edit_contact_phone' => $this->executeEditContactPhone($event, $arguments, $currentUser),
            'Crm.delete_contact_phone' => $this->executeDeleteContactPhone($event, $arguments, $currentUser),
            'Crm.add_contact_email' => $this->executeAddContactEmail($event, $arguments, $currentUser),
            'Crm.edit_contact_email' => $this->executeEditContactEmail($event, $arguments, $currentUser),
            'Crm.delete_contact_email' => $this->executeDeleteContactEmail($event, $arguments, $currentUser),
            'Crm.add_contact_address' => $this->executeAddContactAddress($event, $arguments, $currentUser),
            'Crm.edit_contact_address' => $this->executeEditContactAddress($event, $arguments, $currentUser),
            'Crm.delete_contact_address' => $this->executeDeleteContactAddress($event, $arguments, $currentUser),
            'Crm.add_contact_account' => $this->executeAddContactAccount($event, $arguments, $currentUser),
            'Crm.edit_contact_account' => $this->executeEditContactAccount($event, $arguments, $currentUser),
            'Crm.delete_contact_account' => $this->executeDeleteContactAccount($event, $arguments, $currentUser),
            default => null,
        };
    }

    /**
     * Execute Crm.navigate_to_contact tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeNavigateToContact(Event $event, array $arguments, mixed $currentUser): void
    {
        $search = trim($arguments['search'] ?? '');
        if ($search === '') {
            $event->setResult(['error' => 'search argument is required.']);

            return;
        }

        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        $filter = ['kind' => $arguments['kind'] ?? 'C', 'search' => $search];
        $params = $contactsTable->filter($filter);

        $contact = $currentUser->applyScope('index', $contactsTable->find())
            ->select(['Contacts.id', 'Contacts.title', 'Contacts.kind'])
            ->where($params['conditions'])
            ->first();

        if (!$contact) {
            $event->setResult(['error' => 'No contact found matching "' . $search . '".']);

            return;
        }

        $url = Router::url([
            'plugin' => 'Crm',
            'controller' => 'Contacts',
            'action' => 'view',
            $contact->id,
            '?' => ['kind' => $contact->kind],
        ], true);

        $event->setResult([
            'redirect_url' => $url,
            'contact_id' => $contact->id,
            'contact_title' => $contact->title,
        ]);
    }

    /**
     * Execute Crm.search_contacts tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeSearchContacts(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        $filter = ['kind' => $arguments['kind'] ?? 'C'];

        if (!empty($arguments['search'])) {
            /** @var \Crm\Model\Table\ContactsSearchIndexTable $searchIndexTable */
            $searchIndexTable = TableRegistry::getTableLocator()->get('Crm.ContactsSearchIndex');
            $ft = $searchIndexTable->search($arguments['search'], $currentUser->get('company_id'));
            $filter['id'] = array_keys($ft);
        }

        $params = $contactsTable->filter($filter);

        $contacts = $currentUser->applyScope('index', $contactsTable->find())
            ->select(['Contacts.id', 'Contacts.title', 'Contacts.kind', 'Contacts.tax_no', 'Contacts.mat_no'])
            ->contain(['PrimaryEmails', 'PrimaryPhones', 'PrimaryAddresses'])
            ->where($params['conditions'])
            ->limit(20)
            ->all()
            ->toArray();

        $event->setResult($contacts);
    }

    /**
     * Execute Crm.get_contact tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetContact(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        $contact = $currentUser->applyScope('index', $contactsTable->find())
            ->contain(['ContactsEmails', 'ContactsPhones', 'ContactsAddresses', 'ContactsAccounts'])
            ->where(['Contacts.id' => $arguments['id'] ?? ''])
            ->first();

        $event->setResult($contact);
    }

    /**
     * Execute Crm.get_contact_logs tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetContactLogs(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        $contact = $currentUser->applyScope('index', $contactsTable->find())
            ->where(['Contacts.id' => $arguments['contact_id'] ?? ''])
            ->first();

        if (!$contact || !$currentUser->can('view', $contact)) {
            $event->setResult(['error' => 'Contact not found or access denied.']);

            return;
        }

        $limit = (int)($arguments['limit'] ?? 20);
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        $logs = TableRegistry::getTableLocator()->get('Crm.ContactsLogs')
            ->find()
            ->where(['ContactsLogs.contact_id' => $contact->id])
            ->orderBy(['ContactsLogs.created' => 'DESC'])
            ->limit($limit)
            ->all()
            ->toArray();

        $event->setResult($logs);
    }

    /**
     * Execute Crm.add_contact_log tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddContactLog(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        $contact = $currentUser->applyScope('index', $contactsTable->find())
            ->where(['Contacts.id' => $arguments['contact_id'] ?? ''])
            ->first();

        if (!$contact || !$currentUser->can('edit', $contact)) {
            $event->setResult(['error' => 'Contact not found or access denied.']);

            return;
        }

        $kind = $arguments['kind'] ?? 'N';
        if (!in_array($kind, ['E', 'N'])) {
            $kind = 'N';
        }

        $contactsLogsTable = TableRegistry::getTableLocator()->get('Crm.ContactsLogs');
        $log = $contactsLogsTable->newEntity([
            'contact_id' => $contact->id,
            'user_id' => $currentUser->get('id'),
            'kind' => $kind,
            'descript' => $arguments['descript'] ?? '',
        ]);

        if (!$log->getErrors() && $contactsLogsTable->save($log)) {
            $event->setResult($log);
        } else {
            $event->setResult(['error' => 'Failed to save log entry.', 'errors' => $log->getErrors()]);
        }
    }

    /**
     * Execute Crm.lookup_company tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @return void
     */
    private function executeLookupCompany(Event $event, array $arguments): void
    {
        $taxNo = trim($arguments['tax_no'] ?? '');
        try {
            $client = new SoapClient('http://ddv.inetis.com/Iskalnik.asmx?WSDL');
            $result = $client->Isci(['iskalni_niz' => $taxNo]);

            if (isset($result->IsciResult->anyType)) {
                $r = $result->IsciResult->anyType->enc_value;

                $street = $r->xmlNaslov;
                $zip = '';
                $city = '';
                $postPos = mb_strrpos($street, ', ');
                if ($postPos !== false) {
                    $zip = mb_substr($street, $postPos + 2, 4);
                    $city = mb_substr($street, $postPos + 6);
                    $street = mb_substr($street, 0, $postPos);
                }

                $racun = null;
                if (isset($r->xmlTransakcijskiRacuni)) {
                    $item = is_array($r->xmlTransakcijskiRacuni)
                        ? $r->xmlTransakcijskiRacuni[0]->xmlTransakcijskiRacun
                        : $r->xmlTransakcijskiRacuni->xmlTransakcijskiRacun;
                    $racun = is_array($item) ? reset($item) : $item;
                }

                $ibanData = null;
                if ($racun) {
                    $bban = strtr($racun->xmlTRR, ['-' => '']);
                    $bankId = substr($bban, 0, 2);
                    $banks = Configure::read('Crm.banks');
                    $ibanData = [
                        'iban' => 'SI56' . $bban,
                        'bic' => $banks[$bankId]['bic'] ?? null,
                        'bank' => $banks[$bankId]['name'] ?? null,
                    ];
                }

                $event->setResult([
                    'title' => $r->xmlNaziv,
                    'mat_no' => $r->xmlMaticnaStevilka,
                    'tax_no' => $r->xmlDavcnaStevilka,
                    'tax_status' => $r->xmlPlacnikDDV,
                    'street' => $street,
                    'zip' => $zip,
                    'city' => $city,
                    'country' => 'Slovenija',
                    'country_code' => 'SI',
                    'bank_account' => $ibanData,
                ]);
            } else {
                $event->setResult(['error' => 'Company not found.']);
            }
        } catch (SoapFault $e) {
            $event->setResult(['error' => 'SOAP lookup failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Execute Crm.create_contact tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeCreateContact(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        $kind = strtoupper($arguments['kind'] ?? 'C');
        if (!in_array($kind, ['T', 'C'])) {
            $kind = 'C';
        }

        $contactData = ['kind' => $kind, 'owner_id' => $currentUser->get('company_id')];
        foreach (['name', 'surname', 'title', 'tax_no', 'mat_no', 'job'] as $field) {
            if (isset($arguments[$field])) {
                $contactData[$field] = $arguments[$field];
            }
        }
        if (!empty($arguments['email'])) {
            $contactData['primary_email'] = ['email' => $arguments['email'], 'kind' => 'W', 'primary' => true];
        }
        if (!empty($arguments['phone'])) {
            $contactData['primary_phone'] = ['no' => $arguments['phone'], 'kind' => 'W', 'primary' => true];
        }
        if (!empty($arguments['street'])) {
            $contactData['primary_address'] = [
                'street' => $arguments['street'],
                'zip' => $arguments['zip'] ?? '',
                'city' => $arguments['city'] ?? '',
                'country_code' => $arguments['country_code'] ?? 'SI',
                'primary' => true,
            ];
        }

        $contact = $contactsTable->newEntity($contactData, [
            'associated' => ['PrimaryEmails', 'PrimaryPhones', 'PrimaryAddresses'],
        ]);

        if (!$currentUser->can('edit', $contact)) {
            $event->setResult(['error' => 'You are not authorized to create contacts.']);

            return;
        }

        if (!$contact->getErrors() && $contactsTable->save($contact)) {
            $event->setResult([
                'id' => $contact->id,
                'title' => $contact->get('title'),
                'kind' => $contact->get('kind'),
            ]);
        } else {
            $event->setResult(['error' => 'Failed to create contact.', 'errors' => $contact->getErrors()]);
        }
    }

    /**
     * Execute Crm.update_contact tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeUpdateContact(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        $contact = $currentUser->applyScope('index', $contactsTable->find())
            ->where(['Contacts.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$contact) {
            $event->setResult(['error' => 'Contact not found or access denied.']);

            return;
        }

        if (!$currentUser->can('edit', $contact)) {
            $event->setResult(['error' => 'You are not authorized to edit this contact.']);

            return;
        }

        $updateData = [];
        foreach (['name', 'surname', 'title', 'tax_no', 'mat_no', 'job', 'descript'] as $field) {
            if (isset($arguments[$field])) {
                $updateData[$field] = $arguments[$field];
            }
        }
        // @phpstan-ignore argument.templateType
        $contactsTable->patchEntity($contact, $updateData);

        // @phpstan-ignore argument.templateType
        if (!$contact->getErrors() && $contactsTable->save($contact)) {
            $event->setResult(['id' => $contact->id, 'title' => $contact->get('title')]);
        } else {
            $event->setResult(['error' => 'Failed to update contact.', 'errors' => $contact->getErrors()]);
        }
    }

    // --- Phones ---

    /**
     * Execute Crm.add_contact_phone tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddContactPhone(Event $event, array $arguments, mixed $currentUser): void
    {
        $contact = $this->loadOwnedContact($currentUser, $arguments['contact_id'] ?? '');
        if (!$contact || !$currentUser->can('edit', $contact)) {
            $event->setResult(['error' => 'Contact not found or access denied.']);

            return;
        }

        $table = TableRegistry::getTableLocator()->get('Crm.ContactsPhones');
        $entry = $table->newEntity([
            'contact_id' => $contact->id,
            'no' => $arguments['no'] ?? '',
            'kind' => strtoupper($arguments['kind'] ?? 'W'),
            'primary' => (bool)($arguments['primary'] ?? false),
        ]);

        if (!$entry->getErrors() && $table->save($entry)) {
            $this->touchContact($table, $contact->id);
            $event->setResult(['id' => $entry->id, 'no' => $entry->get('no')]);
        } else {
            $event->setResult(['error' => 'Failed to save phone.', 'errors' => $entry->getErrors()]);
        }
    }

    /**
     * Execute Crm.edit_contact_phone tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeEditContactPhone(Event $event, array $arguments, mixed $currentUser): void
    {
        $table = TableRegistry::getTableLocator()->get('Crm.ContactsPhones');
        $entry = $table->find()->where(['id' => $arguments['id'] ?? ''])->first();

        if (!$entry) {
            $event->setResult(['error' => 'Phone entry not found.']);

            return;
        }

        if (!$currentUser->can('edit', $entry)) {
            $event->setResult(['error' => 'You are not authorized to edit this phone entry.']);

            return;
        }

        // @phpstan-ignore argument.templateType
        $table->patchEntity($entry, array_intersect_key($arguments, array_flip(['no', 'kind', 'primary'])));

        // @phpstan-ignore argument.templateType
        if (!$entry->getErrors() && $table->save($entry)) {
            $this->touchContact($table, $entry->contact_id);
            $event->setResult(['id' => $entry->id, 'no' => $entry->get('no')]);
        } else {
            $event->setResult(['error' => 'Failed to update phone.', 'errors' => $entry->getErrors()]);
        }
    }

    /**
     * Execute Crm.delete_contact_phone tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeDeleteContactPhone(Event $event, array $arguments, mixed $currentUser): void
    {
        $table = TableRegistry::getTableLocator()->get('Crm.ContactsPhones');
        $entry = $table->find()->where(['id' => $arguments['id'] ?? ''])->first();

        if (!$entry) {
            $event->setResult(['error' => 'Phone entry not found.']);

            return;
        }

        if (!$currentUser->can('delete', $entry)) {
            $event->setResult(['error' => 'You are not authorized to delete this phone entry.']);

            return;
        }

        if ($table->delete($entry)) {
            $this->touchContact($table, $entry->contact_id);
            $event->setResult(['success' => true]);
        } else {
            $event->setResult(['error' => 'Failed to delete phone.']);
        }
    }

    // --- Emails ---

    /**
     * Execute Crm.add_contact_email tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddContactEmail(Event $event, array $arguments, mixed $currentUser): void
    {
        $contact = $this->loadOwnedContact($currentUser, $arguments['contact_id'] ?? '');
        if (!$contact || !$currentUser->can('edit', $contact)) {
            $event->setResult(['error' => 'Contact not found or access denied.']);

            return;
        }

        $table = TableRegistry::getTableLocator()->get('Crm.ContactsEmails');
        $entry = $table->newEntity([
            'contact_id' => $contact->id,
            'email' => $arguments['email'] ?? '',
            'kind' => strtoupper($arguments['kind'] ?? 'W'),
            'primary' => (bool)($arguments['primary'] ?? false),
        ]);

        if (!$entry->getErrors() && $table->save($entry)) {
            $this->touchContact($table, $contact->id);
            $event->setResult(['id' => $entry->id, 'email' => $entry->get('email')]);
        } else {
            $event->setResult(['error' => 'Failed to save email.', 'errors' => $entry->getErrors()]);
        }
    }

    /**
     * Execute Crm.edit_contact_email tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeEditContactEmail(Event $event, array $arguments, mixed $currentUser): void
    {
        $table = TableRegistry::getTableLocator()->get('Crm.ContactsEmails');
        $entry = $table->find()->where(['id' => $arguments['id'] ?? ''])->first();

        if (!$entry) {
            $event->setResult(['error' => 'Email entry not found.']);

            return;
        }

        if (!$currentUser->can('edit', $entry)) {
            $event->setResult(['error' => 'You are not authorized to edit this email entry.']);

            return;
        }

        // @phpstan-ignore argument.templateType
        $table->patchEntity($entry, array_intersect_key($arguments, array_flip(['email', 'kind', 'primary'])));

        // @phpstan-ignore argument.templateType
        if (!$entry->getErrors() && $table->save($entry)) {
            $this->touchContact($table, $entry->contact_id);
            $event->setResult(['id' => $entry->id, 'email' => $entry->get('email')]);
        } else {
            $event->setResult(['error' => 'Failed to update email.', 'errors' => $entry->getErrors()]);
        }
    }

    /**
     * Execute Crm.delete_contact_email tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeDeleteContactEmail(Event $event, array $arguments, mixed $currentUser): void
    {
        $table = TableRegistry::getTableLocator()->get('Crm.ContactsEmails');
        $entry = $table->find()->where(['id' => $arguments['id'] ?? ''])->first();

        if (!$entry) {
            $event->setResult(['error' => 'Email entry not found.']);

            return;
        }

        if (!$currentUser->can('delete', $entry)) {
            $event->setResult(['error' => 'You are not authorized to delete this email entry.']);

            return;
        }

        if ($table->delete($entry)) {
            $this->touchContact($table, $entry->contact_id);
            $event->setResult(['success' => true]);
        } else {
            $event->setResult(['error' => 'Failed to delete email.']);
        }
    }

    // --- Addresses ---

    /**
     * Execute Crm.add_contact_address tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddContactAddress(Event $event, array $arguments, mixed $currentUser): void
    {
        $contact = $this->loadOwnedContact($currentUser, $arguments['contact_id'] ?? '');
        if (!$contact || !$currentUser->can('edit', $contact)) {
            $event->setResult(['error' => 'Contact not found or access denied.']);

            return;
        }

        $table = TableRegistry::getTableLocator()->get('Crm.ContactsAddresses');
        $entry = $table->newEntity([
            'contact_id' => $contact->id,
            'street' => $arguments['street'] ?? '',
            'zip' => $arguments['zip'] ?? '',
            'city' => $arguments['city'] ?? '',
            'country' => $arguments['country'] ?? '',
            'country_code' => $arguments['country_code'] ?? 'SI',
            'kind' => strtoupper($arguments['kind'] ?? 'W'),
            'primary' => (bool)($arguments['primary'] ?? false),
        ]);

        if (!$entry->getErrors() && $table->save($entry)) {
            $this->touchContact($table, $contact->id);
            $event->setResult(['id' => $entry->id, 'street' => $entry->get('street')]);
        } else {
            $event->setResult(['error' => 'Failed to save address.', 'errors' => $entry->getErrors()]);
        }
    }

    /**
     * Execute Crm.edit_contact_address tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeEditContactAddress(Event $event, array $arguments, mixed $currentUser): void
    {
        $table = TableRegistry::getTableLocator()->get('Crm.ContactsAddresses');
        $entry = $table->find()->where(['id' => $arguments['id'] ?? ''])->first();

        if (!$entry) {
            $event->setResult(['error' => 'Address entry not found.']);

            return;
        }

        if (!$currentUser->can('edit', $entry)) {
            $event->setResult(['error' => 'You are not authorized to edit this address entry.']);

            return;
        }

        // @phpstan-ignore argument.templateType
        $table->patchEntity(
            $entry,
            array_intersect_key(
                $arguments,
                array_flip(['street', 'zip', 'city', 'country', 'country_code', 'kind', 'primary']),
            ),
        );

        // @phpstan-ignore argument.templateType
        if (!$entry->getErrors() && $table->save($entry)) {
            $this->touchContact($table, $entry->contact_id);
            $event->setResult(['id' => $entry->id, 'street' => $entry->get('street')]);
        } else {
            $event->setResult(['error' => 'Failed to update address.', 'errors' => $entry->getErrors()]);
        }
    }

    /**
     * Execute Crm.delete_contact_address tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeDeleteContactAddress(Event $event, array $arguments, mixed $currentUser): void
    {
        $table = TableRegistry::getTableLocator()->get('Crm.ContactsAddresses');
        $entry = $table->find()->where(['id' => $arguments['id'] ?? ''])->first();

        if (!$entry) {
            $event->setResult(['error' => 'Address entry not found.']);

            return;
        }

        if (!$currentUser->can('delete', $entry)) {
            $event->setResult(['error' => 'You are not authorized to delete this address entry.']);

            return;
        }

        if ($table->delete($entry)) {
            $this->touchContact($table, $entry->contact_id);
            $event->setResult(['success' => true]);
        } else {
            $event->setResult(['error' => 'Failed to delete address.']);
        }
    }

    // --- Bank accounts ---

    /**
     * Execute Crm.add_contact_account tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddContactAccount(Event $event, array $arguments, mixed $currentUser): void
    {
        $contact = $this->loadOwnedContact($currentUser, $arguments['contact_id'] ?? '');
        if (!$contact || !$currentUser->can('edit', $contact)) {
            $event->setResult(['error' => 'Contact not found or access denied.']);

            return;
        }

        $table = TableRegistry::getTableLocator()->get('Crm.ContactsAccounts');
        $entry = $table->newEntity([
            'contact_id' => $contact->id,
            'iban' => $arguments['iban'] ?? '',
            'bic' => $arguments['bic'] ?? null,
            'bank' => $arguments['bank'] ?? null,
            'kind' => strtoupper($arguments['kind'] ?? 'B'),
            'primary' => (bool)($arguments['primary'] ?? false),
        ]);

        if (!$entry->getErrors() && $table->save($entry)) {
            $this->touchContact($table, $contact->id);
            $event->setResult(['id' => $entry->id, 'iban' => $entry->get('iban')]);
        } else {
            $event->setResult(['error' => 'Failed to save account.', 'errors' => $entry->getErrors()]);
        }
    }

    /**
     * Execute Crm.edit_contact_account tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeEditContactAccount(Event $event, array $arguments, mixed $currentUser): void
    {
        $table = TableRegistry::getTableLocator()->get('Crm.ContactsAccounts');
        $entry = $table->find()->where(['id' => $arguments['id'] ?? ''])->first();

        if (!$entry) {
            $event->setResult(['error' => 'Account entry not found.']);

            return;
        }

        if (!$currentUser->can('edit', $entry)) {
            $event->setResult(['error' => 'You are not authorized to edit this account entry.']);

            return;
        }

        // @phpstan-ignore argument.templateType
        $table->patchEntity(
            $entry,
            array_intersect_key($arguments, array_flip(['iban', 'bic', 'bank', 'kind', 'primary'])),
        );

        // @phpstan-ignore argument.templateType
        if (!$entry->getErrors() && $table->save($entry)) {
            $this->touchContact($table, $entry->contact_id);
            $event->setResult(['id' => $entry->id, 'iban' => $entry->get('iban')]);
        } else {
            $event->setResult(['error' => 'Failed to update account.', 'errors' => $entry->getErrors()]);
        }
    }

    /**
     * Execute Crm.delete_contact_account tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeDeleteContactAccount(Event $event, array $arguments, mixed $currentUser): void
    {
        $table = TableRegistry::getTableLocator()->get('Crm.ContactsAccounts');
        $entry = $table->find()->where(['id' => $arguments['id'] ?? ''])->first();

        if (!$entry) {
            $event->setResult(['error' => 'Account entry not found.']);

            return;
        }

        if (!$currentUser->can('delete', $entry)) {
            $event->setResult(['error' => 'You are not authorized to delete this account entry.']);

            return;
        }

        if ($table->delete($entry)) {
            $this->touchContact($table, $entry->contact_id);
            $event->setResult(['success' => true]);
        } else {
            $event->setResult(['error' => 'Failed to delete account.']);
        }
    }

    /**
     * Load a contact accessible by the current user via authorization scope.
     *
     * @param mixed $currentUser Current user with applyScope.
     * @param string $contactId Contact UUID.
     * @return \Crm\Model\Entity\Contact|null
     */
    private function loadOwnedContact(mixed $currentUser, string $contactId): mixed
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        return $currentUser->applyScope('index', $contactsTable->find())
            ->where(['Contacts.id' => $contactId])
            ->first();
    }

    /**
     * Touch the parent contact's modified timestamp.
     *
     * @param \Cake\ORM\Table $subTable Sub-entity table that belongs to Contacts.
     * @param string $contactId Contact UUID.
     * @return void
     */
    private function touchContact(mixed $subTable, string $contactId): void
    {
        // @phpstan-ignore-next-line
        $contact = $subTable->Contacts->get($contactId);
        // @phpstan-ignore-next-line
        $subTable->Contacts->touch($contact);
        // @phpstan-ignore-next-line
        $subTable->Contacts->save($contact);
    }
}
