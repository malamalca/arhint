<?php
declare(strict_types=1);

namespace LilTasks\Lib;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use DateTime;
use Syncroton_Backend_IContent;
use Syncroton_Command_FolderSync;
use Syncroton_Data_IData;
use Syncroton_Exception_NotFound;
use Syncroton_Exception_UnexpectedValue;
use Syncroton_Model_EmailBody;
use Syncroton_Model_Folder;
use Syncroton_Model_IDevice;
use Syncroton_Model_IEntry;
use Syncroton_Model_IFolder;
use Syncroton_Model_ISyncState;
use Syncroton_Model_SyncCollection;
use Syncroton_Model_Task;
use Syncroton_Registry;

class LilActiveSyncTasks implements Syncroton_Data_IData
{
    protected $_supportedFolderTypes = [
        Syncroton_Command_FolderSync::FOLDERTYPE_TASK,
        Syncroton_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED,
    ];

    /**
     * @var \Syncroton_Model_IDevice
     */
    protected $_device;

    /**
     * @var \DateTime
     */
    protected $_timeStamp;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var string
     */
    protected $_tablePrefix;

    /**
     * @var string|null
     */
    protected $_ownerId;

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
        }
    }

    /**
     * getEntry
     *
     * @param \Syncroton_Model_SyncCollection $collection Task entry.
     * @param mixed $serverId Task Server id.
     * @return bool|\Syncroton_Model_Task
     */
    public function getEntry(Syncroton_Model_SyncCollection $collection, $serverId)
    {
        /** @var \LilTasks\Model\Table\TasksTable $Tasks */
        $Tasks = TableRegistry::get('LilTasks.Tasks');
        $task = $Tasks->get($serverId, ['contain' => []]);
        if (!$task->owner_id == $this->_ownerId) {
            return false;
        }

        if (empty($task)) {
            throw new Syncroton_Exception_NotFound("entry $serverId not found in folder {$collection->collectionId}");
        }

        ////////////////////////////////////////////////////////////////////////////////////////////
        $entry = new Syncroton_Model_Task();
        $entry->subject = $task->title;
        $entry->importance = $task->priority;

        $entry->body = new Syncroton_Model_EmailBody();
        $entry->body->data = $task->descript;

        if (!empty($task->completed)) {
            $entry->complete = 1;
            $entry->dateCompleted = $task->completed;
        } else {
            $entry->complete = 0;
        }

        return $entry;
    }

    /**
     * createEntry
     *
     * @see \Syncroton_Data_IData::createEntry()
     * @param string $_folderId Folder id.
     * @param \Syncroton_Model_IEntry $_entry Entry
     * @return string
     */
    public function createEntry($_folderId, Syncroton_Model_IEntry $_entry)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;

        $Tasks = TableRegistry::get('LilTasks.Tasks');
        $task = $Tasks->newEmptyEntity();
        $task->owner_id = $this->_ownerId;
        $task->folder_id = $folderId;

        $task->title = $_entry->subject;
        $task->priority = $_entry->importance;

        if ($_entry->complete == 0) {
            $task->completed = null;
        } elseif (!empty($_entry->dateCompleted)) {
            $task->completed = $_entry->dateCompleted;
        }

        $task->descript = $_entry->body->data;

        if (!$Tasks->save($task)) {
            Log::write('debug', 'Error creating Task ' . $task->title);
            Log::write('debug', print_r($task, true));
        }

        return $task->id;
    }

    /**
     * updateEntry
     *
     * @see \Syncroton_Data_IData::updateEntry()
     * @param string $_folderId Folder id
     * @param string $_serverId Server id
     * @param \Syncroton_Model_IEntry $_entry Entry
     * @return bool|string
     */
    public function updateEntry($_folderId, $_serverId, Syncroton_Model_IEntry $_entry)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;

        $Tasks = TableRegistry::get('LilTasks.Tasks');
        $task = $Tasks->get($_serverId, ['contain' => []]);
        if (empty($task)) {
            $task = $Tasks->newEmptyEntity();
            $task->id = $_serverId;
            $task->folder_id = $folderId;
            $task->owner_id = $this->_ownerId;
        }
        if (!$task->owner_id == $this->_ownerId) {
            throw new Syncroton_Exception_NotFound(sprintf('entry %s not found', $_serverId));
        }

        $task->title = $_entry->subject;
        $task->priority = $_entry->importance;

        if ($_entry->complete == 0) {
            $task->completed = null;
        } elseif (!empty($_entry->dateCompleted)) {
            $task->completed = $_entry->dateCompleted;
        }

        $task->descript = $_entry->body->data;

        ////////////////////////////////////////////////////////////////////////////////////////////
        if (!$Tasks->save($task)) {
            Log::write('debug', 'Error updating Task ' . $task->title);
            Log::write('debug', print_r($task, true));
        }

        return $task->id;
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
    public function deleteEntry($_folderId, $_serverId, $_collectionData)
    {
        $Tasks = TableRegistry::get('LilTasks.Tasks');
        $task = $Tasks->get($_serverId);
        if (!$task->owner_id == $this->_ownerId) {
            throw new Syncroton_Exception_NotFound("entry $serverId not found");
        }

        $result = $Tasks->delete($task);

        return (bool)$result;
    }

    /**
     * Return one folder identified by id
     *
     * @param  string  $id Folder id.
     * @throws \Syncroton_Exception_NotFound
     * @return \Syncroton_Model_Folder
     */
    public function getFolder($id)
    {
        $TasksFolder = TableRegistry::get('LilTasks.TasksFolders');
        $folder = $TasksFolder->get($id);

        if (!$folder) {
            throw new Syncroton_Exception_NotFound('Folder not found');
        }

        return new Syncroton_Model_Folder([
            'serverId' => $folder->id,
            'displayName' => $folder->title,
            'type' => Syncroton_Command_FolderSync::FOLDERTYPE_TASK,
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
    public function createFolder(Syncroton_Model_IFolder $folder)
    {
        if (!in_array($folder->type, $this->_supportedFolderTypes)) {
            throw new Syncroton_Exception_UnexpectedValue();
        }

        $TasksFolder = TableRegistry::get('LilTasks.TasksFolders');
        $f = $TasksFolder->newEntity();
        $f->owner_id = $this->_ownerId;
        $f->title = $folder->displayName;
        $f->user_created = $folder->type == Syncroton_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED;
        $TasksFolder->save($f);

        return $this->getFolder($f->id);
    }

    /**
     * updateFolder
     *
     * @see \Syncroton_Data_IData::updateFolder()
     * @param \Syncroton_Model_IFolder $_folder Folder
     * @return \Syncroton_Model_Folder
     */
    public function updateFolder(Syncroton_Model_IFolder $_folder)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;

        $TasksFolders = TableRegistry::get('LilTasks.TasksFolders');
        $folder = $TasksFolders->get($folderId);
        if (empty($folder)) {
            $folder = $TasksFolders->newEmptyEntity();
            $folder->id = $folderId;
            $folder->owner_id = $this->_ownerId;
        }

        if (!$folder->owner_id == $this->_ownerId) {
            throw new Syncroton_Exception_NotFound("folder $folderId not found");
        }

        $folder->title = $_folder->displayName;
        if (!$TasksFolders->save($folder)) {
            Log::write('debug', 'Error syncing Tasks Folder ' . $folder->title);
        }

        return $this->getFolder($folder->id);
    }

    /**
     * deleteFolder
     *
     * @see \Syncroton_Data_IData::deleteFolder()
     * @param string $_folderId Folder id.
     * @return bool
     */
    public function deleteFolder($_folderId)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;

        $TasksFolders = TableRegistry::get('LilTasks.TasksFolders');
        $folder = $TasksFolders->get($folderId);
        if (!$folder->owner_id == $this->_ownerId) {
            throw new Syncroton_Exception_NotFound(sprintf('folder %s not found', $_folderId));
        }

        $result = $TasksFolders->delete($folder);

        return (bool)$result;
    }

    /**
     * emptyFolderContents
     *
     * @see \Syncroton_Data_IData::emptyFolderContents()
     * @param string $folderId Folder id
     * @param array $options Options
     * @return bool
     */
    public function emptyFolderContents($folderId, $options)
    {
        // TODO
        return true;
    }

    /**
     * getAllFolders
     *
     * @see \Syncroton_Data_IData::getAllFolders()
     * @return array
     */
    public function getAllFolders()
    {
        $result = [];

        $TasksFolders = TableRegistry::get('LilTasks.TasksFolders');
        $query = $TasksFolders->find();
        $query
            ->select(['id', 'title'])
            ->where([
                'owner_id' => $this->_ownerId,
            ]);
        $folders = $query->all();

        foreach ($folders as $folder) {
            $result[$folder->id] = new Syncroton_Model_Folder([
                'serverId' => $folder->id,
                'displayName' => $folder->title,
                'type' => $folder->user_created ?
                    Syncroton_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED :
                    Syncroton_Command_FolderSync::FOLDERTYPE_TASK,
                'parentId' => '0',
            ]);
        }

        return $result;
    }

    /**
     * retrieve folders which were modified since last sync
     *
     * @param \DateTime $startTimeStamp Start date time.
     * @param \DateTime $endTimeStamp Ent date time.
     * @return array list of Syncroton_Model_Folder
     */
    public function getChangedFolders(DateTime $startTimeStamp, DateTime $endTimeStamp)
    {
        $TasksFolders = TableRegistry::get('LilTasks.TasksFolders');
        $query = $TasksFolders->find();

        $query
            ->select(['id', 'title'])
            ->where([
                'owner_id' => $this->_ownerId,
                'modified >' => $startTimeStamp->format('Y-m-d H:i:s'),
            ]);

        if ($endTimeStamp instanceof DateTime) {
            $query->where(['modified <' => $endTimeStamp->format('Y-m-d H:i:s')]);
        }

        $folders = $query->all();

        //Log::write('debug', 'Get changed folders: ' . print_r($folders->toArray(), true));

        $result = [];
        foreach ($folders as $folder) {
            $result[$folder->id] = new Syncroton_Model_Folder([
                'serverId' => $folder->id,
                'displayName' => $folder->title,
                'type' => Syncroton_Command_FolderSync::FOLDERTYPE_TASK,
                'parentId' => '0',
            ]);
        }

        return $result;
    }

    /**
     * getServerEntries
     *
     * @param  \Syncroton_Model_IFolder|string  $_folderId Folder id.
     * @param  string                          $_filter Filter string.
     * @return array
     */
    public function getServerEntries($_folderId, $_filter)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;

        $Tasks = TableRegistry::get('LilTasks.Tasks');
        $ids = array_keys(
            $Tasks->find('list')
            ->select(['id'])
            ->where([
                'owner_id' => $this->_ownerId,
                'folder_id' => $folderId,
            ])
            ->toArray()
        );

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
        $_folderId,
        DateTime $_startTimeStamp,
        ?DateTime $_endTimeStamp = null,
        $filterType = null
    ) {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;

        $Tasks = TableRegistry::get('LilTasks.Tasks');
        $query = $Tasks->find('list');

        $query
            ->select(['id'])
            ->where([
                'owner_id' => $this->_ownerId,
                'folder_id' => $folderId,
                'modified >' => $_startTimeStamp->format('Y-m-d H:i:s'),
            ]);

        if ($_endTimeStamp instanceof DateTime) {
            $query->where(['modified <' => $_endTimeStamp->format('Y-m-d H:i:s')]);
        }

        $ids = array_keys($query->toArray());

        //Log::write('debug', 'Get changed entries: ' . print_r($ids, true));

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
    ) {
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
    public function getFileReference($fileReference)
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
    ) {
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
    public function moveItem($_srcFolderId, $_serverId, $_dstFolderId)
    {
        // TODO
        return $_serverId;
    }
}
