<?php
declare(strict_types=1);

namespace Crm\Lib;

use Cake\ORM\TableRegistry;
use Sabre\CardDAV;
use Sabre\CardDAV\Backend\AbstractBackend;
use Sabre\CardDAV\Backend\SyncSupport;
use Sabre\DAV\PropPatch;
use Sabre\VObject;
use Sabre\VObject\Component\VCard;

/**
 * PDO CardDAV backend.
 *
 * This CardDAV backend uses PDO to store addressbooks
 */
class SabreDAVSyncContacts extends AbstractBackend implements SyncSupport
{
    protected array $_phoneTrans = [
        'H' => 'home',
        'M' => 'cell',
        'W' => 'work',
        'F' => 'work',
        'P' => 'home',
    ];

    protected array $_addressTrans = [
        'H' => 'home',
        'W' => 'work',
        'O' => 'home',
    ];

    /**
     * Returns the list of addressbooks for a specific user.
     *
     * @param string $principalUri Principal uri
     * @return array
     */
    public function getAddressBooksForUser($principalUri): array
    {
        $username = explode('/', $principalUri);

        $Users = TableRegistry::getTableLocator()->get('Users');
        $user = $Users->find()
            ->select()
            ->where(['username' => array_pop($username)])
            ->first();

        if (!$user) {
            return [];
        }

        $addressBooks = [];

        $addressBook = [
            'id' => $user->company_id,
            'uri' => 'default',
            'principaluri' => 'principals/' . $user->username,
            '{DAV:}displayname' => 'Default Addressbook',
            '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' => 'Company Addressbook',
            //'{http://calendarserver.org/ns/}getctag' => $row['synctoken'],
            //'{http://sabredav.org/ns}sync-token' => $row['synctoken'] ? $row['synctoken'] : '0',
        ];

        $addressBooks[] = $addressBook;

        return $addressBooks;
    }

    /**
     * Updates properties for an address book.
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $addressBookId Address book id
     * @param \Sabre\DAV\PropPatch $propPatch Properties to patch
     */
    public function updateAddressBook($addressBookId, PropPatch $propPatch): void
    {
    }

    /**
     * Creates a new address book.
     *
     * @param string $principalUri Principal
     * @param string $url Just the 'basename' of the url
     * @param array $properties Properties
     * @return mixed
     */
    public function createAddressBook($principalUri, $url, array $properties): mixed
    {
        return false;
    }

    /**
     * Deletes an entire addressbook and all its contents.
     *
     * @param mixed $addressBookId
     * @return void
     */
    public function deleteAddressBook($addressBookId): void
    {
    }

    /**
     * Returns all cards for a specific addressbook id.
     *
     * This method should return the following properties for each card:
     *   * carddata - raw vcard data
     *   * uri - Some unique url
     *   * lastmodified - A unix timestamp
     *
     * It's recommended to also return the following properties:
     *   * etag - A unique etag. This must change every time the card changes.
     *   * size - The size of the card in bytes.
     *
     * If these last two properties are provided, less time will be spent
     * calculating them. If they are specified, you can also ommit carddata.
     * This may speed up certain requests, especially with large cards.
     *
     * @param mixed $addressbookId
     * @return array
     */
    public function getCards($addressbookId): array
    {
        $ContactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $contacts = $ContactsTable->find()
            ->select()
            ->contain(['ContactsEmails', 'ContactsPhones'])
            ->where(['owner_id' => $addressbookId, 'kind' => 'T', 'syncable' => true])
            ->all();

        $results = [];
        foreach ($contacts as $contact) {
            $card = $this->contactToCard($contact);

            $row = [
                'uri' => '/dav/addressboooks/miha.nahtigal/default/' . $contact->id,
                'size' => strlen($card->serialize()),
                'etag' => '"' . md5($contact->id . $contact->modified->setTimezone('UTC')->toUnixString()) . '"',
                'lastmodifed' => $contact->modified->setTimezone('UTC')->toUnixString(),
            ];
            $results[] = $row;
        }

        return $results;
    }

    /**
     * Returns a specific card.
     *
     * The same set of properties must be returned as with getCards. The only
     * exception is that 'carddata' is absolutely required.
     *
     * If the card does not exist, you must return false.
     *
     * @param mixed  $addressBookId Book id
     * @param string $cardUri Card URI
     * @return array<array-key, mixed>
     */
    public function getCard($addressBookId, $cardUri): array
    {
        $paths = explode('/', $cardUri);
        $cardUri = array_pop($paths);

        $ContactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $contact = $ContactsTable->find()
            ->select()
            ->contain(['ContactsEmails', 'ContactsPhones'])
            ->where(['id' => $cardUri])
            ->first();

        if (!$contact) {
            return [];
        }

        $result['uri'] = '/dav/addressboooks/miha.nahtigal/default/' . $contact->id;
        $result['etag'] = '"' . md5($contact->id . $contact->modified->setTimezone('UTC')->toUnixString()) . '"';
        $result['lastmodified'] = (int)$contact->modified->setTimezone('UTC')->toUnixString();
        $result['carddata'] = $this->contactToCard($contact)->serialize();
        $result['size'] = strlen($result['carddata']);

        return $result;
    }

    /**
     * Returns a list of cards.
     *
     * This method should work identical to getCard, but instead return all the
     * cards in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $addressBookId Book id
     * @param array $uris List of uris
     * @return array
     */
    public function getMultipleCards(mixed $addressBookId, array $uris): array
    {
        $ContactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $contacts = $ContactsTable->find()
            ->select()
            ->contain(['ContactsEmails', 'ContactsPhones'])
            ->where(['id IN' => $uris])
            ->all();

        $results = [];

        foreach ($contacts as $contact) {
            $result = [];
            $result['uri'] = '/dav/addressboooks/miha.nahtigal/default/' . $contact->id;
            $result['etag'] = '"' . md5($contact->id . $contact->modified->setTimezone('UTC')->toUnixString()) . '"';
            $result['lastmodified'] = (int)$contact->modified->setTimezone('UTC')->toUnixString();
            $result['carddata'] = $this->contactToCard($contact)->serialize();
            $result['size'] = strlen($result['carddata']);

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Creates a new card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag is for the
     * newly created resource, and must be enclosed with double quotes (that
     * is, the string itself must contain the double quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed  $addressBookId Book id
     * @param string $cardUri Card URI
     * @param string $cardData Card data
     * @return string|null
     */
    public function createCard($addressBookId, $cardUri, $cardData): ?string
    {
        $vcard = VObject\Reader::read($cardData, VObject\Reader::OPTION_FORGIVING);

        $ContactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $ContactsPhonesTable = TableRegistry::getTableLocator()->get('Crm.ContactsPhones');
        $ContactsEmailsTable = TableRegistry::getTableLocator()->get('Crm.ContactsEmails');

        /** @var \Crm\Model\Entity\Contact $contact */
        $contact = $ContactsTable->newEmptyEntity();
        $contact->owner_id = $addressBookId;
        $contact->id = $cardUri;
        $contact->title = (string)$vcard->FN;
        $contact->name = $vcard->N->getParts()[0] ?? null;
        $contact->surname = $vcard->N->getParts()[1] ?? null;

        if (isset($vcard->TEL)) {
            $phoneTypes = $vcard->TEL['TYPE'];

            $contact->contacts_phones = [];
            foreach ($vcard->TEL as $i => $tel) {
                /** @var \Crm\Model\Entity\ContactsPhone $contactPhone */
                $contactPhone = $ContactsPhonesTable->newEmptyEntity();
                $contactPhone->no = $tel->getParts()[0];
                $contactPhone->primary = isset($tel['PREF']) && $tel['PREF']->getValue() == 1;
                $contactPhone->kind = empty($phoneTypes[$i]) ? 'H' : (
                    $phoneTypes[$i] == 'home' ? 'H' : ($phoneTypes[$i] == 'work' ? 'W' : 'O')
                );

                $contact->contacts_phones[] = $contactPhone;
            }
        }

        if (isset($vcard->EMAIL)) {
            $emailTypes = $vcard->EMAIL['TYPE'];

            $contact->contacts_emails = [];
            foreach ($vcard->EMAIL as $i => $email) {
                //Log::write('debug', print_r($email['TYPE']->getValue(), true));

                /** @var \Crm\Model\Entity\ContactsEmail $contactEmail */
                $contactEmail = $ContactsEmailsTable->newEmptyEntity();
                $contactEmail->email = $email->getParts()[0];
                $contactEmail->primary = isset($email['PREF']) && $email['PREF']->getValue() == 1;
                $contactEmail->kind = empty($emailTypes[$i]) ? 'H' : (
                    $emailTypes[$i] == 'home' ? 'H' : ($emailTypes[$i] == 'work' ? 'W' : 'O')
                );

                $contact->contacts_emails[] = $contactEmail;
            }
        }

        if ($ContactsTable->save($contact, ['associated' => ['ContactsPhones', 'ContactsEmails']])) {
            $etag = '"' . md5($contact->id . $contact->modified->setTimezone('UTC')->toUnixString()) . '"';

            return $etag;
        }

        return null;
    }

    /**
     * Updates a card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag should
     * match that of the updated resource, and must be enclosed with double
     * quotes (that is: the string itself must contain the actual quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId Book id
     * @param string $cardUri Card URI
     * @param string $cardData Card data
     * @return string|null
     */
    public function updateCard($addressBookId, $cardUri, $cardData): ?string
    {
        $vcard = VObject\Reader::read($cardData, VObject\Reader::OPTION_FORGIVING);

        $ContactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $ContactsPhonesTable = TableRegistry::getTableLocator()->get('Crm.ContactsPhones');
        $ContactsEmailsTable = TableRegistry::getTableLocator()->get('Crm.ContactsEmails');

        /** @var \Crm\Model\Entity\Contact $contact */
        $contact = $ContactsTable->get($cardUri, contain: ['ContactsPhones', 'ContactsAddresses', 'ContactsEmails']);
        $contact->title = (string)$vcard->FN;
        $contact->name = $vcard->N->getParts()[0] ?? null;
        $contact->surname = $vcard->N->getParts()[1] ?? null;

        // Sync phone numbers
        $remotePhones = [];
        if (isset($vcard->TEL)) {
            $phoneTypes = $vcard->TEL['TYPE'];

            foreach ($vcard->TEL as $i => $tel) {
                $kind = empty($phoneTypes[$i]) ? 'H' : (
                    $phoneTypes[$i] == 'home' ? 'H' : ($phoneTypes[$i] == 'work' ? 'W' : 'O')
                );
                $remotePhones[$kind] = $tel->getParts()[0];
            }
        }
        if (!empty($contact->contacts_phones)) {
            foreach ($contact->contacts_phones as $k => $phone) {
                $phoneFound = false;
                foreach ($remotePhones as $kind => $no) {
                    if ($kind == $phone->kind) {
                        $contact->contacts_phones[$k]->no = $no;
                        unset($remotePhones[$kind]);
                        $phoneFound = true;
                        break;
                    }
                }
                if (!$phoneFound && !is_null($phone->kind)) {
                    $ContactsPhonesTable->delete($phone);
                    unset($contact->contacts_phones[$k]);
                }
                $contact->setDirty('contacts_phones', true);
            }
        }
        foreach ($remotePhones as $kind => $no) {
            /** @var \Crm\Model\Entity\ContactsPhone $aPhone */
            $aPhone = $ContactsPhonesTable->newEmptyEntity();
            $aPhone->contact_id = $contact->id;
            $aPhone->kind = $kind;
            $aPhone->no = $no;
            if (!isset($contact->contacts_phones)) {
                $contact->contacts_phones = [];
            }
            $contact->contacts_phones[] = $aPhone;
            $contact->setDirty('contacts_phones', true);
        }

        // Sync email addresses
        $remoteEmails = [];
        if (isset($vcard->EMAIL)) {
            $emailTypes = $vcard->EMAIL['TYPE'];
            foreach ($vcard->EMAIL as $i => $email) {
                $kind = empty($emailTypes[$i]) ? 'H' : (
                    $emailTypes[$i] == 'home' ? 'H' : ($emailTypes[$i] == 'work' ? 'W' : 'O')
                );
                $remoteEmails[$kind] = $email->getParts()[0];
            }
        }
        if (!empty($contact->contacts_emails)) {
            foreach ($contact->contacts_emails as $k => $email) {
                $emailFound = false;
                foreach ($remoteEmails as $kind => $remail) {
                    if ($kind == $email->kind) {
                        $contact->contacts_emails[$k]->email = $remail;
                        unset($remoteEmails[$kind]);
                        $emailFound = true;
                        break;
                    }
                }
                if (!$emailFound && !is_null($email->kind)) {
                    $ContactsEmailsTable->delete($email);
                    unset($contact->contacts_emails[$k]);
                }
                $contact->setDirty('contacts_emails', true);
            }
        }
        foreach ($remoteEmails as $kind => $remail) {
            /** @var \Crm\Model\Entity\ContactsEmail $newEmail */
            $newEmail = $ContactsEmailsTable->newEmptyEntity();
            $newEmail->contact_id = $contact->id;
            $newEmail->kind = $kind;
            $newEmail->email = $remail;
            if (!isset($contact->contacts_emails)) {
                $contact->contacts_emails = [];
            }
            $contact->contacts_emails[] = $newEmail;
            $contact->setDirty('contacts_emails', true);
        }

        if ($ContactsTable->save($contact, ['associated' => ['ContactsPhones', 'ContactsEmails']])) {
            $etag = '"' . md5($contact->id . $contact->modified->setTimezone('UTC')->toUnixString()) . '"';

            return $etag;
        }

        return null;
    }

    /**
     * Deletes a card.
     *
     * @param mixed $addressBookId Address book id
     * @param string $cardUri Card URI
     * @return bool
     */
    public function deleteCard($addressBookId, $cardUri): bool
    {
        //Log::write('debug', print_r($cardUri, true));
        $ContactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $contact = $ContactsTable->find()
            ->select()
            ->where(['id' => $cardUri])
            ->first();

        return $ContactsTable->delete($contact);
    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified address book.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'updated.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the addressbook, as reported in the {http://sabredav.org/ns}sync-token
     * property. This is needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param string $addressBookId Address book id
     * @param string $syncToken Token
     * @param int $syncLevel Level
     * @param int|null $limit Limit
     * @return array|null
     */
    public function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null): ?array
    {
        return null;
    }

    /**
     * Transforms Contact entity to vCard
     *
     * @param \Crm\Model\Entity\Contact $contact Contact entity
     * @return \Sabre\VObject\Component\VCard
     */
    private function contactToCard($contact): VCard
    {
        $vcard = new VObject\Component\VCard([
            'PRODID' => 'Arhint',
            'UID' => $contact->id,
            'FN' => $contact->title,
            'N' => [$contact->name, $contact->surname],
        ]);

        if (isset($contact->contacts_emails)) {
            foreach ($contact->contacts_emails as $email) {
                $vcard->add('EMAIL', $email->email, [
                    'type' => $this->_addressTrans[$email->kind] ?? 'home',
                    'pref' => $email->primary ? '1' : '100',
                ]);
            }
        }

        if (isset($contact->contacts_phones)) {
            foreach ($contact->contacts_phones as $phone) {
                $vcard->add('TEL', $phone->no, [
                    'type' => $this->_phoneTrans[$phone->kind] ?? 'home',
                    'pref' => $phone->primary ? '1' : '100',
                ]);
            }
        }

        return $vcard;
    }
}
