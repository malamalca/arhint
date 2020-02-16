<?php
namespace LilCrm\Lib;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use DateTime;
use Syncroton_Backend_IContent;
use Syncroton_Command_FolderSync;
use Syncroton_Data_IData;
use Syncroton_Model_Contact;
use Syncroton_Model_Folder;
use Syncroton_Model_IDevice;
use Syncroton_Model_IEntry;
use Syncroton_Model_IFolder;
use Syncroton_Model_ISyncState;
use Syncroton_Model_SyncCollection;
use Syncroton_Registry;

class ActiveSyncContacts implements Syncroton_Data_IData
{
    protected $_supportedFolderTypes = [
        Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT,
        Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED
    ];

    protected $_phoneTrans = [
        'H' => 'homePhoneNumber',
        'M' => 'mobilePhoneNumber',
        'W' => 'businessPhoneNumber',
        'F' => 'businessFaxNumber',
        'P' => 'home2PhoneNumber'
    ];

    protected $_addressTrans = [
        'H' => 'home',
        'W' => 'business',
        'O' => 'other'
    ];

    /**
     * @var DateTime
     */
    protected $_timeStamp;

    /**
     * the constructor
     *
     * @param Syncroton_Model_IDevice $_device Device.
     * @param DateTime $_timeStamp Timestamp.
     */
    public function __construct(Syncroton_Model_IDevice $_device = null, DateTime $_timeStamp = null)
    {
        $this->_device = $_device;
        $this->_timeStamp = $_timeStamp;
        $this->_db = Syncroton_Registry::getDatabase();
        $this->_tablePrefix = 'Syncroton_';

        $this->_ownerId = null;
        if ($user = Syncroton_Registry::get('user')) {
            $this->_ownerId = $user->company_id;
        }
    }

    /**
     * return one folder identified by id
     *
     * @param  string  $id Folder id.
     * @throws Syncroton_Exception_NotFound
     * @return Syncroton_Model_Folder
     */
    public function getFolder($id)
    {
        //if ($id == 'contacts') {
            return new Syncroton_Model_Folder([
                'serverId' => 'contacts',
                'displayName' => 'All Contacts',
                'type' => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT,
                'parentId' => '0'
            ]);
        /*} else {
            return new Syncroton_Model_Folder(array(
                'serverId'    => 'usercontacts',
                'displayName' => 'User Contacts',
                'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED,
                'parentId'    => '0'
            ));
        }*/
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::createFolder()
     */
    public function createFolder(Syncroton_Model_IFolder $folder)
    {
        if (!in_array($folder->type, $this->_supportedFolderTypes)) {
            throw new Syncroton_Exception_UnexpectedValue();
        }

        $id = !empty($folder->serverId) ? $folder->serverId : sha1(mt_rand() . microtime());

        return $this->getFolder($id);
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::createEntry()
     */
    public function createEntry($_folderId, Syncroton_Model_IEntry $_entry)
    {
        $Contacts = TableRegistry::get('LilCrm.Contacts');
        $c = $Contacts->newEmptyEntity();
        $c->owner_id = $this->_ownerId;
        $c->name = $_entry->firstName;
        $c->surname = $_entry->lastName;
        $c->kind = 'T';
        $c->syncable = true;

        //Log::write('debug', print_r($_entry, true));
        $c->contacts_emails = [];
        $c->contacts_phones = [];
        $c->contacts_addresses = [];
        if (!empty($_entry->email1Address)) {
            $c->contacts_emails[] = $Contacts->ContactsEmails->newEntity(['email' => $_entry->email1Address, 'kind' => 'P']);
        }
        if (!empty($_entry->email2Address)) {
            $c->contacts_emails[] = $Contacts->ContactsEmails->newEntity(['email' => $_entry->email2Address, 'kind' => 'W']);
        }
        if (!empty($_entry->email3Address)) {
            $c->contacts_emails[] = $Contacts->ContactsEmails->newEntity(['email' => $_entry->email3Address, 'kind' => null]);
        }

        foreach ($this->_phoneTrans as $kind => $propName) {
            if (!empty($_entry->{$propName})) {
                $c->contacts_phones[] = $Contacts->ContactsPhones->newEntity(['no' => $_entry->{$propName}, 'kind' => $kind]);
            }
        }

        foreach ($this->_addressTrans as $kind => $root) {
            if (!empty($_entry->{$root . 'AddressCity'}) || !empty($_entry->{$root . 'AddressCountry'}) || !empty($_entry->{$root . 'AddressPostalCode'}) || !empty($_entry->{$root . 'AddressStreet'})) {
                $a = $Contacts->ContactsAddresses->newEntity(['kind' => $kind]);
                if (!empty($_entry->{$root . 'AddressStreet'})) {
                    $a->street = $_entry->{$root . 'AddressStreet'};
                }
                if (!empty($_entry->{$root . 'AddressCity'})) {
                    $a->city = $_entry->{$root . 'AddressCity'};
                }
                if (!empty($_entry->{$root . 'AddressPostalCode'})) {
                    $a->zip = $_entry->{$root . 'AddressPostalCode'};
                }
                if (!empty($_entry->{$root . 'AddressCountry'})) {
                    $a->country = $_entry->{$root . 'AddressCountry'};
                }
                $c->contacts_addresses[] = $a;
            }
        }

        $Contacts->save($c);

        return $c->id;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::deleteEntry()
     */
    public function deleteEntry($_folderId, $_serverId, $_collectionData)
    {
        $Contacts = TableRegistry::get('LilCrm.Contacts');
        $contact = $Contacts->get($_serverId);
        $result = $Contacts->delete($contact);

        return (bool)$result;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::deleteFolder()
     */
    public function deleteFolder($_folderId)
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::emptyFolderContents()
     */
    public function emptyFolderContents($folderId, $options)
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getAllFolders()
     */
    public function getAllFolders()
    {
        $result = [
            'contacts' => new Syncroton_Model_Folder([
                'serverId' => 'contacts',
                'displayName' => 'All Contacts',
                'type' => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT,
                'parentId' => '0'
            ]),
            /*'usercontacts' => new Syncroton_Model_Folder([
                'serverId'    => 'usercontacts',
                'displayName' => 'User Contacts',
                'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED,
                'parentId'    => '0'
            ]),*/
        ];

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getChangedEntries()
     */
    public function getChangedEntries($_folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = null, $filterType = null)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;

        $Contacts = TableRegistry::get('LilCrm.Contacts');
        $query = $Contacts->find('list');

        $query
            ->select(['id'])
            ->where([
                'owner_id' => $this->_ownerId,
                'kind' => 'T',
                'syncable' => true,
                'modified >' => $_startTimeStamp->format("Y-m-d H:i:s")
            ]);

        if ($_endTimeStamp instanceof DateTime) {
            $query->where(['modified <' => $_endTimeStamp->format("Y-m-d H:i:s")]);
        }

        $ids = array_keys($query->toArray());

        return $ids;
    }

    /**
     * retrieve folders which were modified since last sync
     *
     * @param  DateTime  $startTimeStamp Start date time.
     * @param  DateTime  $endTimeStamp Ent date time.
     * @return array list of Syncroton_Model_Folder
     */
    public function getChangedFolders(DateTime $startTimeStamp, DateTime $endTimeStamp)
    {
        $result = [
            'contacts' => new Syncroton_Model_Folder([
                'serverId' => 'contacts',
                'displayName' => 'All Contacts',
                'type' => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT,
                'parentId' => '0'
            ]),
            /*'usercontacts' => new Syncroton_Model_Folder([
                'serverId'    => 'usercontacts',
                'displayName' => 'User Contacts',
                'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED,
                'parentId'    => '0'
            ])*/
        ];
        //$result = [];
        return $result;
    }

    /**
     * @param  Syncroton_Model_IFolder|string  $_folderId Folder id.
     * @param  string                          $_filter Filter string
     * @return array
     */
    public function getServerEntries($_folderId, $_filter)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;

        $Contacts = TableRegistry::get('LilCrm.Contacts');
        $ids = array_keys($Contacts->find('list')
            ->select(['id'])
            ->where([
                'owner_id' => $this->_ownerId,
                'kind' => 'T',
                'syncable' => true
            ])
            ->toArray());

        return $ids;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getCountOfChanges()
     */
    public function getCountOfChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState)
    {
        $allClientEntries = $contentBackend->getFolderState($this->_device, $folder);
        $allServerEntries = $this->getServerEntries($folder->serverId, $folder->lastfiltertype);

        $addedEntries = array_diff($allServerEntries, $allClientEntries);
        $deletedEntries = array_diff($allClientEntries, $allServerEntries);
        $changedEntries = $this->getChangedEntries($folder->serverId, $syncState->lastsync, null, $folder->lastfiltertype);

        return count($addedEntries) + count($deletedEntries) + count($changedEntries);
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getFileReference()
     */
    public function getFileReference($fileReference)
    {
        throw new Syncroton_Exception_NotFound('filereference not found');
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getEntry()
     */
    public function getEntry(Syncroton_Model_SyncCollection $collection, $serverId)
    {
        $Contacts = TableRegistry::get('LilCrm.Contacts');
        $c = $Contacts->get($serverId, ['contain' => ['Companies', 'ContactsPhones', 'ContactsAddresses', 'ContactsEmails']]);
        if (!$c->owner_id == $this->_ownerId) {
            return false;
        }

        if ($c === false) {
            throw new Syncroton_Exception_NotFound("entry $serverId not found in folder {$collection->collectionId}");
        }

        $entry = new Syncroton_Model_Contact();
        $entry->firstName = $c->name;
        $entry->lastName = $c->surname;
        if (!empty($c->company)) {
            $entry->companyName = $c->company->title;
            $entry->jobTitle = $c->job;
        }

        foreach ($c->contacts_phones as $phone) {
            if (isset($this->_phoneTrans[$phone->kind])) {
                $entryProperty = $this->_phoneTrans[$phone->kind];
                $entry->{$entryProperty} = $phone->no;
            }
        }

        foreach ($c->contacts_addresses as $address) {
            if (isset($this->_addressTrans[$address->kind])) {
                $target = $this->_addressTrans[$address->kind];
                $entry->{$target . 'AddressStreet'} = $address->street;
                $entry->{$target . 'AddressCity'} = $address->city;
                $entry->{$target . 'AddressCountry'} = $address->country;
                $entry->{$target . 'AddressPostalCode'} = $address->zip;
            }
        }

        foreach ($c->contacts_emails as $i => $email) {
            if ($i > 3) {
                break;
            }
            $entry->{'email' . ($i + 1) . 'Address'} = $email->email;
        }

        return $entry;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::hasChanges()
     */
    public function hasChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState)
    {
        return (bool)$this->getCountOfChanges($contentBackend, $folder, $syncState);
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::moveItem()
     */
    public function moveItem($_srcFolderId, $_serverId, $_dstFolderId)
    {
        return $_serverId;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::updateEntry()
     */
    public function updateEntry($_folderId, $_serverId, Syncroton_Model_IEntry $_entry)
    {
        $Contacts = TableRegistry::get('LilCrm.Contacts');
        if (!$c = $Contacts->get($_serverId, ['contain' => ['ContactsPhones', 'ContactsAddresses', 'ContactsEmails']])) {
            $c = $Contacts->newEntity();
            $c->id = $_serverId;
            $c->owner_id = $this->_ownerId;
        }
        if (!$c->owner_id == $this->_ownerId) {
            return false;
        }

        $c->name = $_entry->firstName;
        $c->surname = $_entry->lastName;

        if (!empty($_entry->email1Address)) {
            if (!isset($c->contacts_addresses[0])) {
                $c->contacts_addresses[0] = $Contacts->ContactsEmails->newEntity();
            }
            $c->contacts_addresses[0]->contact_id = $c->id;
            $c->contacts_addresses[0]->email = $_entry->email1Address;
        }
        if (!empty($_entry->email2Address)) {
            if (!isset($c->contacts_addresses[1])) {
                $c->contacts_addresses[1] = $Contacts->ContactsEmails->newEntity();
            }
            $c->contacts_addresses[1]->contact_id = $c->id;
            $c->contacts_addresses[1]->email = $_entry->email2Address;
        }
        if (!empty($_entry->email3Address)) {
            if (!isset($c->contacts_addresses[2])) {
                $c->contacts_addresses[2] = $Contacts->ContactsEmails->newEntity();
            }
            $c->contacts_addresses[2]->contact_id = $c->id;
            $c->contacts_addresses[2]->email = $_entry->email3Address;
        }

        // Sync phone numbers
        $remotePhones = [];
        foreach ($this->_phoneTrans as $kind => $entryProperty) {
            if (trim($_entry->{$entryProperty}) != '') {
                $remotePhones[$kind] = $_entry->{$entryProperty};
            }
        }

        if (!empty($c->contacts_phones)) {
            foreach ($c->contacts_phones as $k => $phone) {
                $phoneFound = false;
                foreach ($remotePhones as $kind => $no) {
                    if ($kind == $phone->kind) {
                        $c->contacts_phones[$k]->no = $no;
                        unset($remotePhones[$kind]);
                        $phoneFound = true;
                        break;
                    }
                }
                if (!$phoneFound && !is_null($phone->kind)) {
                    $Contacts->ContactsPhones->delete($phone);
                    unset($c->contacts_phones[$k]);
                }
                $c->dirty('contacts_phones', true);
            }
        }

        foreach ($remotePhones as $kind => $no) {
            $aPhone = $Contacts->ContactsPhones->newEntity();
            $aPhone->contact_id = $c->id;
            $aPhone->kind = $kind;
            $aPhone->no = $no;
            if (!isset($c->contacts_phones)) {
                $c->contacts_phones = [];
            }
            $c->contacts_phones[] = $aPhone;
            $c->dirty('contacts_addresses', true);
        }

        // Sync addresses
        $remoteAddresses = [];
        foreach ($this->_addressTrans as $kind => $entryProperty) {
            if (
                trim($_entry->{$entryProperty . 'AddressStreet'}) != '' ||
                trim($_entry->{$entryProperty . 'AddressCity'}) != '' ||
                trim($_entry->{$entryProperty . 'AddressCountry'}) != '' ||
                trim($_entry->{$entryProperty . 'AddressPostalCode'}) != ''
            ) {
                $remoteAddresses[$kind] = [
                    'street' => $_entry->{$entryProperty . 'AddressStreet'},
                    'city' => $_entry->{$entryProperty . 'AddressCity'},
                    'country' => $_entry->{$entryProperty . 'AddressCountry'},
                    'zip' => $_entry->{$entryProperty . 'AddressPostalCode'}
                ];
            }
        }

        if (!empty($c->contacts_addresses)) {
            foreach ($c->contacts_addresses as $k => $address) {
                $addressFound = false;
                foreach ($remoteAddresses as $kind => $raddr) {
                    if ($kind == $address->kind) {
                        $c->contacts_addresses[$k]->street = $raddr['street'];
                        $c->contacts_addresses[$k]->city = $raddr['city'];
                        $c->contacts_addresses[$k]->zip = $raddr['zip'];
                        $c->contacts_addresses[$k]->country = $raddr['country'];
                        unset($remoteAddresses[$kind]);
                        $addressFound = true;
                        break;
                    }
                }
                if (!$addressFound && !is_null($address->kind)) {
                    $Contacts->ContactsAddresses->delete($address);
                    unset($c->contacts_addresses[$k]);
                }
                $c->dirty('contacts_addresses', true);
            }
        }

        foreach ($remoteAddresses as $kind => $raddr) {
            $anAddress = $Contacts->ContactsAddresses->newEntity();
            $anAddress->contact_id = $c->id;
            $anAddress->kind = $kind;
            $anAddress->street = $raddr['street'];
            $anAddress->city = $raddr['city'];
            $anAddress->zip = $raddr['zip'];
            $anAddress->country = $raddr['country'];
            if (!isset($c->contacts_addresses)) {
                $c->contacts_addresses = [];
            }
            $c->contacts_addresses[] = $anAddress;
            $c->dirty('contacts_addresses', true);
        }

        //Log::write('debug', print_r($c->contacts_addresses, true));

        ////////////////////////////////////////////////////////////////////////////////////////////
        if (!$Contacts->save($c)) {
            Log::write('debug', 'Error syncing Contact ' . $c->title);
            Log::write('debug', print_r($c, true));
        }

        return $c->id;
    }

    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::updateFolder()
     */
    public function updateFolder(Syncroton_Model_IFolder $folder)
    {
        return $this->getFolder($folder->serverId);
    }
}
