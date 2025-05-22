<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use const UPLOAD_ERR_OK;

/**
 * Documents\Controller\DocumentsController Test Case
 */
class DocumentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsAddresses' => 'plugin.Crm.ContactsAddresses',
        'ContactsAccounts' => 'plugin.Crm.ContactsAccounts',
        'Documents' => 'plugin.Documents.Documents',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
        'DocumentsAttachments' => 'plugin.Documents.DocumentsAttachments',
        'DocumentsLinks' => 'plugin.Documents.DocumentsLinks',
        'DocumentsClients' => 'plugin.Documents.DocumentsClients',
        'Expenses' => 'plugin.Expenses.Expenses',
        'Payments' => 'plugin.Expenses.Payments',
        'PaymentsExpenses' => 'plugin.Expenses.PaymentsExpenses',
        'PaymentsAccounts' => 'plugin.Expenses.PaymentsAccounts',
        'Projects' => 'plugin.Projects.Projects',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'environment' => [
                'SERVER_NAME' => 'localhost',
            ],
        ]);
    }

    private function login($userId)
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/documents/documents/index?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc90');
        $this->assertResponseOk();
    }

    /**
     * Test index search method
     *
     * @return void
     */
    public function testIndexSearch()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/documents/documents/index?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc90&search=test');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView()
    {
        // Set session data
        $this->login(USER_ADMIN);
        $this->get('/documents/documents/view/d0d59a31-6de7-4eb4-8230-ca09113a7fe6');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * * ..\..\vendor\bin\phpunit --filter testAddIssued tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testAddIssued()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc90');

        $data = [
            'id' => '',
            'owner_id' => COMPANY_FIRST,
            'user_id' => USER_ADMIN,
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90',

            'title' => 'Totally new document',
            'descript' => 'Payment details etc',
            'dat_issue' => '2015-02-08',
            'location' => 'Ljubljana',

            'receiver' => [
                'id' => null,
                'document_id' => null,
                'title' => 'SomeCompany ltd',
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['issuer', 'receiver']);

        $this->post('/documents/documents/edit?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc90', $data);

        $Documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $Documents
            ->find()
            ->where(['counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90'])
            ->orderBy(['created DESC'])->first();

        $this->assertRedirect(['action' => 'view', $document->id]);

        $this->assertEquals('Totally new document', $document->title);
        $this->assertEquals($counter->counter + 1, $document->counter);

        // test if counter increases
        $newCounter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc90');
        $this->assertEquals($counter->counter + 1, $newCounter->counter);

        // test attached clients
        $Contacts = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');
        $receiver = $Contacts
            ->find()
            ->where(['document_id' => $document->id, 'DocumentsClients.kind' => 'IV'])
            ->first();

        $this->assertFalse(empty($receiver));
        $this->assertTextEquals('SomeCompany ltd', $receiver->title);

        $issuer = $Contacts
            ->find()
            ->where(['document_id' => $document->id, 'DocumentsClients.kind' => 'II'])
            ->first();
        $this->assertFalse(empty($issuer));
        $this->assertTextEquals('Arhim d.o.o.', $issuer->title);
    }

    /**
     * Test add from ArhintScan
     *
     * * ..\..\vendor\bin\phpunit --filter testAddArhintScan tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testAddArhintScan()
    {
        // External program has to send a valid header
        $this->configRequest([
            'headers' => ['Lil-Scan' => 'Valid'],
            //'environment' => [
            //    'PHP_AUTH_USER' => 'admin',
            //    'PHP_AUTH_PW' => 'pass',
            //]
        ]);
        // Set session data
        $this->login(USER_ADMIN);

        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc90');

        $jpgAttachment = new UploadedFile(
            dirname(__FILE__) . DS . 'data' . DS . 'sunset.jpg',
            100963,
            UPLOAD_ERR_OK,
            'sunset.jpg',
            'image/jpg',
        );

        $data = [
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90',
            'title' => 'Uploaded Document',
            'dat_issue' => '2020-05-31',
            'documents_attachments' => [
                0 => [
                    'filename' => $jpgAttachment,
                ],
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/documents/edit', $data);

        $this->assertResponseSuccess();
        $this->assertContentType('application/json');
        $this->assertResponseContains('{"document":{');

        //dd((string)$this->_response->getBody());

        $Documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $Documents
            ->find()
            ->contain(['DocumentsAttachments'])
            ->where(['counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90'])
            ->orderBy(['created DESC'])->first();

        $this->assertFalse(empty($document->documents_attachments[0]));
        $this->assertEquals('Document', $document->documents_attachments[0]->model);

        $this->assertEquals('Uploaded Document', $document->title);
        $this->assertEquals($counter->counter + 1, $document->counter);

        // test if counter increases
        $newCounter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc90');
        $this->assertEquals($counter->counter + 1, $newCounter->counter);
    }

    /**
     * Test edit received document method
     *
     * ..\..\vendor\bin\phpunit --filter testEditReceived tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testEditReceived()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'owner_id' => COMPANY_FIRST,
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90',
            'kind' => 'IV',
            'counter' => 1,
            'no' => 'R-cust-012',
            'title' => 'New title',
            'descript' => 'Usually empty in received documents',
            'dat_issue' => '2015-02-08',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/documents/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc90', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $Documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $Documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6');

        $this->assertEquals('New title', $document->title);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/documents/documents/delete/d0d59a31-6de7-4eb4-8230-ca09113a7fe6');
        $this->assertRedirect(['action' => 'index', '?' => ['counter' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90']]);
    }
}
