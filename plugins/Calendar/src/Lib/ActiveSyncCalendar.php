<?php
declare(strict_types=1);

namespace Calendar\Lib;

use Cake\Core\Configure;
use Cake\I18n\DateTime as CakeDateTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use DateTime;
use Syncroton_Backend_IContent;
use Syncroton_Command_FolderSync;
use Syncroton_Data_IData;
use Syncroton_Exception_NotFound;
use Syncroton_Exception_UnexpectedValue;
use Syncroton_Model_EmailBody;
use Syncroton_Model_Event;
use Syncroton_Model_FileReference;
use Syncroton_Model_Folder;
use Syncroton_Model_IDevice;
use Syncroton_Model_IEntry;
use Syncroton_Model_IFolder;
use Syncroton_Model_ISyncState;
use Syncroton_Model_SyncCollection;
use Syncroton_Registry;
use Zend_Db_Adapter_Abstract;

class ActiveSyncCalendar implements Syncroton_Data_IData
{
    protected array $_supportedFolderTypes = [
        Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR,
        Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR_USER_CREATED,
    ];

    /**
     * @var \Syncroton_Model_IDevice
     */
    protected Syncroton_Model_IDevice $_device;

    /**
     * @var \DateTime
     */
    protected DateTime $_timeStamp;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected Zend_Db_Adapter_Abstract $_db;

    /**
     * @var string
     */
    protected string $_tablePrefix;

    /**
     * @var string|null
     */
    protected ?string $_ownerId = null;

    /**
     * @var string|null
     */
    protected ?string $_userId = null;

    /**
     * @var string|null
     */
    protected ?string $_calendarName = 'Arhint Calendar';

    /**
     * The constructor
     *
     * @param \Syncroton_Model_IDevice $_device Device interface
     * @param \DateTime $_timeStamp Timestamp
     */
    public function __construct(?Syncroton_Model_IDevice $_device = null, ?DateTime $_timeStamp = null)
    {
        $this->_device = $_device;
        $this->_timeStamp = $_timeStamp;
        $this->_db = Syncroton_Registry::getDatabase();
        $this->_tablePrefix = 'Syncroton_';

        $this->_ownerId = null;

        $user = Syncroton_Registry::get('user');
        if (!empty($user)) {
            $this->_ownerId = $user->company_id;
            $this->_userId = $user->id;
        }
    }

    /**
     * Returns Syncroton_Model_Event from Calendars\Event entity.
     *
     * @param \Syncroton_Model_SyncCollection $collection Events collection.
     * @param mixed $serverId Event Server id.
     * @return \Syncroton_Model_Event|bool
     */
    public function getEntry(Syncroton_Model_SyncCollection $collection, mixed $serverId): bool|Syncroton_Model_Event
    {
        /** @var \Calendar\Model\Table\EventsTable $EventsTable */
        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $event = $EventsTable->get($serverId);

        if (!$event->calendar_id == $this->_ownerId) {
            return false;
        }

        if (empty($event)) {
            throw new Syncroton_Exception_NotFound("Event $serverId not found in folder {$collection->collectionId}");
        }

        ////////////////////////////////////////////////////////////////////////////////////////////
        $entry = new Syncroton_Model_Event();
        $entry->subject = $event->title;
        $entry->location = $event->location;
        $entry->allDayEvent = $event->all_day ? 1 : 0;

        $entry->startTime = $event->dat_start
            ? new DateTime($event->dat_start->setTimezone('UTC')->toDateTimeString())
            : '';
        $entry->endTime = $event->dat_end ? new DateTime($event->dat_end->setTimezone('UTC')->toDateTimeString()) : '';
        $entry->reminder = $event->reminder;
        //recurrence

        $entry->uID = $event->id;

        $entry->body = new Syncroton_Model_EmailBody();
        $entry->body->data = $event->body;

        //Log::write('debug', 'Get Entry: ' . print_r($entry, true));

        return $entry;
    }

    /**
     * Creates Calendar\Event entity from Syncroton_Model_Event
     *
     * @see \Syncroton_Data_IData::createEntry()
     * @param string $_folderId Folder id.
     * @param \Syncroton_Model_IEntry $_entry Entry
     * @return string Calendar\Event.id entity id
     */
    public function createEntry(string $_folderId, Syncroton_Model_IEntry $_entry): string
    {
        //$folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $event = $EventsTable->newEmptyEntity();

        $event->calendar_id = $this->_userId;

        $event->title = $_entry->subject;
        $event->location = $_entry->location;
        $event->body = $_entry->body->data;

        $event->all_day = (bool)$_entry->allDayEvent;

        $event->dat_start = (new CakeDateTime($_entry->startTime, 'UTC'))
            ->setTimezone(Configure::read('App.defaultTimezone'));
        $event->dat_end = (new CakeDateTime($_entry->endTime, 'UTC'))
            ->setTimezone(Configure::read('App.defaultTimezone'));
        $event->reminder = $_entry->reminder;

        if (!$EventsTable->save($event)) {
            Log::write('debug', 'Error creating Event ' . $event->title);
            Log::write('debug', print_r($event, true));
        }

        return $event->id;
    }

    /**
     * Update Calendar\Event entity from Syncroton_Model_Event
     *
     * @see \Syncroton_Data_IData::updateEntry()
     * @param string $_folderId Folder id
     * @param string $_serverId Server id
     * @param \Syncroton_Model_IEntry $_entry Entry
     * @return string|bool
     */
    public function updateEntry(string $_folderId, string $_serverId, Syncroton_Model_IEntry $_entry): bool|string
    {
        //$folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $event = $EventsTable->get($_serverId);
        if (empty($event)) {
            $event = $EventsTable->newEmptyEntity();
            $event->id = $_serverId;
            $event->calendar_id = $this->_userId;
        }

        $event->title = $_entry->subject;
        $event->location = $_entry->location;
        $event->body = $_entry->body->data ?? null;

        $event->all_day = (bool)$_entry->allDayEvent;

        $event->dat_start = (new CakeDateTime($_entry->startTime, 'UTC'))
            ->setTimezone(Configure::read('App.defaultTimezone'));
        $event->dat_end = (new CakeDateTime($_entry->endTime, 'UTC'))
            ->setTimezone(Configure::read('App.defaultTimezone'));
        $event->reminder = $_entry->reminder;

        Log::write('debug', print_r($_entry, true));

        ////////////////////////////////////////////////////////////////////////////////////////////
        if (!$EventsTable->save($event)) {
            Log::write('debug', 'Error updating Event ' . $event->title);
            Log::write('debug', print_r($event, true));
        }

        return $event->id;
    }

    /**
     * deleteEntry
     *
     * @see \Syncroton_Data_IData::deleteEntry()
     * @param string $_folderId Folder id
     * @param string $_serverId Server id
     * @param mixed $_collectionData Collection data
     * @return bool
     */
    public function deleteEntry(string $_folderId, string $_serverId, mixed $_collectionData): bool
    {
        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $event = $EventsTable->get($_serverId);
        if (!$event->calendar_id == $this->_userId) {
            throw new Syncroton_Exception_NotFound("entry $serverId not found");
        }

        $result = $EventsTable->delete($event);

        return (bool)$result;
    }

    /**
     * Return one folder identified by id
     *
     * @param string  $id Folder id.
     * @throws \Syncroton_Exception_NotFound
     * @return \Syncroton_Model_Folder
     */
    public function getFolder(string $id): Syncroton_Model_Folder
    {
        return new Syncroton_Model_Folder([
            'serverId' => 'calendar',
            'displayName' => $this->_calendarName,
            'type' => Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR,
            'parentId' => '0',
        ]);
    }

    /**
     * createFolder
     *
     * @see \Syncroton_Data_IData::createFolder()
     * @param \Syncroton_Model_IFolder $folder Folder
     * @return \Syncroton_Model_Folder
     */
    public function createFolder(Syncroton_Model_IFolder $folder): Syncroton_Model_Folder
    {
        if (!in_array($folder->type, $this->_supportedFolderTypes)) {
            throw new Syncroton_Exception_UnexpectedValue();
        }

        $id = !empty($folder->serverId) ? $folder->serverId : sha1(mt_rand() . microtime());

        return $id;
    }

    /**
     * updateFolder
     *
     * @see \Syncroton_Data_IData::updateFolder()
     * @param \Syncroton_Model_IFolder $_folder Folder
     * @return \Syncroton_Model_Folder
     */
    public function updateFolder(Syncroton_Model_IFolder $_folder): Syncroton_Model_Folder
    {
        return $this->getFolder($_folder->serverId);
    }

    /**
     * deleteFolder
     *
     * @see \Syncroton_Data_IData::deleteFolder()
     * @param string $_folderId Folder id.
     * @return bool
     */
    public function deleteFolder(string $_folderId): bool
    {
        return true;
    }

    /**
     * emptyFolderContents
     *
     * @see \Syncroton_Data_IData::emptyFolderContents()
     * @param string $folderId Folder id
     * @param array $options Options
     * @return bool
     */
    public function emptyFolderContents(string $folderId, array $options): bool
    {
        return true;
    }

    /**
     * getAllFolders
     *
     * @see \Syncroton_Data_IData::getAllFolders()
     * @return array
     */
    public function getAllFolders(): array
    {
        $result = [
            'calendar' => new Syncroton_Model_Folder([
                'serverId' => 'calendar',
                'displayName' => $this->_calendarName,
                'type' => Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR,
                'parentId' => '0',
            ]),
        ];

        return $result;
    }

    /**
     * retrieve folders which were modified since last sync
     *
     * @param \DateTime $startTimeStamp Start date time.
     * @param \DateTime $endTimeStamp Ent date time.
     * @return array list of Syncroton_Model_Folder
     */
    public function getChangedFolders(DateTime $startTimeStamp, DateTime $endTimeStamp): array
    {
        $result = [
            'contacts' => new Syncroton_Model_Folder([
                'serverId' => 'calendar',
                'displayName' => $this->_calendarName,
                'type' => Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR,
                'parentId' => '0',
            ]),
        ];
        //$result = [];
        return $result;
    }

    /**
     * getServerEntries
     *
     * @param \Syncroton_Model_IFolder|string  $_folderId Folder id.
     * @param string                           $_filter Filter string.
     * @return array
     */
    public function getServerEntries(Syncroton_Model_IFolder|string $_folderId, string $_filter): array
    {
        //$folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $ids = array_keys(
            $EventsTable->find('list')
            ->select(['id'])
            ->where([
                'calendar_id' => $this->_userId,
                //'folder_id' => $folderId,
            ])
            ->toArray()
        );

        //Log::write('debug', 'getServerEntries: ' . print_r($ids, true));

        return $ids;
    }

    /**
     * getChangedEntries
     *
     * @see \Syncroton_Data_IData::getChangedEntries()
     * @param string $_folderId Folder id
     * @param \DateTime $_startTimeStamp Start datetime
     * @param \DateTime|null $_endTimeStamp End datetime
     * @param string|null $filterType Filter type
     * @return array
     */
    public function getChangedEntries(
        string $_folderId,
        DateTime $_startTimeStamp,
        ?DateTime $_endTimeStamp = null,
        ?string $filterType = null
    ): array {
        //$folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $query = $EventsTable->find('list');

        $query
            ->select(['id'])
            ->where([
                'calendar_id' => $this->_userId,
                //'folder_id' => $folderId,
                'modified >' => $_startTimeStamp->format('Y-m-d H:i:s'),
            ]);

        if ($_endTimeStamp instanceof DateTime) {
            $query->where(['modified <' => $_endTimeStamp->format('Y-m-d H:i:s')]);
        }

        $ids = array_keys($query->toArray());

        Log::write('debug', 'Get changed entries: ' . print_r($ids, true));

        return $ids;
    }

    /**
     * getCountOfChanges
     *
     * @see \Syncroton_Data_IData::getCountOfChanges()
     * @param \Syncroton_Backend_IContent $contentBackend Content backend
     * @param \Syncroton_Model_IFolder $folder Folder
     * @param \Syncroton_Model_ISyncState $syncState Sync state
     * @return int
     */
    public function getCountOfChanges(
        Syncroton_Backend_IContent $contentBackend,
        Syncroton_Model_IFolder $folder,
        Syncroton_Model_ISyncState $syncState
    ): int {
        $allClientEntries = $contentBackend->getFolderState($this->_device, $folder);
        $allServerEntries = $this->getServerEntries($folder->serverId, $folder->lastfiltertype);

        $addedEntries = array_diff($allServerEntries, $allClientEntries);
        $deletedEntries = array_diff($allClientEntries, $allServerEntries);
        $changedEntries = $this->getChangedEntries(
            $folder->serverId,
            $syncState->lastsync,
            null,
            $folder->lastfiltertype
        );

        return count($addedEntries) + count($deletedEntries) + count($changedEntries);
    }

    /**
     * getFileReference
     *
     * @see \Syncroton_Data_IData::getFileReference()
     * @param mixed $fileReference File reference
     * @return \Syncroton_Model_FileReference
     * @throw \Syncroton_Exception_NotFound
     */
    public function getFileReference(mixed $fileReference): Syncroton_Model_FileReference
    {
        throw new Syncroton_Exception_NotFound('filereference not found');
    }

    /**
     * hasChanges
     *
     * @see \Syncroton_Data_IData::hasChanges()
     * @param \Syncroton_Backend_IContent $contentBackend Content backend
     * @param \Syncroton_Model_IFolder $folder Folder
     * @param \Syncroton_Model_ISyncState $syncState Sync state
     * @return bool
     */
    public function hasChanges(
        Syncroton_Backend_IContent $contentBackend,
        Syncroton_Model_IFolder $folder,
        Syncroton_Model_ISyncState $syncState
    ): bool {
        return (bool)$this->getCountOfChanges($contentBackend, $folder, $syncState);
    }

    /**
     * moveItem
     *
     * @param string $_srcFolderId Source folder id
     * @param string $_serverId Server id
     * @param string $_dstFolderId Destination folder id
     * @return string
     */
    public function moveItem(string $_srcFolderId, string $_serverId, string $_dstFolderId): string
    {
        // TODO
        return $_serverId;
    }
}
