<?php
declare(strict_types=1);

namespace App\Controller;

/*use Cake\Datasource\ConnectionManager;
use Syncroton_Registry;
use Zend_Db;
use Zend_Log;
use Zend_Log_Filter_Priority;
use Zend_Log_Writer_Stream;*/

/**
 * This controller is intended for Syncroton testing only!!
 */
class SyncController extends AppController
{
    /**
     * index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        /*$this->Authorization->skipAuthorization();

        $conf = ConnectionManager::get('default')->config();

        $db = Zend_Db::factory('PDO_MYSQL', [
            'host' => $conf['host'],
            'username' => $conf['username'],
            'password' => $conf['password'],
            'dbname' => $conf['database'],
        ]);
        Syncroton_Registry::setDatabase($db);

        $writer = new Zend_Log_Writer_Stream(constant('LOGS') . 'syncroton.log');
        $writer->addFilter(new Zend_Log_Filter_Priority(Zend_Log::DEBUG));
        Syncroton_Registry::set('loggerBackend', new Zend_Log($writer));

        Syncroton_Registry::setContactsDataClass('\LilCrm\Lib\ActiveSyncContacts');

        $device = Syncroton_Registry::getDeviceBackend()->getUserDevice('miha.nahtigal', 'ApplC33JKLWDDTWD');

        /*$doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?'.'>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );

        $folderSync = new \Syncroton_Command_FolderSync($doc, $device, $device->policykey);
        $folderSync->handle();*/

        /*$doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?' . '>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
  <Collections>
    <Collection>
      <SyncKey>0</SyncKey>
      <CollectionId>usercontacts</CollectionId>
      <WindowSize>25</WindowSize>
      <Options>
        <BodyPreference xmlns="uri:AirSyncBase">
          <Type>1</Type>
          <TruncationSize>32768</TruncationSize>
        </BodyPreference>
      </Options>
    </Collection>
  </Collections>
</Sync>');

        $folderSync = new \Syncroton_Command_Sync($doc, $device, $device->policykey);
        $folderSync->handle();
        $responseDoc = $folderSync->getResponse();

        sleep(3);

        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?' . '>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns ="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
  <Collections>
    <Collection>
      <SyncKey>1</SyncKey>
      <CollectionId>usercontacts</CollectionId>
      <GetChanges/>
      <WindowSize>25</WindowSize>
      <Options>
        <BodyPreference xmlns="uri:AirSyncBase">
          <Type>1</Type>
          <TruncationSize>32768</TruncationSize>
        </BodyPreference>
      </Options>
    </Collection>
  </Collections>
</Sync>');
        $folderSync = new \Syncroton_Command_Sync($doc, $device, $device->policykey);
        $folderSync->handle();

        $responseDoc = $folderSync->getResponse();
        $responseDoc->formatOutput = true;

        $response = $this->response
            ->withType('xml')
            ->withStringBody($responseDoc->saveXML());

        return $response;/**/

        return null;
    }
}
