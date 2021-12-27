<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Documents\Controller\DocumentsController Test Case
 */
class DocumentsControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsAddresses' => 'plugin.Crm.ContactsAddresses',
        'ContactsAccounts' => 'plugin.Crm.ContactsAccounts',
        'Documents' => 'plugin.Documents.Documents',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
        'DocumentsAttachments' => 'plugin.Documents.DocumentsAttachments',
        'DocumentsLinks' => 'plugin.Documents.DocumentsLinks',
        'DocumentsItems' => 'plugin.Documents.DocumentsItems',
        'DocumentsTaxes' => 'plugin.Documents.DocumentsTaxes',
        'DocumentsClients' => 'plugin.Documents.DocumentsClients',
        'Vats' => 'plugin.Documents.Vats',
        'Items' => 'plugin.Documents.Items',
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

        $this->get('documents/documents/index');
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
        $this->get('documents/documents/view/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
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
        $counter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');

        $data = [
            'id' => '',
            'owner_id' => COMPANY_FIRST,
            'user_id' => USER_ADMIN,
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'doc_type' => 'IV',

            'title' => 'Totally new document',
            'descript' => 'Payment details etc',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,

            'pmt_kind' => 0,
            'pmt_sepa_type' => 'OTHR',
            'pmt_type' => 'SI',
            'pmt_module' => '00',
            'pmt_ref' => '02',
            'pmt_descript' => 'New document',

            'location' => 'Ljubljana',

            'documents_items' => [
                // and new item
                [
                    'id' => null,
                    'document_id' => null,
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_descript' => '22 %',
                    'vat_percent' => 22,
                    'descript' => 'Hrup\' 13 SE',
                    'qty' => 2,
                    'unit' => 'pcs',
                    'price' => 100,
                    'discount' => 30,
                ],
            ],
            'buyer' => [
                'id' => null,
                'document_id' => null,
                'title' => 'SomeCompany ltd',
            ],
            'receiver' => [
                'id' => null,
                'document_id' => null,
                'title' => 'SomeCompany ltd',
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['documents_items', 'buyer', 'receiver']);

        $this->post('documents/documents/edit?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);

        $Documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $Documents
            ->find()
            ->contain(['DocumentsItems'])
            ->where(['counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89'])
            ->order(['created DESC'])->first();

        $this->assertRedirect(['action' => 'view', $document->id]);

        $this->assertEquals('Totally new document', $document->title);
        $this->assertEquals($counter->counter + 1, $document->counter);
        $this->assertEquals(1, count($document->documents_items));
        $this->assertEquals(100 * 0.7 * 2, $document->net_total);
        $this->assertEquals(round((100 * 0.7 * 2) * 1.22, 2), $document->total);

        // test if counter increases
        $newCounter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
        $this->assertEquals($counter->counter + 1, $newCounter->counter);

        // test attached clients
        $Contacts = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');
        $buyer = $Contacts
            ->find()
            ->where(['document_id' => $document->id, 'DocumentsClients.kind' => 'BY'])
            ->first();

        $this->assertFalse(empty($buyer));
        $this->assertTextEquals('SomeCompany ltd', $buyer->title);

        $seller = $Contacts
            ->find()
            ->where(['document_id' => $document->id, 'DocumentsClients.kind' => 'II'])
            ->first();
        $this->assertFalse(empty($seller));
        $this->assertTextEquals('Arhim d.o.o.', $seller->title);
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
        ]);
        // Set session data
        $this->login(USER_ADMIN);

        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');

        $data = [
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'title' => 'Uploaded Document',
            'dat_issue' => '2020-05-31',
            'documents_attachments' => [
                0 => [
                    'filename' => [
                        'name' => 'sunset.jpg',
                        'type' => 'image/jpg',
                        'size' => 100963,
                        'tmp_name' => dirname(__FILE__) . DS . 'data' . DS . 'sunset.jpg',
                    ],
                ],
            ],
        ];

        $this->post('documents/documents/edit', $data);

        $this->assertResponseSuccess();
        $this->assertContentType('application/json');
        $this->assertResponseContains('{"document":{');

        //dd((string)$this->_response->getBody());

        $Documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $Documents
            ->find()
            ->contain(['DocumentsItems'])
            ->where(['counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89'])
            ->order(['created DESC'])->first();

        $this->assertEquals('Uploaded Document', $document->title);
        $this->assertEquals($counter->counter + 1, $document->counter);

        // test if counter increases
        $newCounter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
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
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'owner_id' => COMPANY_FIRST,
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'kind' => 'IV',
            'counter' => 1,
            'no' => 'R-cust-012',
            'title' => 'First received document',
            'descript' => 'Usually empty in received documents',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'total' => 146.4,
            'pmt_type' => 'OTHR',
            'pmt_module' => 'SI12',
            'pmt_ref' => '1234',
            'documents_taxes' => [
                [
                    'id' => 1,
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'base' => 120,
                ],
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['documents_taxes']);

        $this->post('documents/documents/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5');

        $this->assertEquals(120, $document->net_total);
        $this->assertEquals(round(120 * 1.22, 2), $document->total);
    }

    /**
     * Test edit taxes in received document
     *
     * ..\..\vendor\bin\phpunit --filter testEditReceivedReceivedTaxes tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testEdit2ReceivedDocumentsTaxes()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'owner_id' => COMPANY_FIRST,
            //'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'kind' => 'IV',
            'counter' => 1,
            'no' => 'R-cust-012',
            'title' => 'First received document',
            'descript' => 'Usually empty in received documents',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'total' => 132.95,
            'pmt_type' => 'OTHR',
            'pmt_module' => 'SI12',
            'pmt_ref' => '1234',
            'documents_taxes' => [
                /*
                // delete this item
                [
                    'id' => 1,
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'base' => 120,
                ],*/
                // and add two new items
                [
                    'id' => null,
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_title' => 'DDV 22%',
                    'vat_percent' => 22,
                    'base' => 100,
                ],
                [
                    'id' => null,
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                    'vat_id' => 'e0ef11f0-0d75-4731-a147-9efaf4462e93',
                    'vat_title' => 'DDV 9.5%',
                    'vat_percent' => 9.5,
                    'base' => 10,
                ],
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['documents_taxes']);

        $this->post('documents/documents/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);

        //var_dump($this->_response);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5', ['contain' => ['DocumentsTaxes']]);

        $this->assertEquals(2, count($document->documents_taxes));
        $this->assertEquals(110, $document->net_total);
        $this->assertEquals(round(100 * 1.22 + 10 * 1.095, 2), $document->total);

        //$taxes = TableRegistry::getTableLocator()->get('Documents.DocumentsTaxes');
        //$taxExist = $taxes->exists(['id' => 1]);
        //$this->assertFalse($taxExist);
    }

    /**
     * Test edit items in issued document
     *
     * ..\..\vendor\bin\phpunit --filter testEditIssedDocumentWithItems tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testEditIssedDocumentWithItems()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'owner_id' => COMPANY_FIRST,
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'counter' => 1,
            'no' => 'ISU-01',
            'title' => 'First issued document',
            'descript' => 'Payment details etc',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'pmt_type' => 'OTHR',
            'pmt_module' => 'SI00',
            'pmt_ref' => '01',
            'documents_items' => [
                // edit items price
                [
                    'id' => 1,
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_title' => 'DDV 22%',
                    'vat_percent' => 22,
                    'descript' => 'Hrup\' 13',
                    'qty' => 1,
                    'unit' => 'pcs',
                    'price' => 300,
                    'discount' => 0,
                ],
                // and new item
                [
                    'id' => null,
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_title' => 'DDV 22%',
                    'vat_percent' => 22,
                    'descript' => 'Hrup\' 13 SE',
                    'qty' => 2,
                    'unit' => 'pcs',
                    'price' => 100,
                    'discount' => 10,
                ],
            ],
        ];

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->setUnlockedFields(['documents_items']);

        $this->post('documents/documents/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6?filter%5Bcounter%5D=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', ['contain' => ['DocumentsItems']]);

        $this->assertEquals(2, count($document->documents_items));
        $this->assertEquals(300 + 100 * 0.9 * 2, $document->net_total);
        $this->assertEquals(round((300 + 100 * 0.9 * 2) * 1.22, 2), $document->total);
    }

    /**
     * Test edit items in issued document with delete
     *
     * ..\..\vendor\bin\phpunit --filter testEditIssedDocumentWithItemsDelete tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testEditIssedDocumentWithItemsDelete()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'owner_id' => COMPANY_FIRST,
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'counter' => 1,
            'no' => 'ISU-01',
            'title' => 'First issued document',
            'descript' => 'Payment details etc',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'pmt_type' => 'OTHR',
            'pmt_module' => 'SI00',
            'pmt_ref' => '01',
            'documents_items' => [
                // delete this item
                /*[
                    'id' => 1,
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'descript' => 'Hrup\' 13',
                    'qty' => 1,
                    'unit' => 'pcs',
                    'price' => 300,
                    'discount' => 0,
                ],*/
                // and new item
                [
                    'id' => null,
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_title' => 'DDV 22%',
                    'vat_percent' => 22,
                    'descript' => 'Hrup\' 13 SE',
                    'qty' => 2,
                    'unit' => 'pcs',
                    'price' => 100,
                    'discount' => 10,
                ],
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['documents_items']);

        $this->post('documents/documents/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6?filter%5Bcounter%5D=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', ['contain' => ['DocumentsItems']]);

        $this->assertEquals(1, count($document->documents_items));
        $this->assertEquals(100 * 0.9 * 2, $document->net_total);
        $this->assertEquals(round((100 * 0.9 * 2) * 1.22, 2), $document->total);

        //$items = TableRegistry::getTableLocator()->get('Documents.DocumentsItems');
        //$deletedItem = $items->exists(['id' => 1]);
        //$this->assertFalse($deletedItem);
    }

    /**
     * Test edit document layouts only
     *
     * ..\..\vendor\bin\phpunit --filter testEditLayouts tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testEditLayouts()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', ['contain' => ['DocumentsItems']]);

        $this->assertEquals(1, count($document->documents_items));
        $this->assertEquals(290, $document->net_total);
        $this->assertEquals(353.8, $document->total);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'tpl_header_id' => 'a08d3c00-7443-40e0-ac62-0caca1747e24',
            'tpl_body_id' => '',
            'tpl_footer_id' => '',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('documents/documents/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', ['contain' => ['DocumentsItems']]);

        $this->assertEquals(1, count($document->documents_items));
        $this->assertEquals(290, $document->net_total);
        $this->assertEquals(353.8, $document->total);

        // received
        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'tpl_header_id' => 'a08d3c00-7443-40e0-ac62-0caca1747e24',
            'tpl_body_id' => '',
            'tpl_footer_id' => '',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('documents/documents/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5', ['contain' => ['DocumentsTaxes']]);

        $this->assertEquals(1, count($document->documents_taxes));
        $this->assertEquals(1, $document->documents_taxes[0]->id);
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

        $this->get('documents/documents/delete/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertRedirect(['action' => 'index', '?' => ['counter' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88']]);
    }
}
